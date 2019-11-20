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
    if (!isset($render_array['#attached'])) {
      throw new \LogicException('The render array has not yet been rendered, hence not all attachments have been collected yet.');
    }

    $assets = new static();
    if (isset($render_array['#attached']['js'])) {
      foreach ($render_array['#attached']['js'] as $jsItem) {
        if (isset($jsItem['dependencies']) && is_array($jsItem['dependencies'])) {
          $render_array['#attached']['library'] = array_merge($render_array['#attached']['library'], $jsItem['dependencies']);
        }
      }
      $assets->setJs($render_array['#attached']['js']);
    }
    if (isset($render_array['#attached']['library'])) {
      $assets->setLibraries($render_array['#attached']['library']);
    }
    if (isset($render_array['#attached']['drupalSettings'])) {
      $assets->setSettings($render_array['#attached']['drupalSettings']);
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
