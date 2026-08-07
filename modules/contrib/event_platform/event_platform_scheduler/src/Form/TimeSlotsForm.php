<?php

namespace Drupal\event_platform_scheduler\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to generate time slots in bulk.
 *
 * @ingroup event_platform_scheduler
 */
class TimeSlotsForm extends FormBase {

  /**
   * Information about the entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new SchedulerSettingsForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
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
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'event_platform_time_slots';
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('replace')) {
      $this->deleteAllTimeSlots();
    }
    $timezone = date_default_timezone_get();
    $weight = 0;
    // Convert the provided dates and determine the difference.
    $start_date = new DrupalDateTime($form_state->getValue('start_date'));
    $end_date = new DrupalDateTime($form_state->getValue('end_date'));
    $diff = $start_date->diff($end_date);
    // Convert the difference to an integer.
    $diff = (int) $diff->format('%a');

    // Parse the times input.
    $times = $this->parseTimes($form_state->getValue('times'));

    // Populate variables we will use within our loops.
    $current_date = $start_date;
    $day_interval = \DateInterval::createFromDateString('1 day');
    $duration_string = $form_state->getValue('duration');
    $duration_interval = \DateInterval::createFromDateString($duration_string . ' minutes');

    for ($i = 0; $i <= $diff; $i++) {
      if ($i) {
        $current_date->add($day_interval);
      }
      $date_string = $current_date->format('Y-m-d');
      foreach ($times as $time) {
        $assembled_time = $date_string . ' ' . $time . ' ' . $timezone;
        $start_time = new \DateTimeImmutable($assembled_time);
        // @todo Check if time was properly converted.
        $end_time = $start_time;
        $end_time = $end_time->add($duration_interval);

        $new_term = Term::create([
          'vid' => 'time_slot',
          'field_when' => [
            'value' => $start_time->getTimestamp(),
            'end_value' => $end_time->getTimestamp(),
            'duration' => $duration_string,
          ],
          'weight' => $weight++,
        ]);

        $new_term->enforceIsNew();
        $new_term->save();
      }
    }
    $this->messenger()->addMessage($this->t('Time slots have been generated.'));
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $start_date = new DrupalDateTime($form_state->getValue('start_date'));
    $end_date = new DrupalDateTime($form_state->getValue('end_date'));
    if ($start_date > $end_date) {
      $form_state
        ->setErrorByName('time_slots', $this
          ->t('Please choose an end date on or after the start date.'));
    }

    // Check that the provided times can be converted to dates.
    $times = $this->parseTimes($form_state->getValue('times'));
    $date_string = $start_date->format('Y-m-d');
    $timezone = date_default_timezone_get();
    foreach ($times as $index => $time) {
      $assembled_time = $date_string . ' ' . $time . ' ' . $timezone;
      try {
        $start_time = new \DateTimeImmutable($assembled_time);
      }
      catch (\Exception $e) {
        $form_state
          ->setErrorByName('time_slots', $this
            ->t('Unable to parse the time string @time. Please check that it uses one of the supported formats.', ['@time' => $index]));
      }
    }
  }

  /**
   * Defines the settings form for Search elevate entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config_pages = $this->entityTypeManager->getStorage('config_pages');
    $event_details = $config_pages->load('event_details');
    $current_event_val = $event_details->get('field_current')?->getValue();
    // Extract the value from within a nested array.
    while (is_array($current_event_val)) {
      $current_event_val = array_pop($current_event_val);
    }
    $current_event = Term::load($current_event_val);
    $dates = $current_event->get('field_dates')?->first();
    $form['start_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Start Date'),
      '#default_value' => $dates?->get('start_date')->getValue()->format('Y-m-d'),
      '#description' => $this->t('The first day for which time slots will be generated.'),
      '#required' => TRUE,
    ];
    $form['end_date'] = [
      '#type' => 'date',
      '#title' => $this->t('End Date'),
      '#default_value' => $dates?->get('end_date')->getValue()->format('Y-m-d'),
      '#description' => $this->t('The last day for which time slots will be generated.'),
      '#required' => TRUE,
    ];
    $form['times'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Start Times'),
      '#default_value' => "10am\n11am\n12pm\n1pm\n2pm\n3pm",
      '#description' => $this->t('Provide the start times for the time slots to be generated, with each time on its own line. The following formats can all be understood: 1:00pm, 13:00, 13h00'),
      '#required' => TRUE,
    ];
    $form['duration'] = [
      '#type' => 'number',
      '#title' => $this->t('Duration'),
      '#default_value' => '45',
      '#field_suffix' => ' ' . $this->t('minutes'),
      '#description' => $this->t('The length, in minutes, of the time slots that will be generated.'),
      '#required' => TRUE,
    ];
    $form['replace'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Replace existing'),
      '#description' => $this->t('Select this to replace all existing time slots with the generated values.'),
    ];
    $form['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate'),
    ];

    return $form;
  }

  /**
   * Parse the user input into a normalized array of times.
   *
   * @param string $input
   *   The user input to be parsed.
   *
   * @return array
   *   An array of normalized times.
   */
  protected function parseTimes(string $input) {
    $rows = explode("\n", $input);
    $times = [];
    foreach ($rows as $row) {
      $adjust_twelve = FALSE;
      if (stripos($row, 'am')) {
        $adjust_twelve = TRUE;
      }
      // Normalize time formatting.
      $row = str_ireplace(['h', 'am'], [':', ''], trim($row));
      if (empty($row)) {
        continue;
      }
      $time_parts = explode(':', $row);
      $last_index = count($time_parts) - 1;
      $hour = (int) $time_parts[0];
      // Convert afternoon times to 24h format.
      if (stripos($time_parts[$last_index], 'pm')) {
        $time_parts[$last_index] = str_ireplace('pm', '', $time_parts[$last_index]);
        if ($hour != 12) {
          $time_parts[0] = $hour + 12;
        }
      }
      // Convert midnight times.
      elseif ($adjust_twelve && $hour == 12) {
        $hour = '00';
      }
      // Check for an empty string in the minutes.
      if (isset($time_parts[1]) && empty($time_parts[1])) {
        unset($time_parts[1]);
      }
      // Normalize our time_parts array.
      $all_parts = ['12', '00', '00'];
      // $time_parts = array_merge($all_parts, $time_parts);
      $time_parts = $time_parts + $all_parts;
      $times[$row] = implode(':', $time_parts);
    }
    return $times;
  }

  /**
   * Helper function to clear out any existing time slots.
   */
  protected function deleteAllTimeSlots() {
    $storage = $this->entityTypeManager->getStorage('taxonomy_term');

    $time_slots = $storage->loadByProperties([
      'vid' => 'time_slot',
    ]);
    foreach ($time_slots as $time_slot) {
      $time_slot->delete();
    }
  }

}
