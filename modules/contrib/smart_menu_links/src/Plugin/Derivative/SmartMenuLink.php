<?php

namespace Drupal\smart_menu_links\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Derivative class that provides the menu links for the Products.
 */
class SmartMenuLink extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The entity type manager.
   *
   * @var EntityTypeManagerInterface $entityTypeManager.
   */
  protected $entityTypeManager;

  /**
   * Creates a SmartMenuLink instance.
   *
   * @param mixed $base_plugin_id
   *   The plugin id.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct($base_plugin_id, EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $links = [];
    // Get all menu link config entities.
    $entities = $this->entityTypeManager->getStorage('smart_menu_link')->loadMultiple(NULL);
    foreach ($entities as $id => $sm_link) {
      /** @var \Drupal\smart_menu_links\SmartMenuLinkInterface $sm_link */
      $links[$id] = $sm_link->getPluginDefinition() + $base_plugin_definition;
    }

    return $links;
  }

}
