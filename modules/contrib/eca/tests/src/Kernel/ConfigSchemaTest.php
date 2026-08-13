<?php

namespace Drupal\Tests\eca\Kernel;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests ECA configuration schema definitions.
 */
#[Group('eca')]
#[Group('eca_core')]
#[RunTestsInSeparateProcesses]
class ConfigSchemaTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'eca',
    'eca_base',
    'modeler_api',
  ];

  /**
   * Tests the event plugin schema and event plugin validation.
   */
  public function testEventPluginSchema(): void {
    $typed_config_manager = $this->container->get('config.typed');
    $event_plugin_definition = $typed_config_manager->getDefinition('eca.event.plugin');
    $event_definition = $typed_config_manager->getDefinition('eca.eca.*');

    $this->assertArrayNotHasKey('id', $event_plugin_definition['mapping']);
    $this->assertSame([
      'manager' => 'plugin.manager.eca.event',
      'interface' => 'Drupal\\eca\\Plugin\\ECA\\Event\\EventInterface',
    ], $event_definition['mapping']['events']['sequence']['mapping']['plugin']['constraints']['PluginExists']);
  }

}
