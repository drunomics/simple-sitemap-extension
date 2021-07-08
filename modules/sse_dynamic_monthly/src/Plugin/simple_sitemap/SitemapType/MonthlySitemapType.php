<?php

namespace Drupal\sse_dynamic_monthly\Plugin\simple_sitemap\SitemapType;

use Drupal\simple_sitemap_extensions\Plugin\simple_sitemap\SitemapType\DynamicSitemapType;

/**
 * The monthly dynamic sitemap type.
 *
 * @SitemapType(
 *   id = "monthly_dynamic_type",
 *   label = @Translation("Monthly dynamic sitemap type"),
 *   description = @Translation("Dynamic sitemap to show articles by month."),
 *   sitemapGenerator = "monthly_dynamic_generator",
 *   urlGenerators = {
 *     "monthly_url_generator"
 *   },
 * )
 */
class MonthlySitemapType extends DynamicSitemapType {

}
