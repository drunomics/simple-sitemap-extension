<?php

namespace Drupal\simple_sitemap_extensions;

use Drupal\simple_sitemap\SimplesitemapManager;

/**
 * Helper functionality for sitemap index variants.
 */
trait SitemapIndexTrait {

  /**
   * Gets variants that are sitemap index variants.
   *
   * @param \Drupal\simple_sitemap\SimplesitemapManager $manager
   *   Sitemap manager.
   * @return array
   */
  protected function getIndexVariants(SimplesitemapManager $manager) {
    $variants = $manager->getSitemapVariants('sitemap_index');
    return $variants;
  }

  /**
   * Gets variants that are not sitemap index variants.
   *
   * @param \Drupal\simple_sitemap\SimplesitemapManager $manager
   *   Sitemap manager.
   * @return array
   */
  protected function getNonIndexVariants(SimplesitemapManager $manager) {
    $variants = $manager->getSitemapVariants();
    return array_filter($variants, function ($variant) {
      return $variant['type'] != 'sitemap_index';
    });
  }

}
