<?php

namespace Drupal\event_platform_details\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\Url;
use Drupal\config_pages\Entity\ConfigPages;
use Drupal\taxonomy\Entity\Term;

/**
 * Provides an event platform homepage hero block.
 *
 * @Block(
 *   id = "event_platform_home_hero",
 *   admin_label = @Translation("Event Platform Homepage Hero"),
 *   category = @Translation("Event Platform")
 * )
 */
class EventPlatformHomeHeroBlock extends BlockBase implements BlockPluginInterface {
  use RedirectDestinationTrait;

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $details_page = 'event_details';

    // Add link to update info.
    $params = $this->getDestinationArray();
    $configPage = ConfigPages::config($details_page);
    if ($configPage) {
      $url = Url::fromRoute('entity.config_pages.edit_form', ['config_pages' => $details_page], ['query' => $params]);
      $cache_tag = 'config_pages:' . $configPage->id();
    }
    else {
      $url = Url::fromUri('internal:/admin/event-details', ['query' => $params]);
      $cache_tag = 'config_pages_list';
    }
    $build['edit_link'] = [
      '#type' => 'link',
      '#title' => $this->t('Edit Info'),
      '#url' => $url,
      '#options' => [
        'attributes' => ['class' => ['button']],
      ],
      '#access' => $url->access(),
      '#cache' => [
        'tags' => [$cache_tag],
      ],
    ];

    if (!$configPage) {
      $build['edit_link']['#title'] = $this->t('Provide Event Info');
      return $build;
    }
    $current_event_val = $configPage->get('field_current')?->getValue();
    // Extract the value from within a nested array.
    while (is_array($current_event_val)) {
      $current_event_val = array_pop($current_event_val);
    }
    $current_event = Term::load($current_event_val);
    $event_name = $current_event->get('name')->value;
    $date_start = $current_event->get('field_dates')->value;
    $date_end = $current_event->get('field_dates')->end_value ?? NULL;
    $location = $current_event->get('field_location_short')->value ?? NULL;
    $org_name = $configPage->get('field_event_name')->value ?? NULL;
    $description = $configPage->get('field_hompage_description_text')->value ?? NULL;
    $cta_url = $configPage->get('field_homepage_media_cta')->uri ?? NULL;
    $cta_title = $configPage->get('field_homepage_media_cta')->title ?? NULL;

    $url = $cta_url ? Url::fromUri($cta_url) : NULL;
    $event_link = Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $current_event_val]);
    $block = [
      '#theme' => 'event_platform_home_hero_block',
      '#attributes' => [
        'class' => ['header_cta'],
        'id' => 'header-cta-block',
      ],
      '#org_name' => $org_name . ' ' . $event_name,
      '#date_start' => $date_start,
      '#date_end' => $date_end,
      '#location' => $location,
      '#description' => $description,
      '#cta_url' => $url,
      '#cta_title' => $cta_title,
      '#event_details' => $configPage,
      '#event_url' => $event_link,
      '#cache' => [
        'tags' => [$cache_tag],
      ],
    ];
    $build['event_platform_home_hero_cta'] = $block;
    return $build;
  }

}
