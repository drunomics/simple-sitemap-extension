<?php

namespace Drupal\simple_sitemap_extensions\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class PathProcessorSitemapVariantIn.
 *
 * @package Drupal\simple_sitemap\PathProcessor
 */
class PathProcessorSitemapVariantIn implements InboundPathProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    $args = explode('/', $path);
    if (count($args) === 4 && $args[3] === 'sitemap.xml') {
      $page = $args[2];
      $path = '/' . $args[1] . '/sitemap.xml';
      $request->query->set('page', $page);
    }

    if (count($args) === 5 && 'sitemaps' === $args[1] && $args[4] === 'sitemap.xml') {
      $page = $args[3];
      $path = '/' . $args[2] . '/sitemap.xml';
      $request->query->set('page', $page);
    }

    return $path;
  }

}
