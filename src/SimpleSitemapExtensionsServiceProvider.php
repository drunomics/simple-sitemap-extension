<?php

namespace Drupal\simple_sitemap_extensions;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the simple_sitemap queue worker service.
 */
class SimpleSitemapExtensionsServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($container->hasDefinition('simple_sitemap.queue_worker')) {
      $definition = $container->getDefinition('simple_sitemap.queue_worker');
      $definition->setClass('Drupal\simple_sitemap_extensions\DynamicSitemapQueueWorker');
    }
  }

}
