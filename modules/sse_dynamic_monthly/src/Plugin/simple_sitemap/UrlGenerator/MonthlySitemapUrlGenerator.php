<?php

namespace Drupal\sse_dynamic_monthly\Plugin\simple_sitemap\UrlGenerator;

use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\simple_sitemap\EntityHelper;
use Drupal\simple_sitemap\Logger;
use Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\EntityUrlGenerator;
use Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\UrlGeneratorManager;
use Drupal\simple_sitemap\Simplesitemap;
use Drupal\sse_dynamic_monthly\Plugin\simple_sitemap\SitemapGenerator\MonthlySitemapGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generates entity URLs on a monthly basis.
 *
 * @UrlGenerator(
 *   id = "monthly_url_generator",
 *   label = @Translation("Monthly URL generator"),
 *   description = @Translation("Generates URLs for entity bundles and bundle overrides on a monthly basis."),
 * )
 */
class MonthlySitemapUrlGenerator extends EntityUrlGenerator {

  /**
   * Drupal datetime formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Simplesitemap $generator,
    Logger $logger,
    LanguageManagerInterface $language_manager,
    EntityTypeManagerInterface $entity_type_manager,
    EntityHelper $entityHelper,
    UrlGeneratorManager $url_generator_manager,
    MemoryCacheInterface $memory_cache,
    ModuleHandlerInterface $module_handler,
    DateFormatterInterface $date_formatter
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $generator,
      $logger,
      $language_manager,
      $entity_type_manager,
      $entityHelper,
      $url_generator_manager,
      $memory_cache
    );
    $this->moduleHandler = $module_handler;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('simple_sitemap.generator'),
      $container->get('simple_sitemap.logger'),
      $container->get('language_manager'),
      $container->get('entity_type.manager'),
      $container->get('simple_sitemap.entity_helper'),
      $container->get('plugin.manager.simple_sitemap.url_generator'),
      $container->get('entity.memory_cache'),
      $container->get('module_handler'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDataSets() {
    $data_sets = [];
    $sitemap_entity_types = $this->entityHelper->getSupportedEntityTypes();
    $bundle_settings = $this->generator->setVariants($this->sitemapVariant)->getBundleSettings();
    // Iterate over months until we come to the oldest entity date.
    $date_oldest = new DrupalDateTime($this->dateFormatter->format($this->getOldestEntityCreatedDate($bundle_settings), 'custom', 'Y-m'));
    foreach ($bundle_settings as $entity_type_name => $bundles) {
      $date_now = new DrupalDateTime($this->dateFormatter->format(time(), 'custom', 'Y-m'));
      while ($date_now >= $date_oldest) {
        if (isset($sitemap_entity_types[$entity_type_name])) {

          // Skip this entity type if another plugin is written to override its
          // generation.
          foreach ($this->urlGeneratorManager->getDefinitions() as $plugin) {
            if (isset($plugin['settings']['overrides_entity_type'])
              && $plugin['settings']['overrides_entity_type'] === $entity_type_name) {
              continue 3;
            }
          }

          $entityTypeStorage = $this->entityTypeManager->getStorage($entity_type_name);
          $keys = $sitemap_entity_types[$entity_type_name]->getKeys();

          foreach ($bundles as $bundle_name => $bundle_settings) {
            if (!empty($bundle_settings['index'])) {
              $query = $entityTypeStorage->getQuery();

              if (empty($keys['id'])) {
                $query->sort($keys['id'], 'ASC');
              }
              if (!empty($keys['bundle'])) {
                $query->condition($keys['bundle'], $bundle_name);
              }
              if (!empty($keys['status'])) {
                $query->condition($keys['status'], 1);
              }
              $query
                ->condition('created', strtotime($date_now), '>')
                ->condition('created', strtotime($date_now->modify('+1 month')), '<=');
              // Shift access check to EntityUrlGeneratorBase for language
              // specific access. See
              // https://www.drupal.org/project/simple_sitemap/issues/3102450.
              $query->accessCheck(FALSE);

              $this->moduleHandler->invokeAll('simple_sitemap_extensions_alter_dataset_entity_query', [
                $this,
                $query,
              ]);

              // Set month back to correct value.
              $date_now->modify('-1 month');
              foreach ($query->execute() as $entity_id) {
                $data_sets[] = [
                  MonthlySitemapGenerator::DYNAMIC_GENERATOR_PARAMETER_NAME => (string) $date_now->format('Y-m'),
                  'entity_type' => $entity_type_name,
                  'id' => $entity_id,
                ];
              }
            }
          }
        }
        $date_now->modify('-1 month');
      }
    }
    return $data_sets;
  }

  /**
   * Get date of the oldest article.
   *
   * @return mixed
   *   Usually a timestamp.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getOldestEntityCreatedDate($bundle_settings) {
    $oldest = NULL;
    $sitemap_entity_types = $this->entityHelper->getSupportedEntityTypes();
    foreach ($bundle_settings as $entity_type_name => $bundles) {
      if (isset($sitemap_entity_types[$entity_type_name])) {

        // Skip this entity type if another plugin is written to override its
        // generation.
        foreach ($this->urlGeneratorManager->getDefinitions() as $plugin) {
          if (isset($plugin['settings']['overrides_entity_type'])
            && $plugin['settings']['overrides_entity_type'] === $entity_type_name) {
            continue 2;
          }
        }

        $entityTypeStorage = $this->entityTypeManager->getStorage($entity_type_name);
        $keys = $sitemap_entity_types[$entity_type_name]->getKeys();

        foreach ($bundles as $bundle_name => $bundle_settings) {
          if (!empty($bundle_settings['index'])) {
            $query = $entityTypeStorage->getQuery();
            if (!empty($keys['bundle'])) {
              $query->condition($keys['bundle'], $bundle_name);
            }
            if (!empty($keys['status'])) {
              $query->condition($keys['status'], 1);
            }
            $result = $query
              ->sort('created', 'ASC')
              ->range(0, 1)
              ->execute();
            $oldest_entity = $entityTypeStorage->load(reset($result));
            if (empty($oldest)) {
              $oldest = $oldest_entity->created->value;
            }
            else {
              $oldest = $oldest_entity->created->value < $oldest ? $oldest_entity->created->value : $oldest;
            }
          }
        }
      }
    }
    return $oldest ?? time();
  }

  /**
   * {@inheritdoc}
   */
  protected function processDataSet($data_set) {
    $processed_data_set = parent::processDataSet($data_set);
    foreach ($processed_data_set as &$item) {
      $item['meta'][MonthlySitemapGenerator::DYNAMIC_GENERATOR_PARAMETER_NAME] = $data_set[MonthlySitemapGenerator::DYNAMIC_GENERATOR_PARAMETER_NAME];
    }
    return $processed_data_set;
  }

}
