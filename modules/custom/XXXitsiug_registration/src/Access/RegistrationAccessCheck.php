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

    $session = \Drupal::request()->getSession();
    $registration = $session->get('itsiug_registration');

    /*
     * An established registration session grants access.
     *
     * This is the normal path after the Institution Code and PIN
     * have been successfully validated.
     */
    if (!empty($registration['institution_nid'])) {
      return AccessResult::allowed()
        ->cachePerUser();
    }

    /*
     * Institution Representatives.
     *
     * The Institution content type stores the representative
     * in field_representative.
     */
    if (!$account->isAnonymous()) {

      $uid = $account->id();

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
    }

    return AccessResult::forbidden()
      ->cachePerUser();
  }

}