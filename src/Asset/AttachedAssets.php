<?php

namespace Drupal\attachinline\Asset;

use Drupal\Core\Asset\AttachedAssets as CoreAttachedAssets;

/**
 * Class AttachedAssets.
 *
 * @package Drupal\attachinline\Asset
 */
class AttachedAssets extends CoreAttachedAssets implements AttachedAssetsInterface {

  /**
   * The (ordered) list of JavaScript snippets attached to the current response.
   *
   * @var string[]
   */
  protected $js = [];

  /**
   * {@inheritdoc}
   */
  public static function createFromRenderArray(array $render_array) {

    $assets = parent::createFromRenderArray($render_array);

    if (isset($render_array['#attached']['js'])) {
      $libraries = $assets->getLibraries();

      foreach ($render_array['#attached']['js'] as $jsItem) {
        if (isset($jsItem['dependencies']) && is_array($jsItem['dependencies'])) {
          $libraries = array_merge($libraries, $jsItem['dependencies']);
        }
      }
      $assets->setLibraries($libraries);
      $assets->setJs($render_array['#attached']['js']);
    }

    return $assets;
  }

  /**
   * {@inheritDoc}
   */
  public function setJs(array $js) {
    $this->js =  $js;
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function getJs() {
    return $this->js;
  }
}
