<?php

namespace Drupal\simple_sitemap_extensions\Plugin\simple_sitemap\SitemapGenerator;

use Drupal\Component\Datetime\Time;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapGenerator\DefaultSitemapGenerator;
use Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapGenerator\SitemapWriter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Dynamic generator for sitemap index of variants.
 *
 * Dynamic generator should extend this class.
 *
 * @SitemapGenerator(
 *   id = "dynamic_sitemap_index",
 *   label = @Translation("Dynamic sytemap generator"),
 *   description = @Translation("Generates a dynamic sitemap containing links to dynamic sitemap chunks."),
 * )
 */
abstract class AbstractDynamicSitemapGenerator extends DefaultSitemapGenerator implements DynamicSitemapGeneratorInterface {

  const DYNAMIC_GENERATOR_ID = 'dynamic_sitemap_generator';

  const DYNAMIC_GENERATOR_PARAMETER_NAME = 'dynamic-parameter';

  /**
   * Drupal state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Dynamic delta mapping.
   *
   * @var array|null
   */
  protected $dynamicDeltaMapping = NULL;

  /**
   * MonthlySitemapGenerator constructor.
   *
   * @param array $configuration
   *   Configuration of Simple XML Sitemap.
   * @param string $plugin_id
   *   Plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Database\Connection $database
   *   Drupal database connection service.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   Module handler service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language manager service.
   * @param \Drupal\Component\Datetime\Time $time
   *   Time service.
   * @param \Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapGenerator\SitemapWriter $sitemap_writer
   *   Sitemap writer service.
   * @param \Drupal\Core\State\StateInterface $state
   *   Drupal state service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Connection $database,
    ModuleHandler $module_handler,
    LanguageManagerInterface $language_manager,
    Time $time,
    SitemapWriter $sitemap_writer,
    StateInterface $state
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $database, $module_handler, $language_manager, $time, $sitemap_writer);
    $this->state = $state;
  }

  /**
   * Poor man's dependency injection.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Service container.
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   *
   * @return AbstractDynamicSitemapGenerator|\Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapGenerator\SitemapGeneratorBase|static
   *   Constructor parameters.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('module_handler'),
      $container->get('language_manager'),
      $container->get('datetime.time'),
      $container->get('simple_sitemap.sitemap_writer'),
      $container->get('state')
    );
  }

  /**
   * Get sitemap url.
   *
   * @param mixed $delta
   *   Which month to fetch.
   *
   * @return string
   *   Url of a sitemap.
   */
  public function getSitemapUrl($delta = NULL) {
    // @TODO Check if this method is necessary.
    if (is_null($delta)) {
      $parameters = [];
    }
    else {
      $dynamic_parameter = $this->getCurrentChunkFromMapping($delta);
      $parameter = $dynamic_parameter ? $this->getDynamicParameterName() : 'page';
      $parameters = [$parameter => $dynamic_parameter ?? $delta];
    }
    $url = Url::fromRoute(
      'simple_sitemap.sitemap_variant',
      $parameters + ['variant' => $this->sitemapVariant],
      $this->getSitemapUrlSettings()
    );

    return $url->toString();
  }

  /**
   * Get current query parameter from the mapping.
   *
   * @param int $delta
   *   Current chunk.
   *
   * @return false|string
   *   Url query parameter or False.
   */
  protected function getCurrentChunkFromMapping(int $delta) {
    if (empty($this->dynamicDeltaMapping)) {
      $this->dynamicDeltaMapping = $this->state->get(self::DYNAMIC_GENERATOR_ID . '_' . $this->sitemapVariant, FALSE);
    }
    return $this->dynamicDeltaMapping[$delta - self::FIRST_CHUNK_DELTA] ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDynamicChunks($results, $variant, $dynamic_parameter_name, $max_links = NULL) {
    // Create dynamic chunks.
    $dynamic_chunks = [];
    foreach ($results as $link) {
      // Url generator must also include dynamic parameter to create chunks
      // from it.
      $dynamic_chunks[$link['meta'][$dynamic_parameter_name]][] = $link;
    }
    $dynamic_chunks_max_links = [];
    if (!empty($max_links)) {
      foreach ($dynamic_chunks as $dynamic_parameter => $dynamic_chunk) {
        $max_links_chunks = array_chunk($dynamic_chunk, $max_links, TRUE);
        $counter = 1;
        foreach ($max_links_chunks as $max_links_chunk) {
          $dynamic_chunks_max_links[$dynamic_parameter . '-' . (string) ($counter)] = $max_links_chunk;
          $counter++;
        }
      }
    }
    else {
      foreach ($dynamic_chunks as $dynamic_parameter => $dynamic_chunk) {
        $dynamic_chunks_max_links[$dynamic_parameter . '-1'] = $dynamic_chunk;
      }
    }
    $this->state->set(self::DYNAMIC_GENERATOR_ID . '_' . $variant, array_keys($dynamic_chunks_max_links));
    return $dynamic_chunks_max_links;
  }

  /**
   * {@inheritdoc}
   */
  public function getDynamicParameterName() {
    return self::DYNAMIC_GENERATOR_PARAMETER_NAME;
  }

}
