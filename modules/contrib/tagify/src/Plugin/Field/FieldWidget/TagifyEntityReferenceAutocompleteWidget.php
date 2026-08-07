<?php

declare(strict_types=1);

namespace Drupal\tagify\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\tagify\Enum\MatchOperator;
use Drupal\tagify\Enum\SuggestionsDropdown;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation 'tagify_entity_reference_autocomplete_widget' widget.
 */
#[FieldWidget(
  id: 'tagify_entity_reference_autocomplete_widget',
  label: new TranslatableMarkup('Tagify'),
  description: new TranslatableMarkup('An autocomplete text field with tagify support.'),
  field_types: [
    'entity_reference',
  ],
  multiple_values: TRUE,
)]
class TagifyEntityReferenceAutocompleteWidget extends WidgetBase {

  /**
   * Constructs a TagifyEntityReferenceAutocompleteWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface $selectionManager
   *   The selection plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    protected readonly AccountInterface $currentUser,
    protected readonly SelectionPluginManagerInterface $selectionManager,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected readonly ModuleHandlerInterface $moduleHandler,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('plugin.manager.entity_reference_selection'),
      $container->get('entity_type.manager'),
      $container->get('module_handler')
    );
  }

  /**
   * Set the entity type manager service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  protected function setEntityTypeManager(EntityTypeManagerInterface $entityTypeManager): void {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'match_operator' => MatchOperator::Contains->value,
      'match_limit' => 10,
      'suggestions_dropdown' => SuggestionsDropdown::OnFirstCharacter->value,
      'placeholder' => '',
      'show_entity_id' => 0,
      'show_info_label' => 0,
      'info_label' => '',
      'parent_selection' => 1,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $element['match_operator'] = [
      '#type' => 'radios',
      '#title' => $this->t('Autocomplete matching'),
      '#default_value' => $this->getSetting('match_operator'),
      '#options' => MatchOperator::options(),
      '#description' => $this->t('Select the method used to collect autocomplete suggestions. Note that <em>Contains</em> can cause performance issues on sites with thousands of entities.'),
    ];
    $element['match_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of results'),
      '#default_value' => $this->getSetting('match_limit'),
      '#min' => 0,
      '#description' => $this->t('The number of suggestions that will be listed. Use <em>0</em> to remove the limit.'),
    ];
    $element['suggestions_dropdown'] = [
      '#type' => 'radios',
      '#title' => $this->t('Suggestions dropdown'),
      '#default_value' => $this->getSetting('suggestions_dropdown'),
      '#options' => SuggestionsDropdown::options(),
      '#description' => $this->t('Select the method used to show suggestions dropdown.'),
    ];
    $element['placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => $this->t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];
    $element['show_entity_id'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include entity id'),
      '#default_value' => $this->getSetting('show_entity_id'),
      '#description' => $this->t('Include the entity ID within the tag.'),
    ];
    $element['show_info_label'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include info label'),
      '#default_value' => $this->getSetting('show_info_label'),
      '#description' => $this->t('Show an extra tag with information next to the entity label.'),
    ];
    $element['parent_selection'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Parent selection'),
      '#default_value' => $this->getSetting('parent_selection'),
      '#description' => $this->t('Allow parent selection from hierarchical entities.'),
    ];
    $element['info_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Info label content'),
      '#default_value' => $this->getSetting('info_label'),
      '#description' => $this->t('The extra information which will be shown within the tag. You can use tokens to make this dynamic.'),
      '#states' => [
        'visible' => [
          sprintf(':input[name="fields[%s][settings_edit_form][settings][show_info_label]"]', $this->fieldDefinition->getName()) => ['checked' => TRUE],
        ],
      ],
    ];

    if ($this->moduleHandler->moduleExists('token')) {
      $token_type = $this->fieldDefinition->getFieldStorageDefinition()->getSetting('target_type');
      // Convert 'taxonomy_term' target type into 'term' in order to make it
      // work as a token type.
      if ($token_type === 'taxonomy_term') {
        $token_type = 'term';
      }
      $element['info_label_tokens'] = [
        '#type' => 'item',
        '#theme' => 'token_tree_link',
        '#token_types' => [$token_type],
        '#show_restricted' => TRUE,
        '#global_types' => FALSE,
        '#recursion_limit' => 3,
        '#states' => [
          'visible' => [
            sprintf(':input[name="fields[%s][settings_edit_form][settings][show_info_label]"]', $this->fieldDefinition->getName()) => ['checked' => TRUE],
          ],
        ],
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary[] = $this->t('Autocomplete matching: @match_operator', ['@match_operator' => MatchOperator::fromSettings($this->getSetting('match_operator'))->label()]);
    $size = $this->getSetting('match_limit') ?: $this->t('unlimited');
    $summary[] = $this->t('Autocomplete suggestion list size: @size', ['@size' => $size]);
    $summary[] = $this->t('Autocomplete suggestions dropdown: @suggestions_dropdown', ['@suggestions_dropdown' => SuggestionsDropdown::fromSettings($this->getSetting('suggestions_dropdown'))->label()]);
    $placeholder = $this->getSetting('placeholder');
    if (!empty($placeholder)) {
      $summary[] = $this->t('Placeholder: @placeholder', ['@placeholder' => $placeholder]);
    }
    else {
      $summary[] = $this->t('No placeholder');
    }
    $show_entity_id = $this->getSetting('show_entity_id');
    $summary[] = $show_entity_id ? $this->t('Include the entity ID within the tag') : $this->t('Remove the entity ID from the tag');
    $parent_selection = $this->getSetting('parent_selection');
    $summary[] = $parent_selection ? $this->t('Parent selection allowed') : $this->t('Parent selection not allowed');
    if ($this->getSetting('show_info_label')) {
      $summary[] = $this->t('Include a label with extra information inside the tag');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    // Append the match operation to the selection settings.
    $selection_settings = $this->getFieldSetting('handler_settings') + [
      'match_operator' => $this->getSetting('match_operator'),
      'match_limit' => $this->getSetting('match_limit'),
      'suggestions_dropdown' => $this->getSetting('suggestions_dropdown'),
      'placeholder' => $this->getSetting('placeholder'),
      'cardinality' => $this->fieldDefinition
        ->getFieldStorageDefinition()
        ->getCardinality(),
      'show_entity_id' => (bool) $this->getSetting('show_entity_id'),
    ];
    if ($this->getSetting('show_info_label')) {
      $selection_settings['info_label'] = $this->getSetting('info_label');
    }
    $target_type = $this->getFieldSetting('target_type');

    if ($target_type === 'taxonomy_term') {
      $selection_settings['parent_selection'] = $this->getSetting('parent_selection');
    }

    // User field definition doesn't have fieldStorage defined.
    $cardinality = $target_type !== 'user'
      ? $items->getFieldDefinition()->getFieldStorageDefinition()->isMultiple()
      : '';
    // Handle field cardinality in the Tagify side.
    $limited = !$cardinality ? 'tagify--limited' : '';
    $autocreate = $this->getSelectionHandlerSetting('auto_create') ? 'tagify--autocreate' : '';
    $tags_identifier = $items->getName();
    // Concat element position to the Tagify identifier.
    if (!empty($element['#field_parents'])) {
      foreach ($element['#field_parents'] as $parent) {
        if (is_numeric($parent)) {
          $tags_identifier .= '_' . $parent;
        }
      }
    }

    $element += [
      '#type' => 'entity_autocomplete_tagify',
      '#target_type' => $target_type,
      '#default_value' => $items->referencedEntities() ?? NULL,
      '#autocreate' => $this->getSelectionHandlerSetting('auto_create'),
      '#selection_handler' => $this->getFieldSetting('handler'),
      '#selection_settings' => $selection_settings,
      '#max_items' => $this->getSetting('match_limit'),
      '#placeholder' => $this->getSetting('placeholder'),
      '#suggestions_dropdown' => $this->getSetting('suggestions_dropdown'),
      '#show_entity_id' => $this->getSetting('show_entity_id'),
      '#parent_selection' => $this->getSetting('parent_selection'),
      '#attributes' => [
        'class' => [$limited, $autocreate, $tags_identifier],
      ],
      '#cardinality' => $items->getFieldDefinition()
        ->getFieldStorageDefinition()
        ->getCardinality(),
      '#identifier' => $tags_identifier,
    ];

    if ($this->getSetting('show_info_label')) {
      $element['#info_label'] = $this->getSetting('info_label');
    }

    // Adding drag and sort message.
    if ($target_type) {
      $entity_definition = $this->entityTypeManager->getDefinition($target_type);
      $message = $this->t("Drag to re-order @entity_types.", ['@entity_types' => $entity_definition->getPluralLabel()]);

      if ($cardinality) {
        $element['#description'] = !empty($element['#description'])
          ? [
            '#theme' => 'item_list',
            '#items' => [
              $element['#description'],
              $message,
            ],
          ]
          : $message;
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues($values, array $form, FormStateInterface $form_state): array {
    if (!is_string($values)) {
      return [];
    }

    $target_type = $this->getFieldSetting('target_type');
    $target_bundles = $this->getSelectionHandlerSetting('target_bundles');
    $selection_settings = $this->getFieldSetting('handler_settings') + [
      'handler' => $this->getFieldSetting('handler'),
      'match_operator' => $this->getSetting('match_operator'),
      'match_limit' => $this->getSetting('match_limit'),
      'suggestions_dropdown' => $this->getSetting('suggestions_dropdown'),
      'target_type' => $target_type,
      'placeholder' => $this->getSetting('placeholder'),
      'show_entity_id' => $this->getSetting('show_entity_id'),
      'parent_selection' => $this->getSetting('parent_selection'),
    ];
    if ($this->getSetting('show_info_label')) {
      $selection_settings['info_label'] = $this->getSetting('info_label');
    }
    $uid = $this->currentUser->id();
    $handler = $this->selectionManager->getInstance($selection_settings);

    $data = json_decode($values, associative: TRUE);
    if (!is_array($data)) {
      return [];
    }

    $items = [];
    $entity_storage = $this->entityTypeManager->getStorage($target_type);
    $entity_type = $entity_storage->getEntityType();

    // Get the label and bundle keys depending on the entity type.
    // E.g. node has `title` and `type`, taxonomy has `name` and `vid`.
    $label_key = $entity_type->getKey('label');
    $bundle_key = $entity_type->getKey('bundle');

    foreach ($data as $current) {
      // If an entity ID is already provided, we can just use that directly.
      if (isset($current['entity_id'])) {
        $items[] = ['target_id' => $current['entity_id']];
        continue;
      }

      // Find if a tag already exists.
      if ($label_key || $bundle_key) {
        $query = $entity_storage->getQuery()
          ->accessCheck(FALSE)
          ->condition($label_key, $current['value']);
        if ($label_key) {
          $query->condition($label_key, $current['value']);
        }
        if ($bundle_key) {
          $query->condition($bundle_key, $target_bundles, 'IN');
        }

        $ids = $query->execute();
        if ($ids !== []) {
          $items[] = ['target_id' => reset($ids)];
          continue;
        }
      }

      // Auto-create the entity if possible.
      $autocreate_bundle = $this->getAutocreateBundle();
      if ($autocreate_bundle) {
        $entity = $handler->createNewEntity($target_type, $autocreate_bundle, $current['value'], $uid);
        $items[] = ['entity' => $entity];
      }
    }

    return $items;
  }

  /**
   * Returns the name of the bundle which are used for auto-created entities.
   *
   * @return string|null
   *   The bundle names. If autocreate is not active, NULL will be returned.
   */
  protected function getAutocreateBundle(): ?string {
    $bundle = NULL;
    if ($this->getSelectionHandlerSetting('auto_create')) {
      $target_bundles = $this->getSelectionHandlerSetting('target_bundles');
      // If there's no target bundle at all, use the target_type. It's the
      // default for bundleless entity types.
      if (empty($target_bundles)) {
        $bundle = $this->getFieldSetting('target_type');
      }
      // If there's only one target bundle, use it.
      elseif (count($target_bundles) == 1) {
        $bundle = reset($target_bundles);
      }
      // If there's more than one target bundle, use the autocreate bundle
      // stored in selection handler settings.
      elseif (!$bundle = $this->getSelectionHandlerSetting('auto_create_bundle')) {
        // If no bundle has been set as auto create target means that there is
        // an inconsistency in entity reference field settings.
        trigger_error(sprintf(
          "The 'Create referenced entities if they don't already exist' option is enabled but a specific destination bundle is not set. You should re-visit and fix the settings of the '%s' (%s) field.",
          $this->fieldDefinition->getLabel(),
          $this->fieldDefinition->getName()
        ), E_USER_WARNING);
      }
    }

    return $bundle;
  }

  /**
   * Returns the value of a setting for the entity reference selection handler.
   *
   * @param string $setting_name
   *   The setting name.
   *
   * @return mixed
   *   The setting value.
   */
  protected function getSelectionHandlerSetting(string $setting_name): mixed {
    $settings = $this->getFieldSetting('handler_settings');
    return $settings[$setting_name] ?? NULL;
  }

}
