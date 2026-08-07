<?php

namespace Drupal\event_platform_scheduler;

/**
 * Provides reusable methods for the Event Platform Scheduler.
 */
trait SchedulerTrait {

  /**
   * Information about the entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Separator to join / divide workflow and state during storage.
   *
   * @var string
   */
  protected $separator = '||';

  /**
   * Helper function to return a nested array of workflows and related types.
   *
   * @param array $bundles
   *   The content types for which workflows should be found.
   * @param string $entity_type
   *   The entity type whose bundles should have workflows loaded.
   *
   * @return array
   *   The nested array, or an empty array if no workflows apply.
   */
  public function getWorkflowsForTypes(array $bundles, $entity_type = 'node') {
    $workflows_processed = [];
    foreach ($bundles as $type) {
      $query = $this->entityTypeManager->getStorage('workflow')->getQuery();
      $query->condition("type_settings.entity_types.{$entity_type}.*", $type);
      $workflows = $query->execute();
      if ($workflows && is_array($workflows)) {
        // There should only be one workflow per bundle, so use the first one.
        $workflow = array_pop($workflows);
        $workflows_processed[$workflow][] = $type;
      }
    }
    return $workflows_processed;
  }

  /**
   * Retrieve the states for one or more workflows.
   *
   * @param array $workflows
   *   The workflows for which states are needed.
   *
   * @return array
   *   The states for the workflows.
   */
  public function getStatesForWorkflows(array $workflows) {
    $state_options = [];
    // If more than one applicable workflow, append machine name to states.
    if (count($workflows) > 1) {
      $suffix_needed = TRUE;
    }
    else {
      $suffix_needed = FALSE;
    }
    // Iterate through workflows and collect states.
    foreach ($workflows as $workflow => $wf_types) {
      $workflow_obj = $this->entityTypeManager->getStorage('workflow')->load($workflow);
      if (!$workflow_obj) {
        continue;
      }
      $settings = $workflow_obj->get('type_settings');
      $states = $settings['states'];
      if ($states && is_array($states)) {
        uasort($states, [
          'Drupal\Component\Utility\SortArray',
          'sortByWeightElement',
        ]);
        foreach ($states as $state_id => $state) {
          $label = $state['label'];
          if ($suffix_needed) {
            $label .= ' (' . $workflow . ' - ' . implode(', ', $wf_types) . ')';
          }
          $state_options[$workflow . $this->separator . $state_id] = $label;
        }
      }
    }
    return $state_options;
  }

  /**
   * Return all the valid states for a set of bundles.
   *
   * @param array $bundles
   *   The bundles for which to retrieve states.
   * @param string $entity_type
   *   The entity type of the bundles.
   *
   * @return array
   *   The states applicable to the set of bundles.
   */
  public function getStatesForTypes(array $bundles, $entity_type = 'node') {
    $workflows = $this->getWorkflowsForTypes($bundles, $entity_type);
    return $this->getStatesForWorkflows($workflows);
  }

}
