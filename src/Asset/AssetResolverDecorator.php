<?php

namespace Drupal\attachinline\Asset;

use Drupal\Core\Asset\AssetResolver;
use Drupal\Core\Asset\AssetResolverInterface;
use Drupal\Core\Asset\AttachedAssetsInterface as CoreAttachedAssetsInterface;

/**
 * Class AssetResolverDecorator.
 *
 * @package Drupal\attachinline\Asset
 */
class AssetResolverDecorator implements AssetResolverInterface {

  /**
   * The Decorated Asset Resolver.
   *
   * @var \Drupal\Core\Asset\AssetResolverInterface
   */
  private $decorated;

  public function __construct(AssetResolverInterface $assetResolver) {
    $this->decorated = $assetResolver;
  }

  /**
   * {@inheritDoc}
   */
  public function getCssAssets(CoreAttachedAssetsInterface $assets, $optimize) {
    return $this->decorated->getCssAssets($assets, $optimize);
  }

  /**
   * {@inheritDoc}
   */
  public function getJsAssets(CoreAttachedAssetsInterface $assets, $optimize) {
    $jsAssets = $this->decorated->getJsAssets($assets, $optimize);

    $javascript = [];
    if ($assets instanceof AttachedAssetsInterface) {
      $defaultOptions = [
        'type' => 'inline',
        'group'  => JS_DEFAULT,
        'scope' => 'footer',
        'weight' => 0,
      ];

      foreach ($assets->getJs() as $options) {
        if (is_string($options)) {
          $options = ['data' => $options];
        }
        $options += $defaultOptions;

        // Always add a tiny value to the weight, to conserve the insertion
        // order.
        $options['weight'] += count($javascript) / 1000;

        $javascript[hash('sha256', $options['data'])] = $options;
      }

      // Sort JavaScript snippets, so that they appear in the correct order.
      if (method_exists(get_class($this->decorated), 'sort')) {
        uasort($javascript, get_class($this->decorated) . '::sort');
      }
      else {
        uasort($javascript, AssetResolver::class . '::sort');
      }

      // Prepare the return value: filter JavaScript assets per scope.
      foreach ($javascript as $key => $item) {
        if  ($item['scope'] == 'header') {
          $jsAssets[0][$key] = $item;
        }
        else{
          $jsAssets[1][$key] = $item;
        }
      }
    }

    return  $jsAssets;
}}
