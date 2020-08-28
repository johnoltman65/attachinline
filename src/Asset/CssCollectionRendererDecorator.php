<?php

namespace Drupal\attachinline\Asset;

use Drupal\attachinline\EventSubscriber\CspSubscriber;
use Drupal\attachinline\Render\AttachInlineMarkup;
use Drupal\Core\Asset\AssetCollectionRendererInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\csp\Csp;

/**
 * Render Inline CSS snippets attached to the page.
 */
class CssCollectionRendererDecorator implements AssetCollectionRendererInterface {

  /**
   * The decorated asset collection renderer.
   *
   * @var \Drupal\Core\Asset\AssetCollectionRendererInterface
   */
  private $decorated;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $config;

  /**
   * The Module Handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;

  /**
   * The CSP Subscriber service.
   *
   * @var \Drupal\attachinline\EventSubscriber\CspSubscriber
   */
  private $cspSubscriber;

  /**
   * CssCollectionRendererDecorator constructor.
   *
   * @param \Drupal\Core\Asset\AssetCollectionRendererInterface $assetCollectionRenderer
   *   The decorated Asset Collection Renderer.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The Config Factory service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The Module Handler service.
   * @param \Drupal\attachinline\EventSubscriber\CspSubscriber $cspSubscriber
   *   Attach Inline's CSP Event Subscriber service.
   */
  public function __construct(AssetCollectionRendererInterface $assetCollectionRenderer, ConfigFactoryInterface $config, ModuleHandlerInterface $moduleHandler, CspSubscriber $cspSubscriber) {
    $this->decorated = $assetCollectionRenderer;
    $this->config = $config;
    $this->moduleHandler = $moduleHandler;
    $this->cspSubscriber = $cspSubscriber;
  }

  /**
   * {@inheritDoc}
   *
   * Override core's renderer to allow inline style elements.
   *
   * @see \Drupal\Core\Asset\CssCollectionRenderer
   */
  public function render(array $css_assets) {
    $elements = [];

    // Defaults for each STYLE element.
    $element_defaults = [
      '#type' => 'html_tag',
      '#tag' => 'style',
      '#value' => '',
    ];

    // Loop through all JS assets.
    foreach ($css_assets as $key => $css_asset) {
      if ($css_asset['type'] != 'inline') {
        continue;
      }

      $element = $element_defaults;
      $element['#value'] = AttachInlineMarkup::create($css_asset['data']);

      if ($this->moduleHandler->moduleExists('csp')) {
        $allowMethod = $this->config->get('attachinline.settings')->get('csp-allow-method') ?? 'hash';
        if ($allowMethod == 'nonce') {
          $element['#attributes']['nonce'] = $this->cspSubscriber->getNonce();
          $this->cspSubscriber->registerNonce('style-src');
          $this->cspSubscriber->registerNonce('style-src-elem');
        }
        else {
          $cspHash = Csp::calculateHash($css_asset['data']);
          $this->cspSubscriber->registerHash('style-src', $cspHash);
          $this->cspSubscriber->registerHash('style-src-elem', $cspHash);
        }
      }

      $elements[] = $element;

      // Remove the snippet so that the remaining assets can be passed to the
      // core renderer.
      unset($css_assets[$key]);
    }

    // Add inline snippets to the end.
    return array_merge($this->decorated->render($css_assets), $elements);
  }

}
