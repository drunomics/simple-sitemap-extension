<?php

namespace Drupal\simple_sitemap_extensions\Plugin\simple_sitemap\SitemapGenerator;

use Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapGenerator\SitemapGeneratorBase;

/**
 * Generator for sitemap index of variants.
 *
 * @package Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapGenerator
 *
 * @SitemapGenerator(
 *   id = "sitemap_index",
 *   label = @Translation("Sitemap index generator"),
 *   description = @Translation("Generates a sitemap index containing links to all sitemap variants."),
 * )
 */
class SitemapIndexGenerator extends SitemapGeneratorBase {

  /**
   * The attributes.
   *
   * @var array
   */
  protected static $attributes = [
    'xmlns' => self::XMLNS,
  ];

  /**
   * {@inheritdoc}
   */
  protected function getXml(array $links) {
    $this->writer->openMemory();
    $this->writer->setIndent(TRUE);
    $this->writer->startSitemapDocument();

    // Add the XML stylesheet to document if enabled.
    if ($this->settings['xsl']) {
      $this->writer->writeXsl();
    }

    $this->writer->writeGeneratedBy();
    $this->writer->startElement('sitemapindex');

    // Add attributes to document.
    $attributes = self::$attributes;
    $sitemap_variant = $this->sitemapVariant;
    $this->moduleHandler->alter('simple_sitemap_attributes', $attributes, $sitemap_variant);
    foreach ($attributes as $name => $value) {
      $this->writer->writeAttribute($name, $value);
    }

    $sitemap_variant = $this->sitemapVariant;
    $this->moduleHandler->alter('simple_sitemap_links', $links, $sitemap_variant);
    foreach ($links as $link) {
      $this->writer->startElement('sitemap');
      $this->writer->writeElement('loc', is_string($link['url']) ? $link['url'] : $link['url']->toString());

      // Add lastmod if any.
      if (isset($link['lastmod'])) {
        $this->writer->writeElement('lastmod', $link['lastmod']);
      }

      // End element: sitemap.
      $this->writer->endElement();
    }

    // End element: sitemapindex.
    $this->writer->endElement();
    $this->writer->endDocument();

    $result = $this->writer->outputMemory();
    return $result;
  }

}
