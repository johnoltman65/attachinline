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
   * Each snippet can be a string, or an array with properties.
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
   * @return array
   *   An array of javascript snippets.
   */
  public function getJs();

  /**
   * Sets the CSS snippets attached to the current response.
   *
   * Each  snippet can be a string, or an array with properties.
   *
   * [
   *   'data' => '#logo { border: 1px solid #000; }',
   *   'weight' => 0,
   * ]
   *
   * @param array $css
   *   A list of CSS snippets, in the order they should be loaded.
   *
   * @return $this
   */
  public function setCss(array $css);

  /**
   * Returns the CSS snippets attached to the current response.
   *
   * @return array
   *   An array of CSS snippets.
   */
  public function getCss();

}
