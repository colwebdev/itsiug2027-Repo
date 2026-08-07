<?php

namespace Drupal\dxpr_builder\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dxpr_builder\Service\DxprBuilderJWTDecoder;
use Drupal\dxpr_builder\Service\DxprBuilderLicenseServiceInterface;
use Drupal\dxpr_builder\Service\DxprBuilderKeyService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for DXPR Builder Settings.
 */
class DxprBuilderSettingsForm extends FormBase {

  /**
   * Configuration Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * JWT service to manipulate the DXPR JSON token.
   *
   * @var \Drupal\dxpr_builder\Service\DxprBuilderJWTDecoder
   */
  protected $jwtDecoder;

  /**
   * DXPR license service.
   *
   * @var \Drupal\dxpr_builder\Service\DxprBuilderLicenseServiceInterface
   */
  protected $license;

  /**
   * The DXPR Builder key service.
   *
   * @var \Drupal\dxpr_builder\Service\DxprBuilderKeyService
   */
  protected $keyService;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\dxpr_builder\Service\DxprBuilderJWTDecoder $jwtDecoder
   *   Parsing DXPR JWT token.
   * @param \Drupal\dxpr_builder\Service\DxprBuilderLicenseServiceInterface $license
   *   DXPR license service.
   * @param \Drupal\dxpr_builder\Service\DxprBuilderKeyService $keyService
   *   The DXPR Builder key service.
   */
  final public function __construct(
    ConfigFactoryInterface $configFactory,
    ModuleHandlerInterface $moduleHandler,
    EntityTypeManagerInterface $entityTypeManager,
    DxprBuilderJWTDecoder $jwtDecoder,
    DxprBuilderLicenseServiceInterface $license,
    DxprBuilderKeyService $keyService,
  ) {
    $this->configFactory = $configFactory;
    $this->moduleHandler = $moduleHandler;
    $this->entityTypeManager = $entityTypeManager;
    $this->jwtDecoder = $jwtDecoder;
    $this->license = $license;
    $this->keyService = $keyService;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-return mixed
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('entity_type.manager'),
      $container->get('dxpr_builder.jwt_decoder'),
      $container->get('dxpr_builder.license_service'),
      $container->get('dxpr_builder.key_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dxpr_builder_settings';
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   * @phpstan-return array<string, mixed>
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Force a fresh config read.
    $this->configFactory->reset('dxpr_builder.settings');
    $config = $this->configFactory->getEditable('dxpr_builder.settings');

    $form['license_info'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('DXPR license'),
      '#description' => $this->t('Please enter your product key. Find your product key on our <a href=":uri" target="_blank">Getting Started page</a>', [
        ':uri' => 'https://app.dxpr.com/getting-started',
      ]),
    ];

    $has_key_module = $this->moduleHandler->moduleExists('key');

    // Adjust storage options based on Key module availability.
    $storage_options = [
      'config' => $this->t('Store in configuration'),
    ];

    if ($has_key_module) {
      $storage_options['key'] = $this->t('Use Key module');

      $form['license_info']['api_key_storage'] = [
        '#type' => 'radios',
        '#title' => $this->t('API Key Storage Method'),
        '#options' => $storage_options,
        '#description' => $this->t('Choose how to store your DXPR Builder API key.'),
        '#default_value' => $config->get('api_key_storage') ?: 'config',
      ];

      $key_options = $this->getKeyOptions();

      $form['license_info']['key_provider'] = [
        '#type' => 'select',
        '#title' => $this->t('Select Key'),
        '#description' => $this->t('Select the key that contains your DXPR Builder API credentials. <a href="@url">Manage keys</a>', [
          '@url' => '/admin/config/system/keys',
        ]),
        '#options' => $key_options,
        '#default_value' => $config->get('key_provider'),
        '#states' => [
          'visible' => [
            ':input[name="api_key_storage"]' => ['value' => 'key'],
          ],
          'required' => [
            ':input[name="api_key_storage"]' => ['value' => 'key'],
          ],
        ],
      ];
    }
    else {
      // If key module is not available, force config storage.
      $form['license_info']['api_key_storage'] = [
        '#type' => 'value',
        '#value' => 'config',
      ];
    }

    $form['license_info']['json_web_token'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Product key'),
      '#default_value' => $config->get('json_web_token'),
      '#description' => $this->t('Please enter your product key.'),
      '#required' => !$has_key_module || $config->get('api_key_storage') === 'config',
      '#states' => [
        'visible' => [
          ':input[name="api_key_storage"]' => ['value' => 'config'],
        ],
        'required' => [
          ':input[name="api_key_storage"]' => ['value' => 'config'],
        ],
      ],
    ];

    $form['custom_overrides'] = [
      '#type' => 'details',
      '#title' => $this->t('DXPR Builder overrides'),
      '#description' => $this->t('Customize DXPR Builder  behaviors.'),
    ];

    $form['custom_overrides']['offset_selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Smooth-scroll offset selector'),
      '#description' => $this->t('When using a #hash to link to content inside a DXPR Builder container, the user will smooth-scroll to this content. DXPR Builder will also open any tabs, collapsibles, or carousel slides that contain this content. DXPR Builder automatically corrects the amount of page it scrolls to account for the DXPR Theme fixed or sticky header. If you use a different theme you can use this setting to correct the smooth-scroll behavior for your fixed header by adding a CSS selector here.'),
      '#default_value' => $config->get('offset_selector') ?: '.dxpr-theme-header--sticky, .dxpr-theme-header--fixed',
    ];

    $form['ui_customization'] = [
      '#type' => 'details',
      '#title' => $this->t('Text editor overrides'),
      '#description' => $this->t('Extend DXPR Builder text editor style options and text editor font options.'),
    ];

    $form['ui_customization']['cke_stylesset'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Text editor Formatting Styles'),
      '#description' => $this->t('Enter one class on each line in the format: @format. Example: @example.', [
        '@url' => 'https://ckeditor.com/docs/ckeditor4/latest/api/CKEDITOR_config.html#cfg-stylesSet',
        '@format' => '[label]=[element].[class]',
        '@example' => 'Sassy Title=h1.sassy-title',
      ]) . ' ' . $this->t('Uses the <a href="@url">@setting</a> setting internally.', [
        '@setting' => 'stylesSet',
        '@url' => 'http://docs.ckeditor.com/#!/api/CKEDITOR.config-cfg-stylesSet',
      ]
      ),
      '#default_value' => $config->get('cke_stylesset'),
      // '#element_validate' => array('form_validate_stylesset'),
    ];

    $form['bootstrap_details'] = [
      '#type' => 'details',
      '#title' => $this->t('Bootstrap assets'),
    ];

    $form['bootstrap_details']['bootstrap'] = [
      '#type' => 'radios',
      '#title' => $this->t('Include Bootstrap files'),
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Load Bootstrap 3'),
        'bs4' => $this->t('Load Bootstrap 4'),
        'bs5' => $this->t('Load Bootstrap 5'),
      ],
      '#default_value' => $config->get('bootstrap'),
    ];

    $form['media_details'] = [
      '#type' => 'details',
      '#title' => $this->t('Media browser'),
    ];
    $default = ['' => $this->t('None (Use basic file upload widget)')];
    if ($this->moduleHandler->moduleExists('entity_browser')) {
      /** @var array<mixed> $media_browsers */
      // @phpstan-ignore method.alreadyNarrowedType
      $media_browsers = $this->entityTypeManager->getStorage('entity_browser')
        ->getQuery()
        ->accessCheck()
        ->execute();
      $media_browsers = $default + $media_browsers;
    }
    else {
      $media_browsers = $default;
    }

    // If "Media Library" module exists, we allow switch to it as well.
    $has_media_library = $this->moduleHandler->moduleExists('media_library');
    if ($has_media_library) {
      $media_browsers['media_library'] = $this->t('Media Library');
    }

    if ($this->moduleHandler->moduleExists('acquia_dam')) {
      $media_browsers['media_library_acquia_dam'] = $this->t('Media Library (Acquia DAM)');
    }

    $form['media_details']['media_browser'] = [
      '#type' => 'radios',
      '#title' => $this->t('Media browser'),
      '#description' => $this->t('Media Library is the recommended option for managing images and media assets in DXPR Builder. Alternatively, Entity Browser is supported for legacy compatibility.'),
      '#options' => $media_browsers,
      '#default_value' => $config->get('media_browser') ?? ($has_media_library ? 'media_library' : ''),
    ];

    $form['experimental'] = [
      '#type' => 'details',
      '#title' => $this->t('Text format filters'),
    ];

    $form['experimental']['format_filters'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Process Text format filters on Frontend Builder content'),
      '#description' => $this->t("Use with caution. If a field uses DXPR Builder as field formatter any filters that are set on the field's text format will be ignored. This is because when editing on the frontend, you are editing the raw field contents. With this setting enabled the DXPR editor still loads raw fields content, but users that don't have DXPR Builder editing permission will get a filtered field. Some filters will not work at all with DXPR Builder while others should work just fine."),
      '#default_value' => $config->get('format_filters'),
    ];

    // If a local build exists, provide the option to use it instead of the
    // cloud-hosted files.
    $frontend_asset_options = [
      0 => $this->t('Cloud'),
    ];
    if (file_exists(__DIR__ . '/../../dxpr_builder/dxpr_builder.min.js')) {
      $frontend_asset_options[1] = $this->t('Local files (minified)');
    }
    if (file_exists(__DIR__ . '/../../dxpr_builder/dxpr_builder.js')) {
      $frontend_asset_options[2] = $this->t('Local files (unminified)');
    }
    if (count($frontend_asset_options) > 1) {
      $form['editor_assets'] = [
        '#type' => 'details',
        '#title' => $this->t('Editor assets'),
      ];
      $form['editor_assets']['editor_assets_source'] = [
        '#type' => 'radios',
        '#title' => $this->t('Source for Editor assets'),
        '#description' => $this->t("Assets may be loaded from the cloud to provide an up-to-date version (recommended), or can be loaded from a local build."),
        '#options' => $frontend_asset_options,
        '#default_value' => $config->get('editor_assets_source'),
      ];
    }

    $form['actions']['#type'] = 'actions';
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
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $storage_type = $form_state->getValue('api_key_storage');

    // Only validate the json_web_token if using config storage.
    if ($storage_type === 'config') {
      $jwt = $form_state->getValue('json_web_token');
      if (empty($jwt)) {
        $form_state->setErrorByName('json_web_token', $this->t('Product key is required when using configuration storage.'));
        return;
      }

      $jwtPayloadData = $this->jwtDecoder->decodeJwt($jwt);
      if ($jwtPayloadData['sub'] === NULL || $jwtPayloadData['scope'] === NULL) {
        $form_state->setErrorByName(
          'json_web_token',
          $this->t("Your DXPR Builder product key can't be read, please make sure you copy the whole key without any trailing or leading spaces into the form.")
        );
        Cache::invalidateTags(['config:dxpr_builder.settings']);
      }
      elseif ($jwtPayloadData['dxpr_tier'] === NULL) {
        $form_state->setErrorByName(
          'json_web_token',
          $this->t(
            "Your product key (JWT) is outdated and not compatible with DXPR Builder version 2.0.0 and up. Please follow instructions <a href=':uri'>here</a> to get a new product key.",
            [':uri' => 'https://app.dxpr.com/download/all#token']
          )
        );
        Cache::invalidateTags(['config:dxpr_builder.settings']);
      }
    }
    // Validate key selection when using Key module.
    elseif ($storage_type === 'key' && empty($form_state->getValue('key_provider'))) {
      $form_state->setErrorByName('key_provider', $this->t('Please select a key when using the Key module.'));
    }
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $storage_type = $form_state->getValue('api_key_storage');
    $key_value = $form_state->getValue('json_web_token');
    $selected_key_id = $form_state->getValue('key_provider');

    // Pass the selected key ID to the service.
    $this->keyService->setApiKey($key_value, $storage_type, $selected_key_id);

    $config = $this->configFactory->getEditable('dxpr_builder.settings');

    // Save key-related settings first.
    $config
      ->set('api_key_storage', $form_state->getValue('api_key_storage'))
      ->set('key_provider', $form_state->getValue('key_provider'))
      ->save();

    // Save other settings.
    $config
      ->set('bootstrap', $form_state->getValue('bootstrap'))
      ->set('cke_stylesset', $form_state->getValue('cke_stylesset'))
      ->set('editor_assets_source', $form_state->getValue('editor_assets_source'))
      ->set('format_filters', $form_state->getValue('format_filters'))
      ->set('media_browser', $form_state->getValue('media_browser'))
      ->set('offset_selector', $form_state->getValue('offset_selector'))
      ->set('json_web_token', $form_state->getValue('json_web_token'))
      ->save();

    // Move users to new license when changing or setting a new license.
    if ($config->get('json_web_token') != $form_state->getValue('json_web_token')) {
      if ($config->get('json_web_token')) {
        $this->license->syncAllUsersWithCentralStorage(DxprBuilderLicenseServiceInterface::DXPR_USER_REMOVE_OPERATION, $config->get('json_web_token'));
      }
      $this->license->syncAllUsersWithCentralStorage(DxprBuilderLicenseServiceInterface::DXPR_USER_ADD_OPERATION, $form_state->getValue('json_web_token'));
    }

    // Invalidate caches for the library declarations provided by
    // dxpr_builder_library_info_build().
    // There is no cache tag for this part.
    if ($config->get('editor_assets_source') != $form_state->getValue('editor_assets_source') || $config->get('json_web_token') != $form_state->getValue('json_web_token')) {
      drupal_flush_all_caches();
    }
    else {
      Cache::invalidateTags(['config:dxpr_builder.settings']);
    }
  }

  /**
   * Gets the available key options.
   *
   * @return array<string, string>
   *   An array of key options with key ID as array key and label as value.
   */
  protected function getKeyOptions(): array {
    $options = [];
    if ($this->moduleHandler->moduleExists('key')) {
      $keys = $this->entityTypeManager->getStorage('key')->loadMultiple();
      foreach ($keys as $key) {
        $options[$key->id()] = $key->label();
      }
    }
    return $options;
  }

}
