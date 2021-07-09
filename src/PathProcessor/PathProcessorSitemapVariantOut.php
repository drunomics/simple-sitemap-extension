<?php

namespace Drupal\simple_sitemap_extensions\PathProcessor;

use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Processes outgoing paths.
 */
class PathProcessorSitemapVariantOut implements OutboundPathProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    $args = explode('/', $path);
    // We map /sitemaps/{ variant }/{ chunk }/sitemap.xml
    // to /{ variant }/{ chunk }/sitemap.xml.
    if (count($args) === 5 && $args[1] == 'sitemaps'  && $args[4] === 'sitemap.xml') {
      $path = '/' . $args[2] . '/' . $args[3] . '/sitemap.xml';
    }

    // Turns { path }/sitemap.xml into
    // { index }/sub/{ path }/sitemap.xml.
    if (!empty($options['sitemap_index'])) {
      $path = '/' . $options['sitemap_index'] . '/sub' . $path;
    }
    return $path;
  }

}
