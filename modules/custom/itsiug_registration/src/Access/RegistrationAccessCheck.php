<?php

namespace Drupal\itsiug_registration\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Controls access to the conference registration workflow.
 */
class RegistrationAccessCheck {

  /**
   * Determines whether the current user has institution access.
   */
  public function access(AccountInterface $account) {

    // Anonymous users are not allowed.
    if ($account->isAnonymous()) {
      return AccessResult::forbidden()
        ->cachePerUser();
    }

    $uid = $account->id();

    /*
     * Institution Representatives.
     *
     * The Institution content type stores the representative
     * in field_representative.
     */
    $institution_ids = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'institution')
      ->condition('field_representative', $uid)
      ->range(0, 1)
      ->execute();

    if (!empty($institution_ids)) {
      return AccessResult::allowed()
        ->cachePerUser();
    }

    /*
     * Preserve access for an existing registration session.
     *
     * This allows the normal registration workflow to continue
     * functioning for users who have established a session.
     */
    $session = \Drupal::request()->getSession();
    $registration = $session->get('itsiug_registration');

    if (!empty($registration['institution_nid'])) {
      return AccessResult::allowed()
        ->cachePerUser();
    }

    return AccessResult::forbidden()
      ->cachePerUser();
  }

}