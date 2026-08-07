<?php

namespace Drupal\event_platform_details\Cache\Context;

use Drupal\config_pages\Entity\ConfigPages;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Defines a cache context that determines whether today is a weekend.
 *
 * Cache context ID: 'sessions_open'.
 *
 * @CacheContext(
 *   id = "sessions_open",
 *   label = @Translation("Are Sessions Open?"),
 *   cacheTags = {},
 *   dependencies = {},
 * )
 */
class SessionsOpen implements CacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Are Sessions Open?');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    $result = static::areSessionsOpen() ? 'yes' : 'no';

    return "sessions_open.{$result}";
  }

  /**
   * Returns whether or not sessions are open for the current event.
   *
   * @return bool
   *   Returns TRUE if the current event has sessions open.
   */
  public static function areSessionsOpen() {
    // Retrieve the current event from the config page.
    $configPage = ConfigPages::config('event_details');
    if (!$configPage) {
      return FALSE;
    }
    $current_event_val = $configPage->get('field_current')?->getValue();
    // Extract the value from within a nested array.
    while (is_array($current_event_val)) {
      $current_event_val = array_pop($current_event_val);
    }
    $entity_id = $current_event_val;
    $event = Term::load($entity_id);
    if (!$event) {
      return FALSE;
    }
    $state = $event->get('moderation_state')->value;
    return $state === 'sessions_open';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
