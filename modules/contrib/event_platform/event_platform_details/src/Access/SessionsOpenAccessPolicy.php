<?php

declare (strict_types=1);

namespace Drupal\event_platform_details\Access;

use Drupal\Core\Session\AccessPolicyBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\CalculatedPermissionsItem;
use Drupal\Core\Session\RefinableCalculatedPermissionsInterface;
use Drupal\event_platform_details\Cache\Context\SessionsOpen;

/**
 * Allows speakers to create sessions during the appropriate workflow state.
 */
class SessionsOpenAccessPolicy extends AccessPolicyBase {

  /**
   * {@inheritdoc}
   */
  public function calculatePermissions(AccountInterface $account, string $scope): RefinableCalculatedPermissionsInterface {
    $calculated_permissions = parent::calculatePermissions($account, $scope);

    if (!SessionsOpen::areSessionsOpen() || !in_array('speaker', $account->getRoles())) {
      return $calculated_permissions;
    }

    return $calculated_permissions->addItem(new CalculatedPermissionsItem([
      'create session content',
    ]));
  }

  /**
   * {@inheritdoc}
   */
  public function getPersistentCacheContexts(): array {
    return ['sessions_open'];
  }

}
