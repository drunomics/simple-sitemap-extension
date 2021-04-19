<?php

namespace Drupal\simple_sitemap_extensions\Plugin\simple_sitemap\SitemapType;

use Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapType\SitemapTypeBase;

/**
 * The dynamic sitemap type.
 *
 * @SitemapType(
 *   id = "dynamic_sitemap_type",
 *   label = @Translation("Dynamic sitemap type"),
 *   description = @Translation("Dynamic sitemap type can dynamically create sitemap index."),
 *   sitemapGenerator = "dynamic_sitemap_generator",
 *   urlGenerators = {
 *   },
 * )
 */
class DynamicSitemapType extends SitemapTypeBase {

}
