<?php

namespace Drupal\event_platform_details\Plugin\Block;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\storage\Entity\Storage;

/**
 * Provides an event platform header cta block.
 *
 * @Block(
 *   id = "event_platform_header_cta",
 *   admin_label = @Translation("Event Platform Header CTA"),
 *   category = @Translation("Event Platform")
 * )
 */
class EventPlatformHeaderCtaBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $options = [
      '' => '- Select -',
    ];
    // Retrieve available CTAs and add as options.
    // @todo Use dependency injection instead.
    $ctas = \Drupal::entityTypeManager()->getStorage('storage')->loadByProperties(['type' => 'cta']);
    foreach ($ctas as $id => $cta) {
      $options[$id] = $cta->get('field_link')->first()->getString();
    }

    // Retrieve available workflow states for events.
    // @todo Use dependency injection instead.
    $moderation_info = \Drupal::service('content_moderation.moderation_information');
    $workflow = $moderation_info->getWorkflowForEntityTypeAndBundle('taxonomy_term', 'event');
    $type_settings = $workflow->get('type_settings');
    uasort($type_settings['states'], ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);

    // Keep an array of processed states to aid in saving to configuration.
    $state_options = [];
    foreach ($type_settings['states'] as $type => $settings) {
      if ($type === 'draft') {
        // Draft state isn't really used, so set a default CTA instead.
        $type = 'default';
        $label = $this->t('Default');
        $description = $this->t('Choose which link to use as a default.');
        $required = TRUE;
      }
      else {
        $label = $settings['label'];
        $description = $this->t('Choose which link to use for the @state moderation state.', ['@state' => $settings['label']]);
        $required = FALSE;
      }
      $state_options[] = $type;
      $form[$type . '_state_link'] = [
        '#type' => 'select',
        '#title' => $this->t('@state link', ['@state' => $label]),
        '#description' => $description,
        '#default_value' => $this->configuration[$type . '_state_link'] ?? '',
        '#options' => $options,
        '#required' => $required,
      ];
    }

    $form['states'] = [
      '#type' => 'value',
      '#value' => $state_options,
    ];

    $current_path = $path = \Drupal::requestStack()->getCurrentRequest()->getPathInfo();

    // Add a link to create a new CTA.
    $form['add_cta'] = [
      '#type' => 'link',
      '#url' => Url::fromRoute('entity.storage.add_form', ['storage_type' => 'cta', 'destination' => $current_path]),
      '#title' => 'Add a CTA',
      '#attributes' => [
        'id' => 'add-cta-dialog',
        'class' => ['use-ajax', 'button', 'button-secondary'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode([
          'width' => 700,
          'minHeight' => 500,
          'title' => $this->t('New CTA'),
        ]),
      ],
    ];

    // Add a link to manage CTAs.
    $form['manage_ctas'] = [
      '#type' => 'link',
      '#url' => Url::fromRoute('view.ctas.page_1', ['destination' => $current_path]),
      '#title' => 'Manage CTAs',
      '#attributes' => [
        'id' => 'add-cta-dialog',
        'class' => ['use-ajax', 'button', 'button-secondary'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode([
          'width' => 700,
          'minHeight' => 500,
          'title' => $this->t('Manage CTAs'),
        ]),
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $states = $form_state->getValue('states');
    if (!is_array($states)) {
      return;
    }
    foreach ($states as $state) {
      $this->configuration[$state . '_state_link'] = $form_state->getValue($state . '_state_link');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $event = \Drupal::service('event_platform_details.event_helper')->determineEvent();
    if (!$event) {
      return $this->getEmptyMarkup();
    }
    $state = $event->get('moderation_state')->value;
    $link = $this->configuration[$state . '_state_link'];
    if (empty($link) && !empty($this->configuration['default_state_link'])) {
      $link = $this->configuration['default_state_link'];
    }

    if ($link) {
      $link_entity = Storage::load($link);
      $cta_url = $link_entity->get('field_link')->uri ?? NULL;
      $cta_title = $link_entity->get('field_link')->title ?? NULL;
    }
    if (!$link || !$cta_url || !$cta_title) {
      // Invalid input so return an empty array.
      return $this->getEmptyMarkup();
    }

    $url = Url::fromUri($cta_url);
    $block = [
      '#theme' => 'event_platform_header_cta_block',
      '#attributes' => [
        'class' => ['site-header__register'],
        'id' => 'header-cta-block',
      ],
      '#url'  => $url,
      '#title'  => $cta_title,
      '#cache' => [
        'tags' => ['storage:' . $link, 'taxonomy_term:' . $event->id()],
        'context' => ['url.path'],
      ],
    ];
    $build['event_platform_header_cta'] = $block;
    return $build;
  }

  /**
   * Returns a render array for an empty value.
   *
   * @return array
   *   The empty markup.
   */
  protected function getEmptyMarkup() {
    return [
      'event_platform_header_cta' => [
        '#cache' => [
          'tags' => ['storage_list:cta'],
        ],
      ],
    ];
  }

}
