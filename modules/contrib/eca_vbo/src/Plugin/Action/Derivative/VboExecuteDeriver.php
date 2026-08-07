<?php

namespace Drupal\eca_vbo\Plugin\Action\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides actions for any existing ECA config reacting upon VBO execution.
 */
final class VboExecuteDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The cache backend for ECA VBO action definitions.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected CacheBackendInterface $cache;

  /**
   * Constructs a new EntityActionDeriverBase object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend for ECA VBO action definitions.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, CacheBackendInterface $cache) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id): VboExecuteDeriver {
    return new VboExecuteDeriver(
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('cache.eca_vbo_memory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition): array {
    $cid = 'eca_vbo_definitions';
    if ($cached = $this->cache->get($cid)) {
      $definitions = $cached->data;
    }
    else {
      $definitions = [];
      $plugin_ids = ['vbo:execute', 'vbo:execute_multiple'];

      $ids = $this->entityTypeManager->getStorage('eca')->getQuery()->accessCheck(FALSE)->execute();
      array_walk($ids, function (&$value) {
        $value = 'eca.eca.' . $value;
      });

      foreach ($this->configFactory->loadMultiple($ids) as $config) {
        /** @var \Drupal\Core\Config\Config[]|\Drupal\Core\Config\ImmutableConfig $config */
        if (!$config->get('status')) {
          continue;
        }
        foreach (($config->get('events') ?? []) as $event) {
          if (!in_array($event['plugin'], $plugin_ids, TRUE)) {
            continue;
          }
          $operation_name = $event['configuration']['operation_name'] ?? '';
          $action_id = strtolower(preg_replace("/[^a-zA-Z0-9]+/", "_", trim($operation_name)));
          if ($action_id !== '' && $action_id !== '_') {
            if (!isset($definitions[$action_id])) {
              $definitions[$action_id] = [
                'label' => str_replace('_', ' ', ucfirst($operation_name)),
                'operation_name' => $operation_name,
              ] + $base_plugin_definition;
            }
            $definitions[$action_id]['eca_config'][] = $config->get('id');
          }
        }
      }

      $this->cache->set($cid, $definitions, Cache::PERMANENT, ['config:eca_list']);
    }

    $this->derivatives = $definitions;
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
