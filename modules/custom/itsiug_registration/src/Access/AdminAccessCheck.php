<?php

namespace Drupal\itsiug_registration\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Role and permission based access checks for ITSIUG admin routes.
 */
class AdminAccessCheck {

  /**
   * Access check for ITSIUG admin pages.
   */
  public function admin(AccountInterface $account): AccessResult {
    return $this->checkAccess($account, ['access itsiug admin']);
  }

  /**
   * Access check for admin-only pages.
   */
  public function adminRole(AccountInterface $account): AccessResult {
    if ($account->isAnonymous() || !in_array('administrator', $account->getRoles(), TRUE)) {
      return AccessResult::forbidden()
        ->cachePerUser();
    }

    return AccessResult::allowed()
      ->cachePerUser();
  }

  /**
   * Access check for ITSIUG reports pages.
   */
  public function reports(AccountInterface $account): AccessResult {
    return $this->checkAccess($account, ['access itsiug reports', 'access itsiug admin']);
  }

  /**
   * Access check for reports restricted to Exco Members.
   */
  public function excoMember(AccountInterface $account): AccessResult {
    if ($account->isAnonymous() || !in_array('exco_member', $account->getRoles(), TRUE)) {
      return AccessResult::forbidden()
        ->cachePerUser();
    }

    return AccessResult::allowed()
      ->cachePerUser();
  }

  /**
   * Allow exco_member role or any of the specified permissions.
   */
  private function checkAccess(AccountInterface $account, array $permissions): AccessResult {
    if ($account->isAnonymous()) {
      return AccessResult::forbidden()
        ->cachePerUser();
    }

    if (in_array('exco_member', $account->getRoles(), TRUE)) {
      return AccessResult::allowed()
        ->cachePerUser();
    }

    foreach ($permissions as $permission) {
      if ($account->hasPermission($permission)) {
        return AccessResult::allowed()
          ->cachePerUser();
      }
    }

    return AccessResult::forbidden()
      ->cachePerUser();
  }

}