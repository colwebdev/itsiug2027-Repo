<?php

/**
 * @file
 * Builds the conference programme content model.
 *
 * Idempotent: safe to run repeatedly. Run with:
 *   ddev drush php:script scripts/setup_programme_content_model.php
 */

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Creates a vocabulary if it does not exist.
 */
function itsiug_programme_setup_vocabulary(string $id, string $label, string $description = ''): void {
  if (!Vocabulary::load($id)) {
    Vocabulary::create([
      'vid' => $id,
      'name' => $label,
      'description' => $description,
    ])->save();
    echo "Created vocabulary: $id\n";
  }
}

/**
 * Creates terms in a vocabulary, preserving the given order as term weights.
 */
function itsiug_programme_setup_terms(string $vid, array $names): void {
  $storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
  $weight = 0;
  foreach ($names as $name) {
    $existing = $storage->loadByProperties(['vid' => $vid, 'name' => $name]);
    if (!$existing) {
      Term::create(['vid' => $vid, 'name' => $name, 'weight' => $weight])->save();
      echo "  + $vid: $name\n";
    }
    $weight++;
  }
}

/**
 * Creates a node type if it does not exist.
 */
function itsiug_programme_setup_node_type(string $id, string $label, string $description, string $title_label = 'Title'): void {
  if (!NodeType::load($id)) {
    NodeType::create([
      'type' => $id,
      'name' => $label,
      'description' => $description,
      'title_label' => $title_label,
      'new_revision' => TRUE,
      'preview_mode' => DRUPAL_OPTIONAL,
      'display_submitted' => FALSE,
    ])->save();
    echo "Created content type: $id\n";
  }
}

/**
 * Creates a field storage and instance if they do not exist.
 *
 * @param array $definition
 *   Keys: entity_type, bundle, field_name, type, label, cardinality,
 *   required, settings (storage), instance_settings, description.
 */
function itsiug_programme_setup_field(array $definition): void {
  $definition += [
    'cardinality' => 1,
    'required' => FALSE,
    'settings' => [],
    'instance_settings' => [],
    'description' => '',
  ];

  $storage = FieldStorageConfig::loadByName($definition['entity_type'], $definition['field_name']);
  if (!$storage) {
    $storage = FieldStorageConfig::create([
      'field_name' => $definition['field_name'],
      'entity_type' => $definition['entity_type'],
      'type' => $definition['type'],
      'cardinality' => $definition['cardinality'],
      'settings' => $definition['settings'],
    ]);
    $storage->save();
    echo "Created field storage: {$definition['field_name']}\n";
  }

  $field = FieldConfig::loadByName($definition['entity_type'], $definition['bundle'], $definition['field_name']);
  if (!$field) {
    FieldConfig::create([
      'field_storage' => $storage,
      'bundle' => $definition['bundle'],
      'label' => $definition['label'],
      'description' => $definition['description'],
      'required' => $definition['required'],
      'settings' => $definition['instance_settings'],
    ])->save();
    echo "  attached to {$definition['bundle']}: {$definition['field_name']}\n";
  }
}

/**
 * Builds entity reference handler settings for taxonomy terms.
 */
function itsiug_programme_term_handler(string $vid): array {
  return [
    'handler' => 'default:taxonomy_term',
    'handler_settings' => [
      'target_bundles' => [$vid => $vid],
      'auto_create' => FALSE,
      'sort' => ['field' => 'weight', 'direction' => 'ASC'],
    ],
  ];
}

// ---------------------------------------------------------------------------
// Vocabularies.
// ---------------------------------------------------------------------------
itsiug_programme_setup_vocabulary('conference_day', 'Conference day', 'One term per day of the conference. Each term carries the calendar date.');
itsiug_programme_setup_vocabulary('session_track', 'Track', 'The audience or system stream a session belongs to.');
itsiug_programme_setup_vocabulary('session_category', 'Session category', 'The kind of session, e.g. Plenary, Lunch, Panel Discussion.');
itsiug_programme_setup_vocabulary('session_room', 'Room', 'Venue rooms and spaces.');

// The date carried by each conference day term.
itsiug_programme_setup_field([
  'entity_type' => 'taxonomy_term',
  'bundle' => 'conference_day',
  'field_name' => 'field_date',
  'type' => 'datetime',
  'label' => 'Date',
  'required' => TRUE,
  'settings' => ['datetime_type' => 'date'],
  'description' => 'The calendar date of this conference day.',
]);

itsiug_programme_setup_terms('session_track', [
  'Plenary / All delegates',
  'Student System',
  'Finance System',
  'HR/Payroll System',
  'Technical Systems',
]);

itsiug_programme_setup_terms('session_category', [
  'Registration',
  'Plenary',
  'UG Rep Meeting',
  'Tea/Coffee Break',
  'Lunch',
  'Panel Discussion',
  'Delegate Session',
  'Evening Function',
]);

itsiug_programme_setup_terms('session_room', [
  'Foyer',
  'Registration Desk',
  'Shebeen | Beerhouse',
  'Kings Ballroom - Plenary',
  'Kings Ballroom - STUD',
  'Warriors Hall 1,2 FIN',
  'Warriors Hall 3 - HR/PAYROLL',
  'Seers Court 1 - TECHNICAL',
]);

// ---------------------------------------------------------------------------
// Speaker content type.
// ---------------------------------------------------------------------------
itsiug_programme_setup_node_type('speaker', 'Speaker', 'A person presenting, chairing or facilitating a conference session.', 'Display name');

itsiug_programme_setup_field([
  'entity_type' => 'node',
  'bundle' => 'speaker',
  'field_name' => 'field_job_title',
  'type' => 'string',
  'label' => 'Job title',
  'settings' => ['max_length' => 255],
]);

itsiug_programme_setup_field([
  'entity_type' => 'node',
  'bundle' => 'speaker',
  'field_name' => 'field_organisation',
  'type' => 'string',
  'label' => 'Organisation',
  'settings' => ['max_length' => 255],
]);

itsiug_programme_setup_field([
  'entity_type' => 'node',
  'bundle' => 'speaker',
  'field_name' => 'field_bio',
  'type' => 'text_long',
  'label' => 'Biography',
]);

itsiug_programme_setup_field([
  'entity_type' => 'node',
  'bundle' => 'speaker',
  'field_name' => 'field_speaker_photo',
  'type' => 'entity_reference',
  'label' => 'Photo',
  'settings' => ['target_type' => 'media'],
  'instance_settings' => [
    'handler' => 'default:media',
    'handler_settings' => [
      'target_bundles' => ['image' => 'image'],
      'auto_create' => FALSE,
    ],
  ],
]);

// ---------------------------------------------------------------------------
// Session content type.
// ---------------------------------------------------------------------------
itsiug_programme_setup_node_type('session', 'Session', 'A single item on the conference programme.', 'Topic');

itsiug_programme_setup_field([
  'entity_type' => 'node',
  'bundle' => 'session',
  'field_name' => 'field_session_number',
  'type' => 'integer',
  'label' => 'Session number',
  'description' => 'Numeric only, e.g. 1 for "Session 01". Leave empty for breaks and functions.',
  'settings' => ['unsigned' => TRUE, 'size' => 'normal'],
]);

itsiug_programme_setup_field([
  'entity_type' => 'node',
  'bundle' => 'session',
  'field_name' => 'field_description',
  'type' => 'text_long',
  'label' => 'Description',
  'description' => 'Abstract or further detail shown on the session page.',
]);

itsiug_programme_setup_field([
  'entity_type' => 'node',
  'bundle' => 'session',
  'field_name' => 'field_day',
  'type' => 'entity_reference',
  'label' => 'Day',
  'required' => TRUE,
  'settings' => ['target_type' => 'taxonomy_term'],
  'instance_settings' => itsiug_programme_term_handler('conference_day'),
]);

itsiug_programme_setup_field([
  'entity_type' => 'node',
  'bundle' => 'session',
  'field_name' => 'field_session_times',
  'type' => 'daterange',
  'label' => 'Time slot',
  'required' => TRUE,
  'settings' => ['datetime_type' => 'datetime'],
  'description' => 'Start and end date/time of the session.',
]);

itsiug_programme_setup_field([
  'entity_type' => 'node',
  'bundle' => 'session',
  'field_name' => 'field_track',
  'type' => 'entity_reference',
  'label' => 'Track',
  'cardinality' => FieldStorageConfig::CARDINALITY_UNLIMITED,
  'settings' => ['target_type' => 'taxonomy_term'],
  'instance_settings' => itsiug_programme_term_handler('session_track'),
]);

itsiug_programme_setup_field([
  'entity_type' => 'node',
  'bundle' => 'session',
  'field_name' => 'field_category',
  'type' => 'entity_reference',
  'label' => 'Category',
  'settings' => ['target_type' => 'taxonomy_term'],
  'instance_settings' => itsiug_programme_term_handler('session_category'),
]);

itsiug_programme_setup_field([
  'entity_type' => 'node',
  'bundle' => 'session',
  'field_name' => 'field_room',
  'type' => 'entity_reference',
  'label' => 'Room',
  'settings' => ['target_type' => 'taxonomy_term'],
  'instance_settings' => itsiug_programme_term_handler('session_room'),
]);

$speaker_handler = [
  'handler' => 'default:node',
  'handler_settings' => [
    'target_bundles' => ['speaker' => 'speaker'],
    'auto_create' => FALSE,
    'sort' => ['field' => 'title', 'direction' => 'ASC'],
  ],
];

foreach ([
  'field_chairs' => 'Chair(s)',
  'field_co_chairs' => 'Co-chair(s)',
  'field_presenters' => 'Presenter(s)',
  'field_facilitators' => 'Facilitator(s)',
  'field_scribes' => 'Scribe(s)',
] as $field_name => $label) {
  itsiug_programme_setup_field([
    'entity_type' => 'node',
    'bundle' => 'session',
    'field_name' => $field_name,
    'type' => 'entity_reference',
    'label' => $label,
    'cardinality' => FieldStorageConfig::CARDINALITY_UNLIMITED,
    'settings' => ['target_type' => 'node'],
    'instance_settings' => $speaker_handler,
  ]);
}

itsiug_programme_setup_field([
  'entity_type' => 'node',
  'bundle' => 'session',
  'field_name' => 'field_presentation',
  'type' => 'entity_reference',
  'label' => 'Presentation',
  'cardinality' => FieldStorageConfig::CARDINALITY_UNLIMITED,
  'description' => 'PDF or PowerPoint files delegates can download.',
  'settings' => ['target_type' => 'media'],
  'instance_settings' => [
    'handler' => 'default:media',
    'handler_settings' => [
      'target_bundles' => ['document' => 'document'],
      'auto_create' => FALSE,
    ],
  ],
]);

// ---------------------------------------------------------------------------
// Form and view displays.
// ---------------------------------------------------------------------------

/**
 * Applies a field order to the default form display.
 */
function itsiug_programme_setup_form_display(string $bundle, array $components): void {
  $display = EntityFormDisplay::load("node.$bundle.default")
    ?: EntityFormDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => $bundle,
      'mode' => 'default',
      'status' => TRUE,
    ]);
  $weight = 0;
  foreach ($components as $field_name => $options) {
    $display->setComponent($field_name, $options + ['weight' => $weight]);
    $weight++;
  }
  $display->save();
  echo "Updated form display: node.$bundle.default\n";
}

/**
 * Applies a field order to the default view display.
 */
function itsiug_programme_setup_view_display(string $bundle, array $components, array $hidden = []): void {
  $display = EntityViewDisplay::load("node.$bundle.default")
    ?: EntityViewDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => $bundle,
      'mode' => 'default',
      'status' => TRUE,
    ]);
  $weight = 0;
  foreach ($components as $field_name => $options) {
    $display->setComponent($field_name, $options + ['weight' => $weight, 'label' => 'inline']);
    $weight++;
  }
  foreach ($hidden as $field_name) {
    $display->removeComponent($field_name);
  }
  $display->save();
  echo "Updated view display: node.$bundle.default\n";
}

itsiug_programme_setup_form_display('speaker', [
  'title' => ['type' => 'string_textfield'],
  'field_job_title' => ['type' => 'string_textfield'],
  'field_organisation' => ['type' => 'string_textfield'],
  'field_speaker_photo' => ['type' => 'entity_reference_autocomplete'],
  'field_bio' => ['type' => 'text_textarea'],
]);

itsiug_programme_setup_view_display('speaker', [
  'field_speaker_photo' => ['type' => 'entity_reference_entity_view', 'label' => 'hidden'],
  'field_job_title' => ['type' => 'string'],
  'field_organisation' => ['type' => 'string'],
  'field_bio' => ['type' => 'text_default', 'label' => 'hidden'],
], ['links']);

itsiug_programme_setup_form_display('session', [
  'field_session_number' => ['type' => 'number'],
  'title' => ['type' => 'string_textfield'],
  'field_day' => ['type' => 'options_select'],
  'field_session_times' => ['type' => 'daterange_default'],
  'field_category' => ['type' => 'options_select'],
  'field_track' => ['type' => 'options_buttons'],
  'field_room' => ['type' => 'options_select'],
  'field_chairs' => ['type' => 'entity_reference_autocomplete_tags'],
  'field_co_chairs' => ['type' => 'entity_reference_autocomplete_tags'],
  'field_presenters' => ['type' => 'entity_reference_autocomplete_tags'],
  'field_facilitators' => ['type' => 'entity_reference_autocomplete_tags'],
  'field_scribes' => ['type' => 'entity_reference_autocomplete_tags'],
  'field_description' => ['type' => 'text_textarea'],
  'field_presentation' => ['type' => 'entity_reference_autocomplete_tags'],
]);

itsiug_programme_setup_view_display('session', [
  'field_session_number' => ['type' => 'number_integer'],
  'field_day' => ['type' => 'entity_reference_label', 'settings' => ['link' => FALSE]],
  'field_session_times' => ['type' => 'daterange_custom', 'settings' => ['timezone_override' => '', 'date_format' => 'H:i', 'separator' => '–', 'from_to' => 'both']],
  'field_category' => ['type' => 'entity_reference_label', 'settings' => ['link' => FALSE]],
  'field_track' => ['type' => 'entity_reference_label', 'settings' => ['link' => FALSE]],
  'field_room' => ['type' => 'entity_reference_label', 'settings' => ['link' => FALSE]],
  'field_chairs' => ['type' => 'entity_reference_label', 'settings' => ['link' => TRUE]],
  'field_co_chairs' => ['type' => 'entity_reference_label', 'settings' => ['link' => TRUE]],
  'field_presenters' => ['type' => 'entity_reference_label', 'settings' => ['link' => TRUE]],
  'field_facilitators' => ['type' => 'entity_reference_label', 'settings' => ['link' => TRUE]],
  'field_scribes' => ['type' => 'entity_reference_label', 'settings' => ['link' => TRUE]],
  'field_description' => ['type' => 'text_default', 'label' => 'hidden'],
  'field_presentation' => ['type' => 'entity_reference_entity_view'],
], ['links']);

echo "\nProgramme content model is in place.\n";
