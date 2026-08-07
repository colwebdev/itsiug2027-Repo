<?php

declare(strict_types=1);

namespace Drupal\tagify_user_list;

use Drupal\Core\Entity\EntityInterface;

/**
 * Resolves avatar image fields and styled image URLs for user list widgets.
 */
interface UserImageResolverInterface {

  /**
   * Gets the image/media field options for a user entity.
   *
   * @param string $entity_type_id
   *   The entity type ID, typically 'user'.
   * @param array $bundles
   *   An array of bundle names.
   * @param bool $include_empty
   *   Whether to include the default 'Picture' option in the returned list.
   *
   * @return array
   *   An associative array of field name => label, suitable for a select.
   */
  public function getImageOptions(string $entity_type_id, array $bundles, bool $include_empty = TRUE): array;

  /**
   * Gets the styled image URL from a user entity's image field.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The user entity from which the image path is retrieved.
   * @param string $image
   *   The name of the image field in the user entity.
   * @param string $image_style
   *   The image style to be applied to the image.
   *
   * @return string
   *   The URL of the styled image, or an empty string if not available.
   */
  public function getImagePath(EntityInterface $entity, string $image, string $image_style): string;

}
