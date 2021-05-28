<?php

/**
 * @file
 * Hook definitions.
 */

use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\EntityUrlGenerator;

/**
 * Allows altering of the entity query when generating the dataset.
 *
 * @param \Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\EntityUrlGenerator $generator
 *   The generator.
 * @param \Drupal\Core\Entity\Query\QueryInterface $query
 *   The query which selects the entities to be put on the sitemap.
 */
function hook_simple_sitemap_extensions_alter_dataset_entity_query(EntityUrlGenerator $generator, QueryInterface $query) {

}
