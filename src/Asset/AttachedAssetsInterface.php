<?php

namespace Drupal\attachinline\Asset;

use Drupal\Core\Asset\AttachedAssetsInterface as CoreAttachedAssetsInterface;

/**
 * Extend the Core AttachedAssetsInterface to store inline assets.
 */
interface AttachedAssetsInterface extends CoreAttachedAssetsInterface {

  /**
   * Sets the JavaScript snippets attached to the current response.
   *
   * Each  snippet can be a string, or an array with properties
   *
   * [
   *   'data' => 'alert("Hi!");',
   *   'scope' => 'header',
   *   'weight' => 0,
   * ]
   *
   * @param array $js
   *   A list of JavaScript snippets, in the order they should be loaded.
   *
   * @return $this
   */
  public function setJs(array $js);

  /**
   * Returns the JavaScript snippets attached to the current response.
   *
   * @return string[]
   */
  public function getJs();
}
