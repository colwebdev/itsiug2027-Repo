<?php

declare(strict_types=1);

namespace Drupal\Tests\tagify\Unit\Services;

use Drupal\Core\Theme\ActiveTheme;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\tagify\Services\TagifyThemeResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests the Gin/Claro detection in the theme resolver.
 *
 * @group tagify
 */
#[CoversClass(TagifyThemeResolver::class)]
class TagifyThemeResolverTest extends TestCase {

  /**
   * Tests Gin and Claro detection by machine name and base-theme chain.
   *
   * @param string $activeName
   *   The active theme machine name.
   * @param string[] $baseThemes
   *   Machine names present in the active theme's base-theme chain.
   * @param bool $expectedGin
   *   Whether Gin styling is expected to apply.
   * @param bool $expectedClaro
   *   Whether Claro styling is expected to apply.
   */
  #[DataProvider('themeProvider')]
  public function testDetection(string $activeName, array $baseThemes, bool $expectedGin, bool $expectedClaro): void {
    // Base-theme extensions are non-null Extension objects in production; a
    // placeholder object is enough since the resolver only uses isset().
    $activeTheme = $this->createStub(ActiveTheme::class);
    $activeTheme->method('getName')->willReturn($activeName);
    $activeTheme->method('getBaseThemeExtensions')
      ->willReturn(array_fill_keys($baseThemes, new \stdClass()));

    $themeManager = $this->createStub(ThemeManagerInterface::class);
    $themeManager->method('getActiveTheme')->willReturn($activeTheme);

    $resolver = new TagifyThemeResolver($themeManager);

    $this->assertSame($expectedGin, $resolver->isGinActive());
    $this->assertSame($expectedClaro, $resolver->isClaroActive());
  }

  /**
   * Data provider for theme detection scenarios.
   *
   * @return array<string, array{string, string[], bool, bool}>
   *   Detection scenarios.
   */
  public static function themeProvider(): array {
    return [
      // Gin ships in core as "default_admin".
      'default_admin by name' => ['default_admin', [], TRUE, FALSE],
      'default_admin sub-theme via base chain' => ['my_admin', ['default_admin'], TRUE, FALSE],
      // Gin also ships as the contrib "gin" theme on Drupal 11.1 (still
      // supported), so the legacy machine name must keep matching.
      'contrib gin (machine name "gin")' => ['gin', [], TRUE, FALSE],
      'contrib gin sub-theme via base chain' => ['my_gin', ['gin'], TRUE, FALSE],
      'claro by name' => ['claro', [], FALSE, TRUE],
      'claro sub-theme via base chain' => ['my_claro', ['claro'], FALSE, TRUE],
      'gin wins over claro when both match' => ['default_admin', ['claro'], TRUE, FALSE],
      'unrelated theme matches neither' => ['olivero', [], FALSE, FALSE],
    ];
  }

}
