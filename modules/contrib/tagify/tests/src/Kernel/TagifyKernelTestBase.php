<?php

declare(strict_types=1);

namespace Drupal\Tests\tagify\Kernel;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldTypePluginManager;
use Drupal\KernelTests\KernelTestBase;

/**
 * Base class for Tagify kernel tests.
 */
class TagifyKernelTestBase extends KernelTestBase {
  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'field',
    'user',
    'system',
    'taxonomy',
    // Required by the taxonomy_term "description" base field (text_long).
    'text',
    'filter',
    'tagify',
  ];

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManager
   */
  protected FieldTypePluginManager $fieldTypeManager;

  /**
   * The cache backend interface for discovery cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected CacheBackendInterface $cacheDiscovery;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Install the entity schemas the Tagify tests build fields on. Current
    // core (FieldStorageCreateCheckSubscriber) refuses field storage creation
    // unless the target entity schema is installed first.
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');

    $this->configFactory = $this->container->get('config.factory');
    $this->fieldTypeManager = $this->container->get('plugin.manager.field.field_type');
    $this->cacheDiscovery = $this->container->get('cache.discovery');
  }

}
