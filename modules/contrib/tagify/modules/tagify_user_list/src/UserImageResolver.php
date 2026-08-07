<?php

declare(strict_types=1);

namespace Drupal\tagify_user_list;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\field\FieldConfigInterface;

/**
 * Resolves avatar image fields and styled image URLs for user list widgets.
 */
final class UserImageResolver implements UserImageResolverInterface {

  use StringTranslationTrait;

  public function __construct(
    private readonly EntityFieldManagerInterface $entityFieldManager,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    TranslationInterface $string_translation,
  ) {
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function getImageOptions(string $entity_type_id, array $bundles, bool $include_empty = TRUE): array {
    $options = [];

    if ($include_empty && $entity_type_id === 'user') {
      $options['user_picture'] = $this->t('Picture');
    }

    foreach ($bundles as $bundle) {
      $fields = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);

      foreach ($fields as $field_name => $field_definition) {
        // Skip base fields.
        if (!$field_definition instanceof FieldConfigInterface) {
          continue;
        }

        // Keep image fields and media entity-reference fields only.
        $is_image_field = match ($field_definition->getType()) {
          'image' => TRUE,
          'entity_reference' => $field_definition->getSetting('target_type') === 'media',
          default => FALSE,
        };
        if ($is_image_field) {
          $options[$field_name] = $field_definition->getLabel();
        }
      }
    }

    if (empty($options)) {
      $options[''] = $this->t('No image or media fields found');
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getImagePath(EntityInterface $entity, string $image, string $image_style): string {
    /** @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
    if (!$entity->hasField($image) || $entity->get($image)->isEmpty()) {
      return '';
    }

    /** @var \Drupal\image\ImageStyleInterface|null $style */
    $style = $this->entityTypeManager->getStorage('image_style')->load($image_style);
    if ($style === NULL) {
      return '';
    }

    $user_image = $entity->get($image)->entity;
    // A dangling reference passes isEmpty() but resolves to NULL here.
    if ($user_image === NULL) {
      return '';
    }
    if ($user_image->getEntityTypeId() === 'media') {
      /** @var \Drupal\media\MediaInterface $user_image */
      $file_id = $user_image->getSource()->getSourceFieldValue($user_image);
      /** @var \Drupal\file\FileInterface|null $file */
      $file = $this->entityTypeManager->getStorage('file')->load($file_id);
      return $file !== NULL ? $style->buildUrl($file->getFileUri()) : '';
    }

    /** @var \Drupal\file\FileInterface $user_image */
    return $style->buildUrl($user_image->getFileUri());
  }

}
