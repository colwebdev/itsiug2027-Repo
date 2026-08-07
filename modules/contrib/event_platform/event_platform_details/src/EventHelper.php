<?php

namespace Drupal\event_platform_details;

use Drupal\config_pages\Entity\ConfigPages;
use Drupal\taxonomy\Entity\Term;

/**
 * Helper methods for working with events.
 */
class EventHelper {

  /**
   * Determine the relevant event for the current request path.
   *
   * @return Drupal\taxonomy\Entity\Term
   *   The relevant event term.
   */
  public function determineEvent($use_fallback = TRUE) {
    // @todo Use dependency injection instead.
    // $path = $this->requestStack->getPathInfo();
    $path = \Drupal::requestStack()->getCurrentRequest()->getPathInfo();
    $entity_id = NULL;
    $path_segments = explode('/', $path);
    $entity_type = 'taxonomy_term';
    $storage = NULL;
    // Look for an entity that matches the criteria.
    // Check that the current path meets validation criteria.
    if (!empty($path_segments[1]) && $path_segments[1] === 'events') {
      // Look for a value in the specified path segment.
      $segment = 2;
      if (isset($path_segments[$segment]) && $value = $path_segments[$segment]) {
        // Attempt to find an event with the matching name.
        // @todo Use dependency injection instead.
        $storage = \Drupal::entityTypeManager()->getStorage($entity_type);
        $query = $storage->getQuery()
          ->condition('status', 1)
          ->condition('name', $value)
          ->condition('vid', 'event')
          ->accessCheck(TRUE);
        $results = $query->execute();
        if ($results) {
          $entity_id = array_shift($results);
        }
      }
    }
    // No entity, use the fallback.
    if (!$entity_id) {
      if (!$use_fallback) {
        return NULL;
      }
      $configPage = ConfigPages::config('event_details');
      if (!$configPage) {
        return NULL;
      }
      $current_event_val = $configPage->get('field_current')?->getValue();
      // Extract the value from within a nested array.
      while (is_array($current_event_val)) {
        $current_event_val = array_pop($current_event_val);
      }
      $entity_id = $current_event_val;
    }
    return Term::load($entity_id);
  }

}
