<?php

namespace Drupal\simple_sitemap_extensions\Plugin\simple_sitemap\UrlGenerator;

use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\file\Entity\File;
use Drupal\file\Plugin\Field\FieldType\FileFieldItemList;
use Drupal\simple_sitemap\EntityHelper;
use Drupal\simple_sitemap\Logger;
use Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\EntityUrlGenerator;
use Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\UrlGeneratorManager;
use Drupal\simple_sitemap\Simplesitemap;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Extends the basis entity url generator.
 *
 * @UrlGenerator(
 *   id = "extended_entity",
 *   label = @Translation("Extended entity URL generator"),
 *   description = @Translation("Generates URLs for entity bundles and bundle overrides."),
 * )
 */
class ExtendedEntityUrlGenerator extends EntityUrlGenerator {

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
    ModuleHandlerInterface $module_handler
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
  }

  /**
   * {@inheritDoc}
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
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getDataSets() {
    $data_sets = [];
    $sitemap_entity_types = $this->entityHelper->getSupportedEntityTypes();

    foreach ($this->generator->setVariants($this->sitemapVariant)->getBundleSettings() as $entity_type_name => $bundles) {
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

            if (empty($keys['id'])) {
              $query->sort($keys['id'], 'ASC');
            }
            if (!empty($keys['bundle'])) {
              $query->condition($keys['bundle'], $bundle_name);
            }
            if (!empty($keys['status'])) {
              $query->condition($keys['status'], 1);
            }

            // Shift access check to EntityUrlGeneratorBase for language
            // specific access.
            // See https://www.drupal.org/project/simple_sitemap/issues/3102450.
            $query->accessCheck(FALSE);

            $this->moduleHandler->invokeAll('simple_sitemap_extensions_alter_dataset_entity_query', [$this, $query]);

            $data_set = [
              'entity_type' => $entity_type_name,
              'id' => [],
            ];
            foreach ($query->execute() as $entity_id) {
              $data_set['id'][] = $entity_id;
              if (count($data_set['id']) >= $this->entitiesPerDataset) {
                $data_sets[] = $data_set;
                $data_set['id'] = [];
              }
            }
            // Add the last data set if there are some IDs gathered.
            if (!empty($data_set['id'])) {
              $data_sets[] = $data_set;
            }
          }
        }
      }
    }

    return $data_sets;
  }

  /**
   * {@inheritDoc}
   */
  protected function getEntityImageData(ContentEntityInterface $entity) {
    $image_paths = \Drupal::configFactory()->get('simple_sitemap_extensions.extended_entity.image_paths')->get();
    if (empty($image_paths[$entity->getEntityTypeId()][$entity->bundle()])) {
      return parent::getEntityImageData($entity);
    }

    $image_paths = $image_paths[$entity->getEntityTypeId()][$entity->bundle()];
    $image_data = $this->getImageDataFromImagePaths($entity, (array) $image_paths);

    return $image_data;
  }

  /**
   * Traverses the entity according to path configuration to fetch image data.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   A fieldable entity.
   * @param array $image_paths
   *   Configuration of image paths.
   *
   * @return array
   *   The data.
   */
  private function getImageDataFromImagePaths(FieldableEntityInterface $entity, array $image_paths) {
    $fields = !empty($image_paths['fields']) && is_array($image_paths['fields']) ? $image_paths['fields'] : [];
    $image_data = [];
    foreach ($fields as $field_name => $field_config) {
      if (!$entity->hasField($field_name)) {
        continue;
      }

      // The final field name will be set to TRUE, then fetch the data for it.
      if (is_bool($field_config) && $field_config) {
        $target_field = $entity->get($field_name);
        if ($target_field instanceof FileFieldItemList) {
          $image_data += $this->getImageDataFromFileField($target_field);
        }
      }
      elseif (is_array($field_config)) {
        foreach ($field_config as $item_config) {
          $required_bundles = !empty($item_config['bundles']) ? $item_config['bundles'] : [];
          $field = $entity->get($field_name);

          if ($field instanceof FileFieldItemList) {
            $image_data += $this->getImageDataFromFileField($field);
          }
          elseif ($field instanceof EntityReferenceFieldItemListInterface && !empty($item_config['fields'])) {
            foreach ($field as $field_item) {
              if (!$field_item->entity instanceof EntityInterface) {
                continue;
              }
              if (!empty($required_bundles) && !in_array($field_item->entity->bundle(), $required_bundles)) {
                continue;
              }
              $image_data += $this->getImageDataFromImagePaths($field_item->entity, $item_config);
            }
          }
        }
      }
    }

    return $image_data;
  }

  /**
   * Gets the image data for an image field.
   *
   * @param \Drupal\file\Plugin\Field\FieldType\FileFieldItemList $field
   *   Image field.
   *
   * @return array
   *   The data.
   */
  private function getImageDataFromFileField(FileFieldItemList $field) {
    $image_data = [];
    foreach ($field->getValue() as $value) {
      $id = $value['target_id'];
      $image_data[$id] = [
        'path' => file_create_url(File::load($value['target_id'])->getFileUri()),
        'alt' => $value['alt'],
        'title' => $value['title'],
      ];
    }

    return $image_data;
  }

}
