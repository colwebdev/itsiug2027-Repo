<?php

/**
 * @file
 * Builds the conference document library content model.
 *
 * Idempotent: safe to run repeatedly. Run with:
 *   ddev drush php:script scripts/setup_documents_content_model.php
 */

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

if (!Vocabulary::load('document_category')) {
  Vocabulary::create([
    'vid' => 'document_category',
    'name' => 'Document category',
    'description' => 'Groups documents on the /documents page.',
  ])->save();
  echo "Created vocabulary: document_category\n";
}

$term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
$weight = 0;
foreach (['AGM', 'UG Rep Meeting', 'Finance'] as $name) {
  if (!$term_storage->loadByProperties(['vid' => 'document_category', 'name' => $name])) {
    Term::create(['vid' => 'document_category', 'name' => $name, 'weight' => $weight])->save();
    echo "  + document_category: $name\n";
  }
  $weight++;
}

if (!NodeType::load('document')) {
  NodeType::create([
    'type' => 'document',
    'name' => 'Document',
    'description' => 'A file delegates can download from the conference document library.',
    'title_label' => 'Title',
    'new_revision' => TRUE,
    'preview_mode' => DRUPAL_OPTIONAL,
    'display_submitted' => FALSE,
  ])->save();
  echo "Created content type: document\n";
}

/**
 * Creates a field storage and instance if they do not exist.
 */
function itsiug_documents_setup_field(array $definition): void {
  $definition += [
    'cardinality' => 1,
    'required' => FALSE,
    'settings' => [],
    'instance_settings' => [],
    'description' => '',
  ];

  $storage = FieldStorageConfig::loadByName('node', $definition['field_name']);
  if (!$storage) {
    $storage = FieldStorageConfig::create([
      'field_name' => $definition['field_name'],
      'entity_type' => 'node',
      'type' => $definition['type'],
      'cardinality' => $definition['cardinality'],
      'settings' => $definition['settings'],
    ]);
    $storage->save();
    echo "Created field storage: {$definition['field_name']}\n";
  }

  if (!FieldConfig::loadByName('node', 'document', $definition['field_name'])) {
    FieldConfig::create([
      'field_storage' => $storage,
      'bundle' => 'document',
      'label' => $definition['label'],
      'description' => $definition['description'],
      'required' => $definition['required'],
      'settings' => $definition['instance_settings'],
    ])->save();
    echo "  attached to document: {$definition['field_name']}\n";
  }
}

itsiug_documents_setup_field([
  'field_name' => 'field_description',
  'type' => 'text_long',
  'label' => 'Description',
  'description' => 'Shown beside the download link on the documents page.',
]);

itsiug_documents_setup_field([
  'field_name' => 'field_category',
  'type' => 'entity_reference',
  'label' => 'Category',
  'required' => TRUE,
  'settings' => ['target_type' => 'taxonomy_term'],
  'instance_settings' => [
    'handler' => 'default:taxonomy_term',
    'handler_settings' => [
      'target_bundles' => ['document_category' => 'document_category'],
      'auto_create' => FALSE,
      'sort' => ['field' => 'weight', 'direction' => 'ASC'],
    ],
  ],
]);

itsiug_documents_setup_field([
  'field_name' => 'field_document_file',
  'type' => 'file',
  'label' => 'Document',
  'required' => TRUE,
  'description' => 'PDF, Word, Excel, PowerPoint or ZIP. Opens in a new tab for delegates.',
  'settings' => [
    'target_type' => 'file',
    'display_field' => FALSE,
    'display_default' => FALSE,
    'uri_scheme' => 'public',
  ],
  'instance_settings' => [
    'file_directory' => 'documents/[date:custom:Y-m]',
    'file_extensions' => 'pdf doc docx xls xlsx ppt pptx zip',
    'max_filesize' => '',
    'description_field' => FALSE,
  ],
]);

$form_display = EntityFormDisplay::load('node.document.default')
  ?: EntityFormDisplay::create([
    'targetEntityType' => 'node',
    'bundle' => 'document',
    'mode' => 'default',
    'status' => TRUE,
  ]);
$weight = 0;
foreach ([
  'title' => ['type' => 'string_textfield'],
  'field_category' => ['type' => 'options_select'],
  'field_description' => ['type' => 'text_textarea'],
  'field_document_file' => ['type' => 'file_generic'],
] as $field_name => $options) {
  $form_display->setComponent($field_name, $options + ['weight' => $weight]);
  $weight++;
}
$form_display->save();
echo "Updated form display: node.document.default\n";

$view_display = EntityViewDisplay::load('node.document.default')
  ?: EntityViewDisplay::create([
    'targetEntityType' => 'node',
    'bundle' => 'document',
    'mode' => 'default',
    'status' => TRUE,
  ]);
$weight = 0;
foreach ([
  'field_category' => ['type' => 'entity_reference_label', 'label' => 'inline', 'settings' => ['link' => FALSE]],
  'field_description' => ['type' => 'text_default', 'label' => 'hidden'],
  'field_document_file' => ['type' => 'file_default', 'label' => 'inline'],
] as $field_name => $options) {
  $view_display->setComponent($field_name, $options + ['weight' => $weight]);
  $weight++;
}
$view_display->removeComponent('links');
$view_display->save();
echo "Updated view display: node.document.default\n";

echo "\nDocument library content model is in place.\n";
