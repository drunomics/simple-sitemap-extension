<?php

namespace Drupal\simple_sitemap_extensions\Plugin\simple_sitemap\SitemapType;

use Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapType\SitemapTypeBase;

/**
 * The sitemap index type.
 *
 * @SitemapType(
 *   id = "sitemap_index",
 *   label = @Translation("Sitemap Index"),
 *   description = @Translation("The sitemap index type."),
 *   sitemapGenerator = "sitemap_index",
 *   urlGenerators = {
 *     "sitemap_variant"
 *   },
 * )
 */
class SitemapIndexType extends SitemapTypeBase {

}
