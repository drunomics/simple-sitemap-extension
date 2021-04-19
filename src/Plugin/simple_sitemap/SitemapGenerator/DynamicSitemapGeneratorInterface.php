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
   * @param int|null $max_links
   *   Maximum number of links per chunk.
   *
   * @return array
   *   Array of chunk arrays.
   */
  public function getDynamicChunks(array $results, string $variant, $max_links = NULL);

  /**
   * Get current query parameter from the mapping.
   *
   * @param int $delta
   *   Current chunk.
   *
   * @return false|string
   *   Url query parameter or False.
   */
  public function getCurrentChunkParameterFromMapping(int $delta);


  /**
   * Get delta from dynamic parameter from the mapping.
   *
   * @param string|null $param
   *   Current chunk.
   *
   * @return false|string
   *   Url query parameter or False.
   */
  public function getCurrentDeltaFromMapping($param = NULL);

}
