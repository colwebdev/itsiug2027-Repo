<?php

namespace Drupal\dxpr_builder\Form;

use Drupal\Core\Url;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\dxpr_builder\Service\DxprBuilderLicenseServiceInterface;

/**
 * Provides a form for DXPR Builder AI Settings.
 */
class DxprBuilderAiSettingsForm extends FormBase {

  /**
   * Configuration Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleList;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The DXPR license service.
   *
   * @var \Drupal\dxpr_builder\Service\DxprBuilderLicenseServiceInterface
   */
  protected $licenseService;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleList
   *   The module extension list.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\dxpr_builder\Service\DxprBuilderLicenseServiceInterface $licenseService
   *   The DXPR license service.
   */
  final public function __construct(
    ConfigFactoryInterface $configFactory,
    ModuleExtensionList $moduleList,
    EntityTypeManagerInterface $entityTypeManager,
    DxprBuilderLicenseServiceInterface $licenseService,
  ) {
    $this->configFactory = $configFactory;
    $this->moduleList = $moduleList;
    $this->entityTypeManager = $entityTypeManager;
    $this->licenseService = $licenseService;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-return mixed
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('extension.list.module'),
      $container->get('entity_type.manager'),
      $container->get('dxpr_builder.license_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dxpr_builder_ai_settings';
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   * @phpstan-return array<string, mixed>
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->configFactory->getEditable('dxpr_builder.settings');
    $ai_enabled = $config->get('ai_enabled') ?? 1;
    $ai_model = $config->get('ai_model') ?: 'dxpr_kavya_m1';
    $providers_config = $config->get('ai_providers') ?? [];

    $form['#attached']['library'][] = 'core/drupal.tableheader';
    $form['#attached']['library'][] = 'core/drupal.tabledrag';
    $form['#attached']['library'][] = 'dxpr_builder/ai-providers';
    $form['#attached']['library'][] = 'dxpr_builder/ai-settings-form';

    $form['#attributes'] = ['class' => ['dxpr-builder-ai-settings-form']];
    $form['#title'] = $this->t('AI settings (beta)');

    // Usage stats fieldset.
    $form['usage_stats'] = [
      '#type' => 'details',
      '#title' => $this->t('AI Usage Tracking'),
      '#open' => TRUE,
      '#attributes' => ['class' => ['form-item--half-width']],
      '#weight' => -100,
    ];

    // Get real AI usage data.
    $usage_data = $this->licenseService->getAiUsageData();

    if ($usage_data) {
      // Word balance table.
      $form['usage_stats']['balance_table'] = [
        '#type' => 'table',
        '#header' => [],
        '#rows' => [
          [
            ['data' => $this->t('Total Word Balance')],
            ['data' => number_format($usage_data['balance']['word_balance']), 'class' => ['text-align-right']],
          ],
          [
            ['data' => $this->t('@month Word Usage', ['@month' => $usage_data['monthly_usage']['month']])],
            ['data' => number_format($usage_data['monthly_usage']['word_count']), 'class' => ['text-align-right']],
          ],
        ],
        '#attributes' => ['class' => ['table-condensed']],
        '#empty' => $this->t('No data available.'),
      ];

      $form['usage_stats']['last_updated'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Last updated: @time', ['@time' => date('Y-m-d H:i:s')]),
        '#attributes' => ['class' => ['description']],
      ];
    }
    else {
      $form['usage_stats']['no_data'] = [
        '#type' => 'markup',
        '#markup' => $this->t('Unable to fetch AI usage data. Please check your connection and try again.'),
      ];
    }

    // Basic settings fieldset.
    $form['basic_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Basic AI Settings'),
      '#open' => TRUE,
      '#attributes' => ['class' => ['form-item--half-width']],
      '#weight' => -99,
    ];

    $form['basic_settings']['ai_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $ai_enabled,
      '#description' => $this->t('Enable AI writing assistant in the text editor.'),
    ];

    $model_description = $this->t(
      'DXAI Kavya M1: Best performance with OpenAI, Google Gemini, and Anthropic. DXAI Kavya M1 EU: Privacy-focused option using MistralAI.'
    );
    $form['basic_settings']['ai_model'] = [
      '#type' => 'select',
      '#title' => $this->t('Model'),
      '#default_value' => $ai_model,
      '#options' => [
        'dxai_kavya_m1' => $this->t('DXAI Kavya M1'),
        'dxai_kavya_m1_eu' => $this->t('DXAI Kavya M1 EU'),
      ],
      '#description' => $model_description,
    ];

    $vocabulary_options = [];
    $vocabularies = $this->entityTypeManager
      ->getStorage('taxonomy_vocabulary')
      ->loadMultiple();
    foreach ($vocabularies as $vocabulary) {
      $vocabulary_options[$vocabulary->id()] = $vocabulary->label();
    }

    if (empty($vocabulary_options)) {
      $vocabulary_options[''] = $this->t('- No vocabularies available -');
    }

    $saved_tone_vocab = $config->get('tone_of_voice_vocabulary');
    $tone_default_value = '';
    if ($saved_tone_vocab && isset($vocabulary_options[$saved_tone_vocab])) {
      $tone_default_value = $saved_tone_vocab;
    }

    // Build the description with a manage link placeholder.
    $tones_description = $this->t('Select a vocabulary to manage tone of voice choices.');
    $manage_tones_url = Url::fromRoute('entity.taxonomy_vocabulary.overview_form', [
    // Placeholder.
      'taxonomy_vocabulary' => $tone_default_value ?: '_',
    ]);
    $tones_description .= ' ' . $this->t('<a href="@url" target="_blank" class="manage-tones-description-link" data-vocab-target="tone_of_voice_vocabulary">Manage Tones</a>', [
      '@url' => $manage_tones_url->toString(),
    ]);

    $form['tone_of_voice_vocabulary'] = [
      '#type' => 'select',
      '#title' => $this->t('Tones dropdown'),
      '#description' => $tones_description,
      '#options' => $vocabulary_options,
      '#default_value' => $tone_default_value,
      '#required' => TRUE,
      '#empty_option' => $this->t('- Select a vocabulary -'),
      '#empty_value' => '',
      // Add an ID for easier JS targeting of the description wrapper.
      '#id' => 'edit-tone-of-voice-vocabulary',
    ];

    $saved_commands_vocab = $config->get('commands_vocabulary');
    $commands_default_value = '';
    if ($saved_commands_vocab && isset($vocabulary_options[$saved_commands_vocab])) {
      $commands_default_value = $saved_commands_vocab;
    }

    // Build the description with a manage link placeholder.
    $commands_description = $this->t('Select a vocabulary to manage AI commands available for editing text.');
    $manage_commands_url = Url::fromRoute('entity.taxonomy_vocabulary.overview_form', [
    // Placeholder.
      'taxonomy_vocabulary' => $commands_default_value ?: '_',
    ]);
    $commands_description .= ' ' . $this->t('<a href="@url" target="_blank" class="manage-commands-description-link" data-vocab-target="commands_vocabulary">Manage Commands</a>', [
      '@url' => $manage_commands_url->toString(),
    ]);

    $form['commands_vocabulary'] = [
      '#type' => 'select',
      '#title' => $this->t('Commands dropdown'),
      '#description' => $commands_description,
      '#options' => $vocabulary_options,
      '#default_value' => $commands_default_value,
      '#required' => TRUE,
      '#empty_option' => $this->t('- Select a vocabulary -'),
      '#empty_value' => '',
      // Add an ID for easier JS targeting of the description wrapper.
      '#id' => 'edit-commands-vocabulary',
    ];

    $providers_description_text = $this->t('Prioritize AI services by dragging them up or down. The top provider is used first. Requests automatically fall back to the next enabled provider depending on availability and response times. DXPR will use the best models of selected providers and use our custom research, planning, and reasoning AI on top of these models to ensure optimal AI speed and quality.');
    $providers_visibility_state = [
      'visible' => [
        ':input[name="basic_settings[ai_model]"]' => ['value' => 'dxai_kavya_m1'],
      ],
    ];

    // Add AI Providers Title.
    $form['ai_providers_title'] = [
      '#type' => 'markup',
      '#markup' => '<h2>' . $this->t('AI Service Providers') . '</h2>',
      '#states' => $providers_visibility_state,
    ];

    // Add AI Providers Description.
    $form['ai_providers_description'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $providers_description_text . '</p>',
      '#states' => $providers_visibility_state,
    ];

    $module_path = $this->moduleList->getPath('dxpr_builder');
    $logo_base_path = 'base:/' . $module_path . '/images/ai/';
    $provider_info = [
      'anthropic' => [
        'label' => $this->t('Anthropic'),
        'logo' => $logo_base_path . 'anthropic-logo.svg',
      ],
      'gemini' => [
        'label' => $this->t('Google Gemini'),
        'logo' => $logo_base_path . 'gemini-logo.svg',
      ],
      'mistral' => [
        'label' => $this->t('MistralAI'),
        'logo' => $logo_base_path . 'mistralai-logo.svg',
      ],
      'openai' => [
        'label' => $this->t('OpenAI'),
        'logo' => $logo_base_path . 'openai-logo.svg',
      ],
      'xai' => [
        'label' => $this->t('XAI'),
        'logo' => $logo_base_path . 'xai-logo.svg',
      ],
    ];

    // Define the providers table directly in the form, applying the state.
    $form['ai_providers_table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Name'),
        $this->t('Logo'),
        ['data' => $this->t('Weight'), 'class' => ['tabledrag-hide']],
        ['data' => '', 'class' => ['tabledrag-hide']],
      ],
      '#empty' => $this->t('No providers available.'),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'field-weight',
        ],
        [
          'action' => 'match',
          'relationship' => 'parent',
          'group' => 'provider-region-name',
          'hidden' => TRUE,
          'source' => 'region-name',
        ],
      ],
      '#attributes' => [
        'id' => 'field-display-overview',
        'class' => [
          'field-ui-overview',
          'responsive-enabled',
          'draggable-table',
        ],
      ],
      '#states' => $providers_visibility_state,
    ];

    $regions = [
      'enabled' => [
        'title' => $this->t('Enabled'),
        'message' => $this->t('No provider is enabled.'),
      ],
      'disabled' => [
        'title' => $this->t('Disabled'),
        'message' => $this->t('No provider is disabled.'),
      ],
    ];

    foreach ($regions as $region => $region_info) {
      // Update the target array key for region rows.
      $form['ai_providers_table']['region-' . $region] = [
        '#attributes' => [
          'class' => ['region-title', 'region-' . $region . '-title'],
        ],
        'title' => [
          '#markup' => $region_info['title'],
          '#wrapper_attributes' => [
            'colspan' => 4,
            'class' => ['tabledrag-has-colspan'],
          ],
        ],
      ];
      // Update the target array key for region messages.
      $form['ai_providers_table']['region-' . $region . '-message'] = [
        '#attributes' => [
          'class' => [
            'region-message',
            'region-' . $region . '-message',
            'region-empty',
          ],
        ],
        'message' => [
          '#markup' => $region_info['message'],
          '#wrapper_attributes' => [
            'colspan' => 4,
            'class' => ['tabledrag-has-colspan'],
          ],
        ],
      ];
    }

    $sorted_providers = [];
    $default_weights = [
      'openai' => 0,
      'gemini' => 1,
      'mistral' => 2,
      'anthropic' => 3,
      'xai' => 4,
    ];

    foreach ($provider_info as $provider_id => $provider) {
      $provider_config = $providers_config[$provider_id] ?? [
        'enabled' => TRUE,
        'weight' => $default_weights[$provider_id],
        'region' => 'enabled',
      ];
      if (!isset($providers_config[$provider_id])) {
        $provider_config['region'] = 'enabled';
      }
      $sorted_providers[$provider_id] = [
        'label' => $provider['label'],
        'logo' => Url::fromUri($provider['logo'])->toString(),
        'weight' => $provider_config['weight'],
        'region' => $provider_config['region'],
      ];
    }

    uasort($sorted_providers, fn($a, $b) => $b['weight'] <=> $a['weight']);

    foreach ($sorted_providers as $provider_id => $provider) {
      // Update the target array key for provider rows.
      $form['ai_providers_table'][$provider_id] = $this->buildProviderRow(
        $provider_id,
        $provider,
        $provider['region'],
        $provider['weight']
      );
    }

    // Update the target array key for attached libraries.
    $form['ai_providers_table']['#attached']['library'][] = 'core/drupal.tabledrag';
    $form['ai_providers_table']['#attached']['library'][] = 'core/drupal.tableheader';
    $form['ai_providers_table']['#attached']['library'][] = 'core/drupal.form';

    $beta_markup = '<div class="messages messages--warning">
      <h4>' . $this->t('AI Capabilities (Beta)') . '</h4>
      <p>' . $this->t('AI writing assistant enhances text editing in CKEditor.') . '</p>
      <p>' . $this->t('Coming soon: AI-powered layout creation and landing page building.') . '</p>
      <p class="beta-disclaimer">' . $this->t('Features in testing may have limited availability. EU model performance depends on MistralAI service capacity.') . '</p>
      </div>';
    $form['beta_notice'] = [
      '#type' => 'markup',
      '#markup' => $beta_markup,
    ];

    $form['actions'] = [
      '#type' => 'actions',
      '#weight' => 99,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->configFactory->getEditable('dxpr_builder.settings');
    $values = $form_state->getValues();

    $config
      ->set('ai_enabled', (bool) $values['ai_enabled'])
      ->set('ai_model', $values['ai_model']);

    if (!empty($values['tone_of_voice_vocabulary'])) {
      $config->set('tone_of_voice_vocabulary', $values['tone_of_voice_vocabulary']);
      $config->set('enable_taxonomy_tones', TRUE);
    }

    if (!empty($values['commands_vocabulary'])) {
      $config->set('commands_vocabulary', $values['commands_vocabulary']);
      $config->set('enable_taxonomy_commands', TRUE);
    }

    $providers = [];
    if (isset($values['providers'])) {
      foreach ($values['providers'] as $provider_id => $provider_data) {
        if (!isset($provider_data['region-name'])) {
          continue;
        }
        $region = $provider_data['region-name'] ?: 'enabled';
        $providers[$provider_id] = [
          'enabled' => $region === 'enabled',
          'weight' => (int) $provider_data['weight'],
          'region' => $region,
        ];
      }
      $config->set('ai_providers', $providers);
    }

    $config->save();
    Cache::invalidateTags(['config:dxpr_builder.settings']);
    $this->messenger()->addStatus($this->t('AI settings have been saved.'));
  }

  /**
   * Returns the region to which a provider row is assigned.
   *
   * @param array<string, mixed> $row
   *   The provider row element.
   *
   * @return string
   *   The region name.
   */
  public function getProviderRegion(array $row): string {
    return $row['region-name']['#default_value'];
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $values = $form_state->getValues();
    if (isset($values['providers'])) {
      foreach ($values['providers'] as $provider_id => $provider_data) {
        if (!isset($provider_data['region-name'])) {
          continue;
        }
        if (empty($provider_data['region-name'])) {
          $form_state->setValue(['providers', $provider_id, 'region-name'], 'enabled');
        }
      }
    }
  }

  /**
   * Builds a provider row for the form table.
   *
   * @param string $provider_id
   *   The provider ID.
   * @param array<string, mixed> $provider
   *   The provider configuration.
   * @param string $region
   *   The region for this provider.
   * @param int $weight
   *   The weight for this provider.
   *
   * @return array<string, mixed>
   *   The form row structure.
   */
  protected function buildProviderRow(string $provider_id, array $provider, string $region, int $weight): array {
    $name_markup = '<div class="provider-name-wrapper">' . $provider['label'] .
      '<sup class="primary-provider-badge">' . $this->t('Primary Provider') . '</sup></div>';

    $logo_markup = '<img src="' . $provider['logo'] . '" alt="' . $provider['label'] . '" class="provider-logo-img">';

    $row = [
      '#attributes' => [
        'class' => ['draggable', 'tabledrag-leaf'],
        'data-provider-id' => $provider_id,
        'id' => $provider_id,
      ],
      'name' => [
        '#markup' => $name_markup,
        '#wrapper_attributes' => ['class' => ['provider-name-cell']],
      ],
      'label' => [
        '#type' => 'markup',
        '#markup' => $logo_markup,
        '#wrapper_attributes' => ['class' => ['tabledrag-cell']],
      ],
      'weight' => [
        '#type' => 'textfield',
        '#title' => $this->t('Weight for @provider', ['@provider' => $provider['label']]),
        '#title_display' => 'invisible',
        '#default_value' => $weight,
        '#size' => 3,
        '#attributes' => ['class' => ['field-weight']],
        '#parents' => ['providers', $provider_id, 'weight'],
        '#wrapper_attributes' => ['class' => ['tabledrag-hide']],
      ],
      'region-name-cell' => [
        '#wrapper_attributes' => [
          'class' => ['region-select-cell', 'tabledrag-hide'],
        ],
        'region-name' => [
          '#type' => 'hidden',
          '#default_value' => $region,
          '#attributes' => [
            'class' => ['provider-region-name'],
            'data-drupal-field-parents' => 'providers[' . $provider_id . '][region-name]',
          ],
          '#parents' => ['providers', $provider_id, 'region-name'],
        ],
      ],
    ];

    return $row;
  }

  /**
   * Submit handler for updating tone vocabulary.
   *
   * @param array<string, mixed> $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function updateToneVocabulary(array &$form, FormStateInterface $form_state): void {
    $selected = $form_state->getValue(['tone_of_voice', 'tone_of_voice_vocabulary']);
    if ($selected) {
      try {
        $storage = $this->entityTypeManager->getStorage('taxonomy_vocabulary');
        $vocabulary = $storage->load($selected);
        $name = $vocabulary ? $vocabulary->label() : $selected;
        $this->messenger()->addStatus($this->t('Tone vocabulary updated to: @vocabulary', [
          '@vocabulary' => $name,
        ]));
      }
      catch (\Exception $e) {
        $this->messenger()->addStatus($this->t('Tone vocabulary updated to: @vocabulary', [
          '@vocabulary' => $selected,
        ]));
      }
    }
    $form_state->setRebuild();
  }

  /**
   * Submit handler for updating commands vocabulary.
   *
   * @param array<string, mixed> $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function updateCommandsVocabulary(array &$form, FormStateInterface $form_state): void {
    $selected = $form_state->getValue(['commands', 'commands_vocabulary']);
    if ($selected) {
      try {
        $storage = $this->entityTypeManager->getStorage('taxonomy_vocabulary');
        $vocabulary = $storage->load($selected);
        $name = $vocabulary ? $vocabulary->label() : $selected;
        $this->messenger()->addStatus($this->t('Commands vocabulary updated to: @vocabulary', [
          '@vocabulary' => $name,
        ]));
      }
      catch (\Exception $e) {
        $this->messenger()->addStatus($this->t('Commands vocabulary updated to: @vocabulary', [
          '@vocabulary' => $selected,
        ]));
      }
    }
    $form_state->setRebuild();
  }

}
