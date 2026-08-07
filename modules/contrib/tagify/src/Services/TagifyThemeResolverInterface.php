<?php

declare(strict_types=1);

namespace Drupal\tagify\Services;

/**
 * Detects whether the active theme is Gin or Claro (or a sub-theme).
 */
interface TagifyThemeResolverInterface {

  /**
   * Determines whether Gin styling should apply to the active theme.
   *
   * Gin ships in Drupal core as the `default_admin` theme, so this returns TRUE
   * for `default_admin` or a sub-theme of it.
   *
   * @return bool
   *   TRUE if `default_admin` (or a sub-theme of it) is active.
   */
  public function isGinActive(): bool;

  /**
   * Determines whether the active theme is Claro, or a sub-theme of Claro.
   *
   * @return bool
   *   TRUE if Claro (or a Claro sub-theme) is active and Gin is not.
   */
  public function isClaroActive(): bool;

}
