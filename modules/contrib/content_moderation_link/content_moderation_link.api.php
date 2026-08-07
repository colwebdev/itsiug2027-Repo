<?php

/**
 * @file
 * Hooks related to Content Moderation Link module.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Perform alterations on the entity object after the content moderation state
 * has been updated.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity object to alter.
 */
function hook_content_moderation_link_alter_entity(EntityInterface &$entity) {
}

/**
 * Perform alterations on the user account object after the content moderation
 * state has been updated.
 *
 * @param \Drupal\Core\Session\AccountInterface $account
 *   The user account object to alter.
 */
function hook_content_moderation_link_alter_account(AccountInterface &$account) {
}

/**
 * @} End of "addtogroup hooks".
 */
