<?php

namespace Drupal\attachinline\Asset;

use Drupal\attachinline\EventSubscriber\CspSubscriber;
use Drupal\Core\Asset\AssetCollectionRendererInterface;
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

  public function __construct(AssetCollectionRendererInterface $assetCollectionRenderer, ModuleHandlerInterface $moduleHandler, CspSubscriber $cspSubscriber) {
    $this->decorated = $assetCollectionRenderer;
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
    ];

    // Loop through all JS assets.
    foreach ($js_assets as $key => $js_asset) {
      if ($js_asset['type'] != 'inline') {
        continue;
      }

      $element = $element_defaults;
      $element['#value'] = $js_asset['data'];

      $elements[] = $element;

      if ($this->moduleHandler->moduleExists('csp')) {
        $cspHash = Csp::calculateHash($js_asset['data']);
        $this->cspSubscriber->registerHash('script-src', $cspHash);
        $this->cspSubscriber->registerHash('script-src-elem', $cspHash);
      }

      // Remove the snippet so that the remaining assets can be passed to the
      // core renderer.
      unset($js_assets[$key]);
    }

    // Add inline snippets to the end.
    return array_merge($this->decorated->render($js_assets), $elements);
  }

}
