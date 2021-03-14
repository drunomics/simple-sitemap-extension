<?php

namespace Drupal\simple_sitemap_extensions\Plugin\simple_sitemap\SitemapGenerator;

/**
 * Interface DynamicSitemapGeneratorInterface.
 */
interface DynamicSitemapGeneratorInterface {

  /**
   * Returns an array of chunks of links to generate sitemap from.
   *
   * @param array $results
   *   Array of links to process.
   * @param string $variant
   *   Currently processed variant.
   * @param string $dynamic_parameter_name
   *   Comes from url generator generateData method.
   * @param int|null $max_links
   *   Maximum number of links per chunk.
   *
   * @return array
   *   Array of chunk arrays.
   */
  public function getDynamicChunks(array $results, string $variant, string $dynamic_parameter_name, $max_links = NULL);

  /**
   * Get dynamic parameter name used in url generator plugin.
   *
   * @return string
   */
  public function getDynamicParameterName();

}
