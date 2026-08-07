<?php

declare(strict_types=1);

namespace Drupal\tagify\Services;

use Drupal\Core\Theme\ThemeManagerInterface;

/**
 * Detects whether the active theme is Gin or Claro (or a sub-theme).
 */
final class TagifyThemeResolver implements TagifyThemeResolverInterface {

  public function __construct(
    private readonly ThemeManagerInterface $themeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function isGinActive(): bool {
    // Gin ships as the contrib "gin" theme on Drupal 11.1, and in core as
    // the "default_admin" theme from Drupal 12. Cover both names since the
    // module supports ^11.1 || ^12.
    $theme = $this->themeManager->getActiveTheme();
    $base_themes = $theme->getBaseThemeExtensions();
    return in_array($theme->getName(), ['gin', 'default_admin'], TRUE)
      || isset($base_themes['gin'])
      || isset($base_themes['default_admin']);
  }

  /**
   * {@inheritdoc}
   */
  public function isClaroActive(): bool {
    // If the active theme is Gin, return FALSE.
    if ($this->isGinActive()) {
      return FALSE;
    }

    $theme = $this->themeManager->getActiveTheme();
    return $theme->getName() === 'claro'
      || isset($theme->getBaseThemeExtensions()['claro']);
  }

}
