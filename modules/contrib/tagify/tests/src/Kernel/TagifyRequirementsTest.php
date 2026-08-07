<?php

declare(strict_types=1);

namespace Drupal\Tests\tagify\Kernel;

use Drupal\Core\Asset\LibrariesDirectoryFileFinder;
use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\tagify\Hook\TagifyRequirements;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the Tagify runtime requirements report.
 *
 * @group tagify
 */
#[RunTestsInSeparateProcesses]
class TagifyRequirementsTest extends TagifyKernelTestBase {

  /**
   * The Tagify requirements hook.
   *
   * @var \Drupal\tagify\Hook\TagifyRequirements
   */
  protected TagifyRequirements $requirements;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->requirements = new TagifyRequirements(
      $this->container->get(LibrariesDirectoryFileFinder::class),
    );
  }

  /**
   * The library is absent in the test environment, so the CDN is reported.
   */
  public function testReportsCdnWhenLibraryNotInstalledLocally(): void {
    $requirements = $this->requirements->runtime();

    $this->assertArrayHasKey('tagify_library', $requirements);
    $this->assertSame('Loaded from CDN', (string) $requirements['tagify_library']['value']);
    $this->assertSame(RequirementSeverity::Info, $requirements['tagify_library']['severity']);
    $this->assertArrayHasKey('description', $requirements['tagify_library']);
  }

  /**
   * The runtime requirements hook is discovered and registered for the module.
   */
  public function testRequirementIsRegistered(): void {
    $requirements = $this->container->get('module_handler')
      ->invoke('tagify', 'runtime_requirements', [[]]);

    $this->assertArrayHasKey('tagify_library', $requirements);
  }

}
