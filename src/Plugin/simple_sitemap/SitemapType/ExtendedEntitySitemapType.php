<?php

namespace Drupal\simple_sitemap_extensions\Plugin\simple_sitemap\SitemapType;

use Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapType\SitemapTypeBase;

/**
 * The extended entity sitemap type.
 *
 * @SitemapType(
 *   id = "extended_entity",
 *   label = @Translation("Extended entity"),
 *   description = @Translation("Allows adding images within paragraphs and altering the entity query. Provided by the Sitemap Extensions module."),
 *   sitemapGenerator = "default",
 *   urlGenerators = {
 *     "extended_entity"
 *   },
 * )
 */
class ExtendedEntitySitemapType extends SitemapTypeBase {

}
