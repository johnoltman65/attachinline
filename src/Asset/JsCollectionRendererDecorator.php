<?php

namespace Drupal\attachinline\Asset;

use Drupal\attachinline\EventSubscriber\CspSubscriber;
use Drupal\attachinline\Render\AttachInlineMarkup;
use Drupal\Core\Asset\AssetCollectionRendererInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\csp\Csp;

/**
 * Render Inline JavaScript snippets attached to the page.
 */
class JsCollectionRendererDecorator implements AssetCollectionRendererInterface {

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
   * JsCollectionRendererDecorator constructor.
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
   * Override core's renderer to allow inline script elements.
   *
   * @see \Drupal\Core\Asset\JsCollectionRenderer
   */
  public function render(array $js_assets) {
    $elements = [];

    // Defaults for each SCRIPT element.
    $element_defaults = [
      '#type' => 'html_tag',
      '#tag' => 'script',
      '#value' => '',
      '#attributes' => [],
    ];

    // Loop through all JS assets.
    foreach ($js_assets as $key => $js_asset) {
      if ($js_asset['type'] != 'inline') {
        continue;
      }

      $element = $element_defaults;
      $element['#value'] = AttachInlineMarkup::create($js_asset['data']);

      if (!empty($js_asset['attributes'])) {
        $element['#attributes'] += $js_asset['attributes'];
      }

      if ($this->moduleHandler->moduleExists('csp')) {
        $allowMethod = $this->config->get('attachinline.settings')->get('csp-allow-method') ?? 'hash';
        if ($allowMethod == 'nonce') {
          $element['#attributes']['nonce'] = $this->cspSubscriber->getNonce();
          $this->cspSubscriber->registerNonce('script-src');
          $this->cspSubscriber->registerNonce('script-src-elem');
        }
        else {
          $cspHash = Csp::calculateHash($js_asset['data']);
          $this->cspSubscriber->registerHash('script-src', $cspHash);
          $this->cspSubscriber->registerHash('script-src-elem', $cspHash);
        }
      }

      $elements[] = $element;

      // Remove the snippet so that the remaining assets can be passed to the
      // core renderer.
      unset($js_assets[$key]);
    }

    // Add inline snippets to the end.
    return array_merge($this->decorated->render($js_assets), $elements);
  }

}
