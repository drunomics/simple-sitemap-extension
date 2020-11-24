<?php

namespace Drupal\simple_sitemap_extensions\Plugin\simple_sitemap\UrlGenerator;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\file\Entity\File;
use Drupal\file\Plugin\Field\FieldType\FileFieldItemList;
use Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\EntityUrlGenerator;

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
   * {@inheritDoc}
   */
  protected function getEntityImageData(ContentEntityBase $entity) {
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
