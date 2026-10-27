<?php

declare(strict_types=1);

namespace Drupal\Tests\itsiug_registration\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;

#[Group('itsiug_registration')]
final class LogoutRedirectTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['itsiug_registration'];

  /**
   * Ensures logout always returns users to the front page.
   */
  public function testLogoutIgnoresDestinationAndRedirectsToFront(): void {
    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);
    $this->assertTrue($this->drupalUserIsLoggedIn($account));

    $this->drupalGet('register/logout', [
      'query' => ['destination' => '/register/dashboard'],
    ]);

    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals(Url::fromRoute('<front>')->toString());
    $this->assertFalse($this->drupalUserIsLoggedIn($account));
  }

}
