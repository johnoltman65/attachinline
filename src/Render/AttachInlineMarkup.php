<?php

namespace Drupal\attachinline\Render;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Render\MarkupTrait;

/**
 * Defines an object that passes unescaped strings through the render system.
 *
 * This object should only be constructed with known-safe strings for inline
 * JavaScript and CSS. Any user entered data should be sanitized before
 * including in the markup string.
 *
 * @see \Drupal\Core\Render\Markup
 */
class AttachInlineMarkup implements MarkupInterface, \Countable {
  use MarkupTrait;

}
