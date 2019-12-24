<?php

namespace Drupal\attachinline\Asset;

use Drupal\Core\Asset\LibraryDiscoveryInterface;

/**
 * Class LibraryDiscoveryDecorator.
 *
 * @package Drupal\attachinline\Asset
 */
class LibraryDiscoveryDecorator implements LibraryDiscoveryInterface {

  /**
   * The decorated LibraryDiscover Service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  private $decorated;

  /**
   * LibraryDiscoveryDecorator constructor.
   *
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $libraryDiscovery
   *   The Library Discovery Service to decorate.
   */
  public function __construct(LibraryDiscoveryInterface $libraryDiscovery) {
    $this->decorated = $libraryDiscovery;
  }

  /**
   * {@inheritDoc}
   */
  public function getLibrariesByExtension($extension) {
    return $this->decorated->getLibrariesByExtension($extension);
  }

  /**
   * {@inheritDoc}
   */
  public function getLibraryByName($extension, $name) {

    // Return a proxy library that moves its dependency to the header.
    if ($extension == 'attachinline') {
      return [
        'header' => TRUE,
        'dependencies' => [
          $name,
        ],
        'js' => [],
      ];
    }

    return $this->decorated->getLibraryByName($extension, $name);
  }

  /**
   * {@inheritDoc}
   */
  public function clearCachedDefinitions() {
    $this->decorated->clearCachedDefinitions();
  }

}
