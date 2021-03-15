<?php

namespace Drupal\sse_dynamic_monthly\Plugin\simple_sitemap\SitemapGenerator;

use Drupal\simple_sitemap_extensions\Plugin\simple_sitemap\SitemapGenerator\AbstractDynamicSitemapGenerator;

/**
 * Generator for sitemap index of variants.
 *
 * @package Drupal\monthly_simple_sitemap\Plugin\simple_sitemap\SitemapGenerator
 *
 * @SitemapGenerator(
 *   id = "monthly_dynamic_generator",
 *   label = @Translation("Monthly dynamic sitemap generator"),
 *   description = @Translation("Generates sitemap links to articles per monhtl sitemaps."),
 * )
 */
class MonthlySitemapGenerator extends AbstractDynamicSitemapGenerator {

  const DYNAMIC_GENERATOR_ID = 'monthly_sitemap_generator';

  const DYNAMIC_GENERATOR_PARAMETER_NAME = 'month';

}
