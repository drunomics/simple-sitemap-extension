<?php

namespace Drupal\simple_sitemap_extensions\PathProcessor;

use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Class PathProcessorSitemapVariantOut.
 *
 * @package Drupal\simple_sitemap\PathProcessor
 */
class PathProcessorSitemapVariantOut implements OutboundPathProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    $args = explode('/', $path);
    // /sitemap/{ variant }/{ chunk }/sitemap.xml ->  /{variant}/sitemap.xml?page=1
    if (count($args) === 4 && is_numeric($args[2]) && $args[3] === 'sitemap.xml') {
      $page = $args[2];
      $path = '/' . $args[1] . '/sitemap.xml';
      $request->query->set('page', $page);
    }

    return $path;
  }

}
