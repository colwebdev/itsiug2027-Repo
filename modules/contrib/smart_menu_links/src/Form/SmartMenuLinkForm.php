<?php

declare(strict_types=1);

namespace Drupal\smart_menu_links\Form;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuParentFormSelectorInterface;
use Drupal\smart_menu_links\Entity\SmartMenuLink;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Smart menu links form.
 */
final class SmartMenuLinkForm extends EntityForm implements ContainerInjectionInterface {

  /**
   * The parent form selector service.
   *
   * @var \Drupal\Core\Menu\MenuParentFormSelectorInterface
   */
  protected $menuParentSelector;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The moderation information manager.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInformation;

  /**
   * Constructs a new \Drupal\Core\Menu\Form\MenuLinkDefaultForm.
   *
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_parent_selector
   *   The menu link manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The EntityTypeManager utility class.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entityTypeBundleInfo utility class.
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_information
   *   The ModerationInformation utility class.
   */
  public function __construct(
    MenuParentFormSelectorInterface $menu_parent_selector,
    EntityTypeManagerInterface $entity_type_manager,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    ModerationInformationInterface $moderation_information,
  ) {
    $this->menuParentSelector = $menu_parent_selector;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->moderationInformation = $moderation_information;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('menu.parent_form_selector'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('content_moderation.moderation_information'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {

    $form = parent::form($form, $form_state);

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => [SmartMenuLink::class, 'load'],
      ],
      '#disabled' => !$this->entity->isNew(),
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $this->entity->status(),
    ];

    $form['link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link text'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->get('link_text'),
      '#required' => TRUE,
    ];

    $form['link_suffix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link suffix'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->get('link_suffix'),
      '#required' => TRUE,
    ];

    $form['expanded'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show as expanded'),
      '#description' => $this->t('If selected and this menu link has children, the menu will always appear expanded. This option may be overridden for the entire menu tree when placing a menu block.'),
      '#default_value' => $this->entity->get('expanded'),
    ];

    $menu_parent = $this->entity->get('menu_name') . ':' . $this->entity->get('parent');
    $form['menu_parent'] = $this->menuParentSelector->parentSelectElement($menu_parent);
    $form['menu_parent']['#title'] = $this->t('Parent link');
    $form['menu_parent']['#description'] = $this->t('The maximum depth for a link and all its children is fixed. Some menu links may not be available as parents if selecting them would exceed this limit.');
    $form['menu_parent']['#attributes']['class'][] = 'menu-title-select';

    $form['menu_name'] = [
      '#type' => 'value',
      '#value' => $this->entity->get('menu_name'),
    ];

    $form['parent'] = [
      '#type' => 'value',
      '#value' => $this->entity->get('parent'),
    ];

    $delta = max(abs((int) $this->entity->get('weight')), 50);
    $form['weight'] = [
      '#type' => 'number',
      '#min' => -$delta,
      '#max' => $delta,
      '#default_value' => $this->entity->get('weight', 0),
      '#title' => $this->t('Weight'),
      '#description' => $this->t('Link weight among links in the same menu at the same depth. In the menu, the links with high weight will sink and links with a low weight will be positioned nearer the top.'),
    ];

    $form['weight'] = [
      '#type' => 'number',
      '#min' => -$delta,
      '#max' => $delta,
      '#default_value' => $this->entity->get('weight', 0),
      '#title' => $this->t('Weight'),
      '#description' => $this->t('Link weight among links in the same menu at the same depth. In the menu, the links with high weight will sink and links with a low weight will be positioned nearer the top.'),
    ];

    $form['entity_heading'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('Referenced Entity'),
    ];

    $form['entity_info'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Please provide information about the entity to look for.'),
    ];

    $form['target_entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity Type'),
      '#required' => TRUE,
      '#options' => $this->getEntityTypes(),
      '#default_value' => $this->entity->get('target_entity_type'),
      '#ajax' => [
        'callback' => '::getBundles',
        'disable-refocus' => FALSE, // Or TRUE to prevent re-focusing on the triggering element.
        'event' => 'change',
        'wrapper' => 'edit-target-bundle--wrapper', // This element is updated with this AJAX callback.
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Retrieving bundles...'),
        ],
      ],
    ];

    $form['target_bundle'] = $this->getBundles($form, $form_state, FALSE);

    $form['moderation_states'] = $this->getModerationStates($form, $form_state, FALSE);

    $form['path_heading'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('Path Information'),
    ];

    $form['source_path_segment'] = [
      '#type' => 'number',
      '#min' => 1,
      '#default_value' => $this->entity->get('source_path_segment', 1),
      '#title' => $this->t('Source Path Segment'),
      '#description' => $this->t('In which part of the path should we look for the entity identifier?'),
    ];

    $form['source_id_or_name'] = [
      '#type' => 'select',
      '#title' => $this->t('ID or Name'),
      '#required' => TRUE,
      '#options' => ['id' => 'ID', 'name' => 'Name'],
      '#default_value' => $this->entity->get('source_id_or_name'),
      '#description' => $this->t('Should we treat the value as a numeric ID or an entity name?'),
    ];

    $form['fallback'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fallback value'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->get('fallback'),
      '#description' => $this->t('In case a valid can\'t be found, provide an optional fallback to use. This field supports tokens.'),
    ];

    $form['validate_segment_id'] = [
      '#type' => 'number',
      '#min' => 1,
      '#default_value' => $this->entity->get('validate_segment_id', 1),
      '#title' => $this->t('Validate an expected segment'),
      '#description' => $this->t('If you would like to validate a pattern for a specific segment, provide the number of the segment to check.'),
    ];

    $form['validate_segment_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Validation pattern'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->get('validate_segment_value'),
      '#description' => $this->t('Provide the expected value to validate.'),
      '#states' => [
        'invisible' => [
          ':input[name="validate_segment_id"]' => ['value' => ''],
        ],
      ],
    ];

    // @todo validate that source_path_segment and validate_segment_id are different.
    return $form;
  }

  /**
   * Generate a keyed array of content entity types.
   *
   * @return array
   *   The array of entity types.
   */
  protected function getEntityTypes() : array {
    $entity_definitions = $this->entityTypeManager->getDefinitions();
    $entities = ['' => '- Select -'];
    foreach ($entity_definitions as $definition_id => $definition) {
      if ($definition instanceof ConfigEntityType || !$this->entityTypeManager->getDefinition($definition_id)->getKey('bundle') || !$this->entityTypeManager->hasHandler($definition_id, 'list_builder')) {
        continue;
      }
      $entities[$definition->id()] = $definition->getLabel();
    }
    return $entities;
  }

  /**
   * Generate an input to select a bundle.
   *
   * @return array
   *   The render array for the input.
   */
  public function getBundles(array &$form, FormStateInterface $form_state, $force_name = TRUE) : array {
    $entity_type_id = $form_state->getValue('target_entity_type') ?: $this->entity->get('target_entity_type') ?: '';
    if ($entity_type_id) {
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    }
    else {
      $entity_type = NULL;
    }
    if (!$entity_type_id || !$entity_type || !$entity_type->hasKey('bundle')) {
      return [
        '#type' => 'hidden',
        '#value' => $this->entity->get('target_bundle', ''),
        '#prefix' => '<div id="edit-target-bundle--wrapper">',
        '#suffix' => '</div>',
      ];
    }
    $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
    $bundle_options = ['' => '- Any -'];
    foreach ($bundles as $bundle_id => $bundle_info) {
      $bundle_options[$bundle_id] = $bundle_info['label'];
    }
    $target_bundles = [
      '#type' => 'select',
      '#title' => $entity_type->getBundleLabel() ?: $this->t('Bundle'),
      '#description' => $this->t('Optionally restrict the kind of entity that will be accepted.'),
      '#options' => $bundle_options,
      '#default_value' => $this->entity->get('target_bundle', ''),
      '#prefix' => '<div id="edit-target-bundle--wrapper">',
      '#suffix' => '</div>',
      '#ajax' => [
        'callback' => '::getModerationStates',
        'disable-refocus' => FALSE, // Or TRUE to prevent re-focusing on the triggering element.
        'event' => 'change',
        'wrapper' => 'edit-moderation-states--wrapper', // This element is updated with this AJAX callback.
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Retrieving states...'),
        ],
      ],
    ];
    if ($force_name) {
      $target_bundles['#attributes'] = ['name' => 'target_bundle'];
    }
    return $target_bundles;
  }

  /**
   * Generate an input to select moderation states.
   *
   * @return array
   *   The render array for the input.
   */
  public function getModerationStates(array &$form, FormStateInterface $form_state, $force_name = TRUE) : array {
    $entity_type_id = $form_state->getValue('target_entity_type') ?: $this->entity->get('target_entity_type') ?: '';
    $bundle = $form_state->getValue('target_bundle') ?: $this->entity->get('target_bundle') ?: '';
    $moderation_states = [
      '#type' => 'checkboxes',
      '#options' => [],
      '#prefix' => '<div id="edit-moderation-states--wrapper">',
      '#suffix' => '</div>',
    ];
    if (!$entity_type_id || !$bundle) {
      return $moderation_states;
    }
    $workflow = $this->moderationInformation->getWorkflowForEntityTypeAndBundle($entity_type_id, $bundle);
    if (!$workflow) {
      return $moderation_states;
    }
    $state_options = [];
    $type_settings = $workflow->get('type_settings');

    uasort($type_settings['states'], ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);

    foreach ($type_settings['states'] as $type => $settings) {
      $state_options[$type] = $settings['label'];
    }

    if (!$state_options) {
      return $moderation_states;
    }

    $moderation_states['#title'] = $this->t('Moderation States');
    $moderation_states['#description'] = $this->t('If you only want the menu item displayed during specific moderation states, select them here.');
    $moderation_states['#description_display'] = 'before';
    $moderation_states['#options'] = $state_options;
    $moderation_states['#default_value'] = $this->entity->get('moderation_states', []);
    if ($force_name) {
      $moderation_states['#attributes'] = ['name' => 'moderation_states'];
    }
    return $moderation_states;
  }

  /**
   * {@inheritdoc}
   */
  public function afterBuild(array $element, FormStateInterface $form_state) {
    if ($menu_parent = $form_state->getValue('menu_parent', '')) {
      [$menu_name, $parent] = explode(':', $menu_parent, 2);
      if (!empty($menu_name)) {
        $form_state->setValue('menu_name', $menu_name);
      }
      if (isset($parent)) {
        $form_state->setValue('parent', $parent);
      }
    }
    if ($validate_segment_id = $form_state->getValue('validate_segment_id')) {
      $form_state->setValue('validate_segment_id', (int) $validate_segment_id);
    }
    elseif ($validate_segment_id === '') {
      $form_state->setValue('validate_segment_id', NULL);
    }
    if ($validate_segment_id = $form_state->getValue('validate_segment_id')) {
      $form_state->setValue('validate_segment_id', (int) $validate_segment_id);
    }
    return parent::afterBuild($element, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $message_args = ['%label' => $this->entity->label()];
    $this->messenger()->addStatus(
      match($result) {
        \SAVED_NEW => $this->t('Created new link %label.', $message_args),
        \SAVED_UPDATED => $this->t('Updated link %label.', $message_args),
      }
    );
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

}
