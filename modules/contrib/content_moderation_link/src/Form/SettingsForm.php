<?php

namespace Drupal\content_moderation_link\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Content Moderation Link settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The storage handler of the workflow entity type.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $workflowStorage;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed config manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityStorageInterface $workflow_storage
   *   The storage of workflow config entities.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typedConfigManager, EntityTypeManagerInterface $entity_type_manager, EntityStorageInterface $workflow_storage) {
    parent::__construct($config_factory, $typedConfigManager);
    $this->entityTypeManager = $entity_type_manager;
    $this->workflowStorage = $workflow_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.manager')->getStorage('workflow')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_moderation_link_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['content_moderation_link.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('content_moderation_link.settings');
    $form['allow_multiple'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow multiple IDs'),
      '#default_value' => $config->get('allow_multiple'),
      '#description' => $this->t('Allow moderation links to specify multiple values, comma separated. If this is unchecked, only the first value willbe used.'),
      '#required' => FALSE,
    ];
    $form['skip_errors'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Skip errors'),
      '#default_value' => $config->get('skip_errors'),
      '#description' => $this->t("When processing multiple values, skip ids that can't be successfully resolved. If unchecked the processing will halt on an invalid id."),
      '#required' => FALSE,
    ];
    $form['destination'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Destination Route'),
      '#placeholder' => '<front>',
      '#default_value' => $config->get('destination'),
      '#required' => FALSE,
    ];

    $security_warning = ' ' . $this->t('We strongly recommend keeping this to as narrow a list as possible. Note that not specifying any values will permit any value to be used, which is a potential security risk.');
    $form['entity_types'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity Type'),
      '#description' => $this->t("What kinds of information can be moderated using links?") . $security_warning,
      '#options' => $this->getEntityTypes(),
      '#multiple' => TRUE,
      '#default_value' => $config->get('entity_types'),
      '#required' => FALSE,
    ];

    // @todo validate that entity type(s) and states have a common workflow.
    $form['moderation_states'] = [
      '#type' => 'select',
      '#title' => $this->t('Destination moderation states'),
      '#description' => $this->t("Which states can be the target of moderation links?") . $security_warning,
      '#options' => $this->getWorkflowStates(),
      '#multiple' => TRUE,
      '#default_value' => $config->get('moderation_states'),
      '#required' => FALSE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('content_moderation_link.settings');
    // Values to skip.
    $values_to_skip = [
      'submit',
      'form_build_id',
      'form_token',
      'form_id',
      'op',
    ];

    foreach ($form_state->getValues() as $key => $value) {
      if (!in_array($key, $values_to_skip)) {
        $config->set($key, $value);
      }
    }
    $config->save();
    parent::submitForm($form, $form_state);
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

  protected function getWorkflowStates() : array {
    // Find all workflows which are moderating entity types of the same type the
    // view is displaying.
    $states = [];
    foreach ($this->workflowStorage->loadByProperties(['type' => 'content_moderation']) as $workflow) {
      /** @var \Drupal\content_moderation\Plugin\WorkflowType\ContentModerationInterface $workflow_type */
      $workflow_type = $workflow->getTypePlugin();
      foreach ($workflow_type->getStates() as $state_id => $state) {
        $states[$workflow->label()][implode('-', [$workflow->id(), $state_id])] = $state->label();
      }
    }
    return $states;
  }

}
