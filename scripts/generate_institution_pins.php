<?php

declare(strict_types=1);

use Drupal\node\Entity\Node;

/**
 * Generate unique 4-digit registration PINs for institutions.
 *
 * Usage:
 *   ddev drush php:script scripts/generate_institution_pins.php
 *
 * Behavior:
 * - Updates only institutions with an empty field_registration_pin.
 * - Preserves existing PINs.
 * - Ensures newly generated PINs do not clash with existing institution PINs.
 */

$institutionIds = \Drupal::entityQuery('node')
  ->accessCheck(FALSE)
  ->condition('type', 'institution')
  ->execute();

if (empty($institutionIds)) {
  fwrite(STDOUT, "No institutions found.\n");
  exit(0);
}

$usedPins = [];
$institutions = [];

foreach ($institutionIds as $nid) {
  $institution = Node::load((int) $nid);
  if (!$institution) {
    continue;
  }

  if (!$institution->hasField('field_registration_pin')) {
    continue;
  }

  $institutions[] = $institution;

  $existingPin = trim((string) $institution->get('field_registration_pin')->value);
  if ($existingPin !== '') {
    $usedPins[$existingPin] = TRUE;
  }
}

$updated = 0;
$skipped = 0;
$errors = 0;

foreach ($institutions as $institution) {
  $currentPin = trim((string) $institution->get('field_registration_pin')->value);

  if ($currentPin !== '') {
    $skipped++;
    continue;
  }

  $pin = NULL;
  $attempts = 0;

  while ($attempts < 20000) {
    $attempts++;
    $candidate = (string) random_int(1000, 9999);
    if (!isset($usedPins[$candidate])) {
      $pin = $candidate;
      break;
    }
  }

  if ($pin === NULL) {
    fwrite(STDERR, "ERROR: Could not generate unique PIN for institution {$institution->id()} ({$institution->label()})\n");
    $errors++;
    continue;
  }

  try {
    $institution->set('field_registration_pin', $pin);
    $institution->save();
    $usedPins[$pin] = TRUE;
    $updated++;

    fwrite(STDOUT, "Updated institution {$institution->id()} ({$institution->label()}) with PIN {$pin}\n");
  }
  catch (Throwable $e) {
    fwrite(STDERR, "ERROR: Failed updating institution {$institution->id()} ({$institution->label()}): {$e->getMessage()}\n");
    $errors++;
  }
}

fwrite(STDOUT, "\nPIN generation summary\n");
fwrite(STDOUT, "Institutions total: " . count($institutions) . "\n");
fwrite(STDOUT, "Updated (new PIN generated): {$updated}\n");
fwrite(STDOUT, "Skipped (already had PIN): {$skipped}\n");
fwrite(STDOUT, "Errors: {$errors}\n");

if ($errors > 0) {
  exit(2);
}
