<?php

namespace Drupal\simple_sitemap_extensions\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('simple_sitemap.sitemap_variant')) {
      $route->setDefault('_controller', '\Drupal\simple_sitemap_extensions\Controller\DynamicSimplesitemapController::getSitemap');
    }
    if ($route = $collection->get('simple_sitemap.sitemap_default')) {
      $route->setDefault('_controller', '\Drupal\simple_sitemap_extensions\Controller\DynamicSimplesitemapController::getSitemap');
    }
    if ($route = $collection->get('simple_sitemap_extensions.sitemap_variant_page')) {
      $route->setDefault('_controller', '\Drupal\simple_sitemap_extensions\Controller\DynamicSimplesitemapController::getSitemap');
    }

  }

}
