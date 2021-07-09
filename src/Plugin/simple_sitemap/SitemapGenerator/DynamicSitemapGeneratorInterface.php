<?php

namespace Drupal\simple_sitemap_extensions\Plugin\simple_sitemap\SitemapGenerator;

/**
 * Interface DynamicSitemapGeneratorInterface.
 *
 * The delta forms the unique key for the sitemap. It looks up the delta from
 * the dynamic chunk though.
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
   * @return array[]
   *   Array of link chunks (=an array of links), keyed by dynamic chunk name.
   */
  public function getDynamicChunks(array $results, string $variant, $max_links = NULL);

  /**
   * Gets the dynamic chunk name for the given delta.
   *
   * Translates the page delta to the chunk name used by the dynamic site
   * map.
   *
   * @param int $delta
   *   Current page.
   *
   * @return false|string
   *   The dynamic chunk or false.
   */
  public function getCurrentChunkParameterFromMapping(int $delta);

  /**
   * Gets the page delta from the dynamic chunk.
   *
   * @param string|null $chunk
   *   The dynamic chunk name.
   *
   * @return false|int
   *   The page delta or false.
   */
  public function getCurrentDeltaFromMapping($chunk = NULL);

}
