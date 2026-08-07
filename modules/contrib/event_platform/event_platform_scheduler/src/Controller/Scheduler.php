<?php

namespace Drupal\event_platform_scheduler\Controller;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AlertCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\event_platform_scheduler\SchedulerTrait;
use Drupal\smart_date\SmartDateManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides listings of instances (with overrides) for a specified rule.
 */
class Scheduler extends ControllerBase {

  use SchedulerTrait;

  /**
   * An array of the rooms that are defined.
   *
   * @var array
   */
  protected $rooms;

  /**
   * An array of the time slots available.
   *
   * @var array
   */
  protected $timeSlots;

  /**
   * ID of the current event.
   *
   * @var string
   */
  protected $currentEventVal;

  /**
   * Smart Date service manager.
   *
   * @var \Drupal\smart_date\SmartDateManager
   */
  protected $smartDateManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInformation;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    SmartDateManager $smart_date_manager,
    ConfigFactoryInterface $config_factory,
    ModerationInformationInterface $moderation_information,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->smartDateManager = $smart_date_manager;
    $this->configFactory = $config_factory;
    $this->moderationInformation = $moderation_information;
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
      $container->get('smart_date.manager'),
      $container->get('config.factory'),
      $container->get('content_moderation.moderation_information')
    );
  }

  /**
   * Generate the grid used to assign times and rooms to sessions.
   *
   * @return array
   *   Render array of the grid and sessions.
   */
  public function schedulerInterface() {
    $storage = $this->entityTypeManager->getStorage('taxonomy_term');

    $rooms = $storage->loadByProperties([
      'vid' => 'room',
    ]);
    $this->rooms = $rooms;

    if (!$this->currentEventVal) {
      $this->getCurrentEvent();
    }
    $current_event_val = $this->currentEventVal;
    $time_slots = $storage->loadByProperties([
      'vid' => 'time_slot',
      'field_event' => $current_event_val,
    ]);
    $this->timeSlots = $time_slots;

    $output['wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'scheduler--wrapper',
      ],
    ];
    $output['wrapper']['table'] = $this->buildSlotsTable();
    if ($sessions_output = $this->listSessions()) {
      $heading = [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $this->t('Sessions'),
      ];
      $unassign = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('Unassign'),
        '#attributes' => [
          'class' => ['scheduler--unassign', 'hidden'],
        ],
      ];
      $output['wrapper']['sessions'] = [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'scheduler--sessions',
        ],
      ];
      $output['wrapper']['sessions']['list'] = [
        'heading' => $heading,
        'unassign' => $unassign,
        'output' => $sessions_output,
      ];

    }

    return $output;
  }

  /**
   * Use helper functions to generate a table of available slots.
   *
   * @return array
   *   Render array of the table of available slots.
   */
  public function buildSlotsTable() {
    $header = $this->buildHeader();
    $rows = $this->buildRows();
    $build['table'] = [
      '#type' => 'table',
      '#attributes' => [
        'id' => 'scheduler-table',
      ],
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this
        ->t('There are no @label yet. Use the <a href="@time-slots-tool">Time Slots</a> tool to generate your event\'s time slots in bulk.', [
          '@label' => 'time slots',
          '@time-slots-tool' => '/admin/event-details/scheduler/time_slots',
        ]),
    ];
    $build['table']['#attached']['library'][] = 'event_platform_scheduler/scheduler';

    return $build;

  }

  /**
   * Builds the header row for the listing.
   *
   * @return array
   *   A render array structure of header strings.
   */
  private function buildHeader() {
    $row['label'] = $this->t('Time Slots');
    $row['dummy'] = '';
    foreach ($this->rooms as $room) {
      $row['room' . $room->id()] = $room->label();
    }
    return $row;
  }

  /**
   * Generate the rows for the grid based on the rooms and time slots.
   *
   * @return array
   *   Render array of the grid rows.
   */
  private function buildRows() {
    $rows = [];
    $last_row_date = '';
    $date_row_num = NULL;
    foreach ($this->timeSlots as $time_slot) {
      $row = [];
      // Use the Smart Date service to format dates and times.
      if (!is_array($date_values_array = $time_slot->get('field_when')->getValue())) {
        continue;
      }
      $date_values = array_pop($date_values_array);
      $row_date = $this->smartDateManager->formatSmartDate($date_values['value'], $date_values['end_value'], 'date_only', NULL, 'string');
      // Avoid outputting the same date repeatedly.
      if ($row_date == $last_row_date) {
        $rows[$date_row_num]['date']['rowspan'] += 1;
      }
      else {
        $row['date'] = [
          'data' => $row_date,
          'rowspan' => 1,
        ];
        $date_row_num = $time_slot->id();
        $last_row_date = $row_date;
      }
      // Use Smart Date's time only format to provide a concise time column.
      $row['time'] = $this->smartDateManager->formatSmartDate($date_values['value'], $date_values['end_value'], 'time_only', NULL, 'string');
      // Generate a drag target slot for each room in this time slot.
      foreach ($this->rooms as $room) {
        $row[] = [
          'data' => '',
          'class' => 'empty',
          'data-room' => $room->id(),
          'data-timeslot' => $time_slot->id(),
        ];
      }
      $rows[$time_slot->id()] = $row;
    }
    return $rows;
  }

  /**
   * Generate a list of the sessions available.
   *
   * @return array
   *   Render array of the sessions available.
   */
  private function listSessions() {
    // Add the filters key first so they'll be above the sessions.
    $output = [
      'filters' => [],
    ];
    $config = $this->configFactory->get('event_platform_scheduler.settings');
    if (!$this->currentEventVal) {
      $this->getCurrentEvent();
    }
    $current_event_val = $this->currentEventVal;
    $types = $config->get('types');
    $states = $config->get('states');
    $filter_fields = $config->get('filters');
    $filter_field_labels = [];
    $filter_field_values = [];
    $filters = [];
    // If more than one type specified, provide a type filter.
    if ($types && is_array($types) && count($types) > 1) {
      $type_objects = $this->entityTypeManager
        ->getStorage('node_type')
        ->loadMultiple($types);
      $type_labels = [
        '' => $this->t('- Any -'),
      ];
      foreach ($type_objects as $type_id => $type_object) {
        $type_labels[$type_id] = $type_object->label();
      }
      $filters['type'] = [
        '#type' => 'select',
        '#name' => 'type',
        '#title' => 'Content Type',
        '#options' => $type_labels,
      ];
    }

    // If more than one moderation state allowed, provide a filter.
    $state_filter_options = [
      '' => $this->t('- Any -'),
    ];
    if (count($states) > 1) {
      $all_state_options = $this->getStatesForTypes($types);
      foreach ($states as $state) {
        if ($state === '0') {
          continue;
        }
        $state_filter_options[$state] = $all_state_options[$state];
      }
      $filters['state'] = [
        '#type' => 'select',
        '#name' => 'state',
        '#title' => 'Moderation State',
        '#options' => $state_filter_options,
      ];
    }

    $storage = $this->entityTypeManager->getStorage('node');
    if ($filter_fields) {
      $taxonomy_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    }
    // If no states specified, pull all content of the chosen types.
    if (empty($states)) {
      $sessions = $storage->loadByProperties([
        'type' => $types,
        'field_event' => $current_event_val,
      ]);
    }
    else {
      // Create an array to track unmoderated chosen types.
      $types_to_process = $types;
      $sessions = [];
      $chosen_states = [];
      foreach ($states as $state_hash) {
        $parts = explode($this->separator, $state_hash, 2);
        $workflow = $parts[0];
        $state = $parts[1] ?? '';
        if (!$state) {
          continue;
        }
        $chosen_states[$workflow][] = $state;
      }
      $workflows_to_process = $this->getWorkflowsForTypes($types);
      foreach ($workflows_to_process as $workflow => $chosen_types) {
        $types_to_process = array_diff($types_to_process, $chosen_types);
        if (empty($chosen_states[$workflow])) {
          continue;
        }
        $query = $storage->getQuery()
          ->addTag('moderation_state')
          ->accessCheck(TRUE)
          ->condition('type', $chosen_types, 'IN')
          ->condition('field_event', $current_event_val)
          ->addMetaData('states', $chosen_states[$workflow]);
        $wf_sessions = $query->execute();
        if ($wf_sessions) {
          $sessions = array_merge($sessions, $wf_sessions);
        }
      }
      if ($types_to_process) {
        $query = $storage->getQuery()
          ->accessCheck(TRUE)
          ->condition('type', $types_to_process, 'IN')
          ->condition('field_event', $current_event_val);
        $wf_sessions = $query->execute();
        if ($wf_sessions) {
          $sessions = array_merge($sessions, $wf_sessions);
        }
      }
    }
    if ($sessions) {
      foreach ($sessions as $session_id) {
        $session = $storage->load($session_id);
        // @todo show list of presenters if set.
        $user_storage = $this->entityTypeManager->getStorage('user');
        $user = $user_storage->load($session->get('uid')->getString());
        $label = $this->t('@title by @presenters', [
          '@title' => $session->label(),
          '@presenters' => $user->label(),
        ]);
        // Only pass room and timeslot if both are populated.
        $room = $session->get('field_r')->getString();
        $timeslot = $session->get('field_time_slot')->getString();
        if ($room xor $timeslot) {
          $room = '';
          $timeslot = '';
        }
        $output['sessions'][$session->id()] = [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $label,
          '#attributes' => [
            'class' => ['session'],
            'draggable' => 'true',
            'id' => 'node-' . $session->id(),
            'data-nid' => $session->id(),
            'data-room' => $room,
            'data-timeslot' => $timeslot,
            'data-type' => $session->bundle(),
            'data-user' => $session->get('uid')->getString(),
          ],
        ];
        // Generate any appropriate filter values.
        if ($filter_fields && is_array($filter_fields)) {
          foreach ($filter_fields as $field) {
            if (!$session->hasField($field)) {
              continue;
            }
            $value = $session->get($field)->getString();
            if (!$value) {
              continue;
            }
            // Add the data attribute.
            $output['sessions'][$session->id()]['#attributes']['data-' . $field] = $value;
            // If about to add the first value for a field, add an empty value.
            if (!isset($filter_field_values[$field])) {
              $filter_field_values[$field] = [
                '' => $this->t('- Any -'),
              ];
            }
            // Add the value and the taxonomy term name.
            $filter_field_values[$field][$value] = $taxonomy_storage->load($value)->label();
            // Use the session object to get the field label if necessary.
            if (!isset($filter_field_labels[$field])) {
              $filter_field_labels[$field] = $session->$field?->getFieldDefinition()->getLabel();
            }
          }
        }
        // If a moderation state filter is in use, populate the data.
        if (count($state_filter_options) > 1) {
          $workflow = $this->moderationInformation->getWorkflowForEntity($session);
          $output['sessions'][$session->id()]['#attributes']['data-state'] = $workflow->id() . $this->separator . $session->moderation_state->value;
        }
      }
    }
    // Create the filter dropdowns.
    if ($filter_field_labels) {
      foreach ($filter_field_labels as $field => $filter_field_label) {
        $filters[$field] = [
          '#type' => 'select',
          '#name' => $field,
          '#title' => $filter_field_label,
          '#options' => $filter_field_values[$field],
        ];
      }
    }
    // Enclose the filters in a details element.
    if ($filters) {
      $output['filters'] = [
        '#type' => 'details',
        '#id' => 'scheduler_filters',
        '#title' => $this->t('Filters'),
        'fields' => $filters,
      ];
    }
    else {
      unset($output['filters']);
    }

    return $output;
  }

  /**
   * Helper function to populate the current event value.
   */
  protected function getCurrentEvent() {
    $config_pages = $this->entityTypeManager->getStorage('config_pages');
    $event_details = $config_pages->load('event_details');
    $current_event_val = $event_details->get('field_current')?->getValue();
    // Extract the value from within a nested array.
    while (is_array($current_event_val)) {
      $current_event_val = array_pop($current_event_val);
    }
    $this->currentEventVal = $current_event_val;
    // $current_event = Term::load($current_event_val);
  }

  /**
   * AJAX endpoint to assign a room and time slot to a session.
   *
   * @param object $node
   *   Upcasted node object.
   * @param int $rid
   *   Term id of the room to assign.
   * @param int $tid
   *   Term id of the time slot to assign.
   *
   * @return object
   *   AJAX response.
   */
  public function assign($node, $rid, $tid) {
    // Do stuff.
    $node->set('field_r', $rid);
    $node->set('field_time_slot', $tid);
    $node->save();
    $response = new AjaxResponse();
    $text = $this->t('The @type node is updated.', ['@type' => $node->bundle()]);
    $response->addCommand(new AlertCommand($text));
    return $response;
  }

  /**
   * AJAX callback to reset the room and time slots for a session.
   *
   * @param object $node
   *   Upcasted node object.
   *
   * @return object
   *   AJAX response.
   */
  public function unassign($node) {
    // Do stuff.
    $node->set('field_r', NULL);
    $node->set('field_time_slot', NULL);
    $node->save();
    $response = new AjaxResponse();
    return $response;
  }

}
