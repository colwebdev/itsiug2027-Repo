<?php

namespace Drupal\Tests\eca_vbo\Kernel;

use Drupal\eca\Entity\Eca;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests for ECA VBO action definitions.
 *
 * @group eca_vbo
 */
class EcaVboActionDefinitionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'eca',
    'views_bulk_operations',
    'eca_vbo',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installConfig(static::$modules);
  }

  /**
   * Tests that the configured action definition is available.
   */
  public function testVboActionDefinition(): void {
    // This config does the following:
    // 1. It creates a VBO action and directly allows any user to execute it.
    // 2. The VBO action itself just prints out a message.
    $eca_config_values = [
      'langcode' => 'en',
      'status' => TRUE,
      'id' => 'eca_vbo_print_message',
      'label' => 'ECA VBO print message',
      'modeller' => 'fallback',
      'version' => '1.0.0',
      'events' => [
        'vbo_execute' => [
          'plugin' => 'vbo:execute',
          'label' => 'Print a message via VBO.',
          'configuration' => [
            'operation_name' => 'Print message',
            'view_id' => '',
            'display_id' => '',
          ],
          'successors' => [
            ['id' => 'print_message', 'condition' => ''],
          ],
        ],
        'vbo_access' => [
          'plugin' => 'vbo:custom_access',
          'label' => 'Custom access for printing a message via VBO.',
          'configuration' => [
            'operation_name' => 'Print message',
            'view_id' => '',
            'display_id' => '',
          ],
          'successors' => [
            ['id' => 'allow_access', 'condition' => ''],
          ],
        ],
      ],
      'conditions' => [],
      'gateways' => [],
      'actions' => [
        'print_message' => [
          'plugin' => 'action_message_action',
          'label' => 'Print a message',
          'configuration' => [
            'replace_tokens' => FALSE,
            'message' => 'Hello',
          ],
          'successors' => [],
        ],
        'allow_access' => [
          'plugin' => 'eca_vbo_set_custom_access',
          'label' => 'Allow access',
          'configuration' => [
            'access_granted' => TRUE,
          ],
          'successors' => [],
        ],
      ],
    ];
    $ecaConfig = Eca::create($eca_config_values);
    $ecaConfig->trustData()->save();

    $action_manager = \Drupal::service('plugin.manager.action');

    // Need to clear cache for being able to see the newly added VBO action.
    $action_manager->clearCachedDefinitions();

    $action_definitions = $action_manager->getDefinitions();
    $this->assertTrue(isset($action_definitions['eca_vbo_execute:print_message']));
  }

}
