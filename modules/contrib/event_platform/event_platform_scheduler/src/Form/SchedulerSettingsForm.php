<?php

namespace Drupal\event_platform_scheduler\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\event_platform_scheduler\SchedulerTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides and processes a configuration form for the Scheduler interface.
 *
 * @ingroup event_platform_scheduler
 */
class SchedulerSettingsForm extends ConfigFormBase {

  use SchedulerTrait;

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'event_platform_scheduler.settings';

  /**
   * Object to access field data.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new SchedulerSettingsForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, ConfigFactoryInterface $config_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Drupal service container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('config.factory'),
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'event_platform_scheduler_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('event_platform_scheduler.settings');
    $config = $this->configFactory->getEditable('event_platform_scheduler.settings');
    $config->set('types', $form_state->getValue('types'));
    $config->set('states', $form_state->getValue('states'));
    $config->set('filters', $form_state->getValue('filters'));
    $config->save();
    $this->messenger()->addMessage($this->t('Configuration was saved.'));
  }

  /**
   * Defines the settings form for Search elevate entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('event_platform_scheduler.settings');
    $types = $this->entityTypeManager
      ->getStorage('node_type')
      ->loadMultiple();

    // Convert the array of type objects into a simple array.
    $type_labels = [];
    foreach ($types as $index => $type) {
      $type_labels[$index] = $type->label();
    }
    $types_chosen = $config->get('types');
    // @todo only list types that have a reference to events.
    $form['types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Content types to schedule'),
      '#default_value' => $types_chosen,
      '#description' => $this->t('Specify content will be available in the scheduler.'),
      '#options' => $type_labels,
    ];
    // Collect all workflows and filters across selected content types.
    if ($types_chosen && is_array($types_chosen)) {
      $state_options = $this->getStatesForTypes($types_chosen);
      if ($state_options) {
        $form['states'] = [
          '#type' => 'checkboxes',
          '#title' => $this->t('Workflow states'),
          '#default_value' => $config->get('states'),
          '#description' => $this->t('Leave blank to use content in any workflow state. Unmoderated content types will show all content.'),
          '#options' => $state_options,
        ];
      }
      $field_options = [];
      // Find all taxonomy reference fields and present them as filter options.
      foreach ($types_chosen as $type) {
        $fields = $this->entityFieldManager->getFieldDefinitions('node', $type);
        $field_options += $this->findTaxonomyFields($fields);
        // Omit the event reference field.
        if (isset($field_options['field_event'])) {
          unset($field_options['field_event']);
        }
      }
      if ($field_options) {
        $form['filters'] = [
          '#type' => 'checkboxes',
          '#title' => $this->t('Filters'),
          '#default_value' => $config->get('filters'),
          '#description' => $this->t('The specified fields will be provided as filters in the scheduler.'),
          '#options' => $field_options,
        ];
      }
    }
    $form['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * Iterate through an array of fields and identify which reference taxonomies.
   *
   * @param array $fields
   *   An array of fields.
   *
   * @return array
   *   An array of taxonomy field labels.
   */
  protected function findTaxonomyFields(array $fields) {
    $taxonomy_fields = [];
    $exclude = [
      'field_r',
      'field_room',
      'field_time_slot',
    ];
    foreach ($fields as $field) {
      $field_storage_definition = $field->getFieldStorageDefinition();
      $field_name = $field_storage_definition->getName();
      // Skip fields used for the scheduler grid.
      if (in_array($field_name, $exclude)) {
        continue;
      }

      // Check if field/property should be skipped.
      if (strpos($field_name, 'field_') !== 0 || $field->getType() !== 'entity_reference') {
        continue;
      }

      // Make sure this is a taxonomy reference field.
      $field_settings = $field->getItemDefinition()->getSettings();
      $is_reference = in_array('Drupal\Core\Field\EntityReferenceFieldItemListInterface', class_implements($field->getClass()));
      if (!$is_reference || $field_settings['target_type'] != 'taxonomy_term') {
        continue;
      }

      $taxonomy_fields[$field_name] = $field->getLabel();
    }
    return $taxonomy_fields;
  }

}
