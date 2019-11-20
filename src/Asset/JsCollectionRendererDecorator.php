<?php

namespace Drupal\attachinline\Asset;

use Drupal\Core\Asset\AssetCollectionRendererInterface;

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

  public function __construct(AssetCollectionRendererInterface $assetCollectionRenderer) {
    $this->decorated = $assetCollectionRenderer;
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

      // TODO Register CSP hash or add CSP nonce value.
      $element['#value'] = $js_asset['data'];

      // Remove the snippet so that the remaining assets can be passed to the
      // core renderer.
      unset($js_assets[$key]);

      $elements[] = $element;
    }

    // Add inline snippets to the end.
    return array_merge($this->decorated->render($js_assets), $elements);
  }

}
