<?php

declare(strict_types=1);

namespace Drupal\smart_menu_links\Plugin\Menu;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\config_pages\Entity\ConfigPages;
use Drupal\config_translation\ConfigMapperManagerInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Menu\MenuLinkBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\smart_menu_links\SmartMenuLinkInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Generate a dynamic link based on the current path.
 */
final class SmartMenuLink extends MenuLinkBase implements ContainerFactoryPluginInterface {

  /**
   * Entities IDs to load.
   *
   * It is an array of entity IDs keyed by entity IDs.
   *
   * @var array
   */
  protected static $entityIdsToLoad = [];

  /**
   * The smart menu link entity connected to this plugin instance.
   *
   * @var \Drupal\smart_menu_links\SmartMenuLinkInterface
   */
  protected $entity;

  /**
   * The smart menu link entity connected to this plugin instance.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected ?ContentEntityInterface $related_to;

  /**
   * The entity type manager.
   *
   * @var EntityTypeManagerInterface $entityTypeManager.
   */
  protected $entityTypeManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The config translation mapper manager.
   *
   * Used to provide the translation route in case Config Translation module is
   * installed.
   *
   * @var \Drupal\config_translation\ConfigMapperManagerInterface|null
   */
  protected $mapperManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new SmartMenuLink.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The current request object.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\config_translation\ConfigMapperManagerInterface|null $mapper_manager
   *   The config translation mapper manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    RequestStack $request_stack,
    LanguageManagerInterface $language_manager,
    EntityRepositoryInterface $entity_repository,
    ?ConfigMapperManagerInterface $mapper_manager = NULL,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    if (!empty($this->pluginDefinition['metadata']['entity_id'])) {
      $entity_id = $this->pluginDefinition['metadata']['entity_id'];
      // Builds a list of entity IDs to take advantage of the more efficient
      // EntityStorageInterface::loadMultiple() in getEntity() at render time.
      static::$entityIdsToLoad[$entity_id] = $entity_id;
    }

    $this->entityTypeManager = $entity_type_manager;
    $this->requestStack = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('request_stack'),
      $container->get('language_manager'),
      $container->get('entity.repository'),
      // Provide integration with Config Translation module if it is enabled.
      $container->get('plugin.manager.config_translation.mapper', ContainerInterface::NULL_ON_INVALID_REFERENCE)
    );
  }

  /**
   * Loads the entity associated with this menu link.
   *
   * @return \Drupal\smart_menu_links\SmartMenuLinkInterface
   *   The menu link content entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If the entity ID and UUID are both invalid or missing.
   */
  public function getEntity(): ?SmartMenuLinkInterface {
    if (empty($this->entity)) {
      $entity = NULL;
      $storage = $this->entityTypeManager->getStorage('smart_menu_link');
      if (!empty($this->pluginDefinition['metadata']['entity_id'])) {
        $entity_id = $this->pluginDefinition['metadata']['entity_id'];
        // Make sure the current ID is in the list, since each plugin empties
        // the list after calling loadMultple(). Note that the list may include
        // multiple IDs added earlier in each plugin's constructor.
        static::$entityIdsToLoad[$entity_id] = $entity_id;
        $entities = $storage->loadMultiple(array_values(static::$entityIdsToLoad));
        $entity = $entities[$entity_id] ?? NULL;
        static::$entityIdsToLoad = [];
      }
      if (!$entity) {
        // Fallback to the loading by the ID.
        $entity = $storage->load($this->getDerivativeId());
      }
      if (!$entity) {
        // throw new PluginException(sprintf('Entity not found through the menu link plugin definition and could not fallback on ID %s', $this->getDerivativeId()));
        return NULL;
      }
      // Clone the entity object to avoid tampering with the static cache.
      $this->entity = clone $entity;
      if ($this->entityRepository) {
        $this->entity = $this->entityRepository->getTranslationFromContext($this->entity);
      }
    }
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    // We only need to get the title from the actual entity if it may be a
    // translation based on the current language context. This can only happen
    // if the site is configured to be multilingual.
    if ($this->languageManager?->isMultilingual()) {
      return $this->getEntity()->getTitle();
    }
    return $this->pluginDefinition['title'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    // We only need to get the description from the actual entity if it may be a
    // translation based on the current language context. This can only happen
    // if the site is configured to be multilingual.
    if ($this->languageManager?->isMultilingual()) {
      return $this->getEntity()->getDescription();
    }
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
public function getEditRoute() {
  if (!property_exists($this, 'menuLink') || empty($this->menuLink)) {
    return NULL;
  }

  try {
    return $this->menuLink->toUrl('edit-form');
  }
  catch (\Exception $e) {
    return NULL;
  }
}

public function getDeleteRoute() {
  if (!property_exists($this, 'menuLink') || empty($this->menuLink)) {
    return NULL;
  }

  try {
    return $this->menuLink->toUrl('delete-form');
  }
  catch (\Exception $e) {
    return NULL;
  }
}

  /**
   * {@inheritdoc}
   */
  public function getTranslateRoute() {
    // @todo There should be some way for Config Translation module to alter
    //   this information in on its own.
    if ($this->mapperManager) {
      $entity_type = 'menu_link_config';
      /** @var \Drupal\menu_link_config\MenuLinkConfigMapper $mapper */
      $mapper = $this->mapperManager->createInstance($entity_type);
      $mapper->setEntity($this->getEntity());
      return [
        'route_name' => $mapper->getOverviewRouteName(),
        'route_parameters' => $mapper->getOverviewRouteParameters(),
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getUuid() {
    return $this->getEntity()->uuid();
  }

  /**
   * {@inheritdoc}
   */
  public function updateLink(array $new_definition_values, $persist) {
    // Filter the list of updates to only those that are allowed.
    $overrides = array_intersect_key($new_definition_values, $this->overrideAllowed);
    // Update the definition.
    $this->pluginDefinition = $overrides + $this->getPluginDefinition();
    if ($persist) {
      $entity = $this->getEntity();
      foreach ($overrides as $key => $value) {
        $entity->{$key} = $value;
      }
      $this->entityTypeManager->getStorage('smart_menu_link')->save($entity);
    }
    return $this->pluginDefinition;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = ['url.path'];
    // @todo make a custom cache context, similar url.path.parent, that is based on on the first n segments to retrieve the configured path segment.
    return $contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $tags = ['config:smart_menu_link_list'];
    // @todo Invalidate the specific config entity instead. None of the below work.
    // $tags = ['config:smart_menu_link:' . $sm_link->id()];
    // $tags = ['config:smart_menu_links:' . $sm_link->id()];
    // $tags = ['config:smart_menu_links.' . $sm_link->id()];
    // $tags = ['config:smart_menu_links.smart_menu_link.' . $sm_link->id()];
    // Find the entities and bundles in use.
    $sm_link = $this->getEntity();
    if (!$sm_link) {
      return $tags;
    }
    // $tags[] = 'config:smart_menu_link:' . $sm_link->id();
    $tags[] = 'config:smart_menu_links.smart_menu_link.' . $sm_link->id();
    $entity_type = $sm_link->get('target_entity_type');
    if ($bundle = $sm_link->get('target_bundle')) {
      $tags[] = "{$entity_type}_list:{$bundle}";
    }
    else {
      $tags[] = "{$entity_type}_list";
    }

    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrlObject($title_attribute = TRUE) {
    $sm_link = $this->getEntity();
    if (!$sm_link) {
      return $this->getNoLinkObject();
    }
    $path = $this->requestStack->getPathInfo();
    // If there is no fallback and this is the homepage, no link is possible.
    if ($path === '/' && empty($sm_link->get('fallback'))) {
      return NULL;
    }
    $entity_id = NULL;
    $path_segments = explode('/', $path);
    $entity_type = $sm_link->get('target_entity_type');
    $storage = NULL;
    // Look for an entity that matches the criteria.
    // Check that the current path meets validation criteria.
    if ($this->validatePath($sm_link, $path_segments)) {
      // Look for a value in the specified path segment.
      $segment = (int) $sm_link->get('source_path_segment');
      if (isset($path_segments[$segment]) && $value = $path_segments[$segment]) {
        $id_or_name = $sm_link->get('source_id_or_name');
        // Attempt to load a matching entity.
        if ($id_or_name === 'name') {
          $storage = $this->entityTypeManager->getStorage($entity_type);
          $query = $storage->getQuery()
            ->condition('status', 1)
            ->condition('name', $value)
            ->accessCheck(TRUE);
          if ($bundle = $sm_link->get('target_bundle')) {
            $type = $this->entityTypeManager->getDefinition($entity_type);
            $query->condition($type->getKey('bundle'), $bundle);
          }
          $results = $query->execute();
          if ($results) {
            $entity_id = array_shift($results);
          }
        }
        else {
          $entity_id = (int) $value;
        }
      }
    }
    // No entity, use the fallback.
    if (!$entity_id) {
      if ($fallback = $sm_link->get('fallback')) {
        /* @phpstan-ignore-next-line */
        if (\Drupal::hasService('token')) {
          /* @phpstan-ignore-next-line */
          $token_service = \Drupal::service('token');
          $token_value = $token_service->replace($fallback);
          if (is_numeric($token_value)) {
            $entity_id = (int) $token_value;
          }
        }
      }
    }
    // If no entity no link is possible.
    if (!$entity_id) {
      return $this->getNoLinkObject();
    }
    if (!$storage) {
      $storage = $this->entityTypeManager->getStorage($entity_type);
    }
    $entity = $storage->load($entity_id);
    $this->related_to = $entity;

    // Get the pathalias of the entity and structure into an internal path.
    $url = Url::fromRoute('entity.' . $entity_type . '.canonical', [$entity_type => $entity_id]);
    $alias = $url?->toString();
    if (!$alias) {
      return $this->getNoLinkObject();
    }
    $alias = 'internal:' . $alias . '/' . $sm_link->get('link_suffix');
    $url = Url::fromUri($alias);

    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() : bool {
    $sm_link = $this->getEntity();
    if (!$sm_link) {
      return FALSE;
    }
    $enabled = $sm_link->get('enabled');
    if (empty($this->related_to)) {
      return $enabled;
    }
    $target_states = $sm_link->get('moderation_states');
    if (!is_array($target_states) || !$target_states = $this->cleanArray($target_states)) {
      return $enabled;
    }
    $related = $this->related_to;
    $entity_state = $related->get('moderation_state')->value;
    if (!in_array($entity_state, $target_states)) {
      $enabled = FALSE;
    }

    return $enabled;
  }

  /**
   * {@inheritdoc}
   */
  public function isTranslatable() {
    // @todo Injecting the module handler for a proper moduleExists() check
    //   might be a bit cleaner.
    return (bool) $this->mapperManager;
  }

  protected function getNoLinkObject() : Url {
    return Url::fromUri('route:<nolink>');
  }

  protected function validatePath($sm_link, $path_segments) : bool {
    // Look for validation criteria.
    if (!$sm_link->get('validate_segment_id') || empty($sm_link->get('validate_segment_value'))) {
      // Nothing to validate, so not a failure.
      return TRUE;
    }
    // Compare the specified items.
    $segment = (int) $sm_link->get('validate_segment_id');
    $expected = $sm_link->get('validate_segment_value');
    if ($path_segments[$segment] !== $expected) {
      return FALSE;
    }
    return TRUE;
  }

  protected function cleanArray($array) : array {
    foreach ($array as $id => $value) {
      if (!$value || $value === '0') {
        unset($array[$id]);
      }
    }
    return $array;
  }

}
