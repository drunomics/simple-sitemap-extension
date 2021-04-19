<?php

namespace Drupal\simple_sitemap_extensions;

use Drupal\simple_sitemap_extensions\Plugin\simple_sitemap\SitemapGenerator\DynamicSitemapGeneratorInterface;
use Drupal\simple_sitemap\Queue\QueueWorker;

/**
 * Sitemap queue worker with dynamic sitemap variant extension.
 *
 * @package Drupal\simple_sitemap_extension
 */
class DynamicSitemapQueueWorker extends QueueWorker {

  /**
   * Generate dynamic chunks of the sitemap.
   *
   * @param bool $complete
   *   All the links has been processed.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function generateVariantChunksFromResults($complete = FALSE) {
    $generator = $this->manager->getSitemapGenerator($this->generatorProcessedNow)
      ->setSitemapVariant($this->variantProcessedNow)
      ->setSettings($this->generatorSettings);
    if (!$generator instanceof DynamicSitemapGeneratorInterface) {
      parent::generateVariantChunksFromResults($complete);
    }
    else {
      if (!empty($this->results)) {
        $processed_results = $this->results;
        $this->moduleHandler->alter('simple_sitemap_links', $processed_results, $this->variantProcessedNow);
        $this->processedResults = array_merge($this->processedResults, $processed_results);
        $this->results = [];
      }

      if (empty($this->processedResults)) {
        return;
      }

      $dynamic_chunks = $generator->getDynamicChunks($this->processedResults, $this->variantProcessedNow, $this->maxLinks);
      foreach ($dynamic_chunks as $dynamic_chunk) {
        if ($complete) {
          $generator->generate($dynamic_chunk);
          $this->processedResults = array_diff_key($this->processedResults, $dynamic_chunk);
        }
      }
    }
  }

}
