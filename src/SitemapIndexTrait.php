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
   *
   * @return array
   */
  protected function getIndexVariants(SimplesitemapManager $manager) {
    $variants = $manager->getSitemapVariants();
    $types = $manager->getSitemapTypes();
    return array_filter($variants, function ($variant) use ($types) {
      // Don't go by type itself, but rather by generator to allow for custom
      // sitemap index types.
      return $types[$variant['type']]['sitemapGenerator'] == 'sitemap_index';
    });
  }

  /**
   * Gets variants that are not sitemap index variants.
   *
   * @param \Drupal\simple_sitemap\SimplesitemapManager $manager
   *   Sitemap manager.
   *
   * @return array
   */
  protected function getNonIndexVariants(SimplesitemapManager $manager) {
    $variants = $manager->getSitemapVariants();
    $types = $manager->getSitemapTypes();
    return array_filter($variants, function ($variant) use ($types) {
      // Don't go by type itself, but rather by generator to allow for custom
      // sitemap index types.
      return $types[$variant['type']]['sitemapGenerator'] != 'sitemap_index';
    });
  }

}
