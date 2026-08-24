<?php

namespace Drupal\ai_summarize_document\Plugin\AiCKEditor;

use Drupal\ai_ckeditor\AiCKEditorPluginBase;
use Drupal\ai_ckeditor\Attribute\AiCKEditor;
use Drupal\ai_ckeditor\Command\AiRequestCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Smalot\PdfParser\Parser;

/**
 * Plugin to summarize documents.
 */
#[AiCKEditor(
  id: 'ai_summarize_document',
  label: new TranslatableMarkup('Summarize Document'),
  description: new TranslatableMarkup('Summarize a selected document.'),
  module_dependencies: [],
)]
final class SummarizeDocument extends AiCKEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'provider' => NULL,
      'document_bundle' => NULL,
      'tone_vocabulary' => NULL,
      'length_vocabulary' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getGenerateButtonLabel() {
    return $this->t('Summarize');
  }

  /**
   * {@inheritdoc}
   */
  protected function getAiResponseLabel() {
    return $this->t('Suggested summary');
  }

  /**
   * {@inheritdoc}
   */
  protected function needsSelectedText() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $media_bundles = $this->entityTypeManager->getStorage('media_type')->loadMultiple();
    if (empty($media_bundles)) {
      return [
        '#markup' => 'You must add at least one media type before you can configure this plugin.',
      ];
    }

    $document_options = [];
    foreach ($media_bundles as $bundle) {
      $document_options[$bundle->id()] = $bundle->label();
    }

    $form['document_bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose the document bundle'),
      '#options' => $document_options,
      '#description' => $this->t('Select media bundle that represent Documents.'),
      '#default_value' => $this->configuration['document_bundle'],
    ];

    $vocabularies = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->loadMultiple();
    $vocabulary_options = [];
    foreach ($vocabularies as $vocabulary) {
      $vocabulary_options[$vocabulary->id()] = $vocabulary->label();
    }

    $form['tone_vocabulary'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose default vocabulary for tone options'),
      '#options' => $vocabulary_options,
      '#description' => $this->t('Select the vocabulary that contains tone options.'),
      '#default_value' => $this->configuration['tone_vocabulary'],
      '#empty_option' => $this->t('- None -'),
    ];

    $form['length_vocabulary'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose default vocabulary for length options'),
      '#options' => $vocabulary_options,
      '#description' => $this->t('Select the vocabulary that contains length options.'),
      '#default_value' => $this->configuration['length_vocabulary'],
      '#empty_option' => $this->t('- None -'),
    ];

    $options = $this->aiProviderManager->getSimpleProviderModelOptions('chat');
    array_shift($options);
    array_splice($options, 0, 1);
    $form['provider'] = [
      '#type' => 'select',
      '#title' => $this->t('AI provider'),
      '#options' => $options,
      "#empty_option" => $this->t('-- Default from AI module (chat) --'),
      '#default_value' => $this->configuration['provider'] ?? $this->aiProviderManager->getSimpleDefaultProviderOptions('chat'),
      '#description' => $this->t('Select which provider to use for this plugin. See the <a href=":link">Provider overview</a> for details about each provider.', [':link' => '/admin/config/ai/providers']),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['provider'] = $form_state->getValue('provider');
    $this->configuration['document_bundle'] = $form_state->getValue('document_bundle');
    $this->configuration['tone_vocabulary'] = $form_state->getValue('tone_vocabulary');
    $this->configuration['length_vocabulary'] = $form_state->getValue('length_vocabulary');
  }

  /**
   * {@inheritdoc}
   */
  public function buildCkEditorModalForm(array $form, FormStateInterface $form_state, array $settings = []) {
    $form = parent::buildCkEditorModalForm($form, $form_state);

    $form['document'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'media',
      '#title' => t('Document'),
      '#required' => TRUE,
      '#selection_settings' => [
        'target_bundles' => [$this->configuration['document_bundle']],
      ],
    ];

    $form['tone'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose the tone'),
      '#required' => FALSE,
      '#empty_option' => $this->t('- None -'),
      '#description' => $this->t('Selecting one of the options will adjust/reword the body content to be appropriate for the target audience.'),
      '#access' => !empty($this->configuration['tone_vocabulary']),
    ];
    $form['tone']['#options'] = $this->getTermOptions($this->configuration['tone_vocabulary']);

    $form['length'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose the length of the response'),
      '#required' => FALSE,
      '#empty_option' => $this->t('- None -'),
      '#description' => $this->t('Selecting one of the options will limit the length of the response text.'),
      '#access' => !empty($this->configuration['length_vocabulary']),
    ];
    $form['length']['#options'] = $this->getTermOptions($this->configuration['length_vocabulary']);

    return $form;
  }

  /**
   * Generate text callback.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return mixed
   *   The result of the AJAX operation.
   */
  public function ajaxGenerate(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $document = $this->entityTypeManager->getStorage('media')->load($values['plugin_config']['document']);
    if (!$document instanceof MediaInterface) {
      throw new \Exception('Could not load the selected document.');
    }

    $file_id = $document->getSource()->getSourceFieldValue($document);
    $file = $this->entityTypeManager->getStorage('file')->load($file_id);
    if (!$file instanceof FileInterface) {
      throw new \Exception('Could not load the file from the selected document.');
    }

    $parser = new Parser();
    $parsed_document = $parser->parseFile($file->getFileUri());

    $length_term = $this->entityTypeManager->getStorage('taxonomy_term')->load($values['plugin_config']['length']);
    $tone_term = $this->entityTypeManager->getStorage('taxonomy_term')->load($values['plugin_config']['tone']);

    try {
      $prompt = "Write a summary about the content of the following text using the same language as the following text:\r\n {$parsed_document->getText()}";
      if ($tone_term) {
        $prompt .= "\r\n\r\n {$tone_term->description->value}";
      }
      if ($length_term) {
        $prompt .= "\r\n\r\n {$length_term->description->value}";
      }
      $response = new AjaxResponse();
      $values = $form_state->getValues();
      $response->addCommand(new AiRequestCommand($prompt, $values['editor_id'], $this->pluginDefinition['id'], 'ai-ckeditor-response'));
      return $response;
    }
    catch (\Exception $e) {
      $this->logger->error('There was an error in the Summarize Document AI plugin for CKEditor.');
      return $form['plugin_config']['response_text']['#value'] = 'There was an error in the Summarize Document AI plugin for CKEditor.';
    }
  }

  /**
   * Helper function to get all terms as an options array.
   *
   * @param string $vid
   *   The vocabulary ID.
   *
   * @return array
   *   The options array.
   */
  protected function getTermOptions(string $vid): array {
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($vid);
    $options = [];

    foreach ($terms as $term) {
      $options[$term->tid] = $term->name;
    }

    return $options;
  }

}
