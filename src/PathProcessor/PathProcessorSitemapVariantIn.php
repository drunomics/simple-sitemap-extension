<?php

namespace Drupal\simple_sitemap_extensions\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Processes incoming paths.
 */
class PathProcessorSitemapVariantIn implements InboundPathProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    if (str_ends_with($path, '/sitemap.xml')) {
      // Remove an index prefix if existing in the form of
      // { index }/sub/{ variant }/{ chunk }/sitemap.xml.
      $args = explode('/', $path);
      if (!empty($args[2]) && $args[2] == 'sub') {
        unset($args[1], $args[2]);
        $path = implode('/', $args);
      }

      // Turn /{ variant }/{ chunk }/sitemap.xml back into
      // /sitemap/{ variant }/{ chunk }/sitemap.xml.
      if (count($args) === 4) {
        $path = '/sitemaps' . $path;
      }
    }
    return $path;
  }

}
