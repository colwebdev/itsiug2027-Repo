<?php

declare(strict_types=1);

use Drupal\node\Entity\Node;

/**
 * Bulk import delegate profiles and conference registrations from CSV.
 *
 * Usage:
 *   ddev drush php:script scripts/import_conference_registrations.php -- scripts/conference_registrations_sample.csv
 *   ddev drush php:script scripts/import_conference_registrations.php -- --apply scripts/conference_registrations_sample.csv
 *   ddev drush php:script scripts/import_conference_registrations.php -- --apply --conference=2 scripts/conference_registrations_sample.csv
 *
 * The script defaults to preview mode. Pass --apply to save changes.
 */

$defaultCsvPath = 'scripts/conference_registrations_sample.csv';
$csvPath = $defaultCsvPath;
$dryRun = TRUE;
$defaultConferenceNid = 2;

$extraArgs = [];
if (isset($extra) && is_array($extra)) {
  $extraArgs = $extra;
}

foreach ($extraArgs as $arg) {
  $arg = trim((string) $arg);

  if ($arg === '--apply') {
    $dryRun = FALSE;
    continue;
  }

  if (str_starts_with($arg, '--conference=')) {
    $defaultConferenceNid = (int) substr($arg, strlen('--conference='));
    continue;
  }

  if ($arg !== '' && !str_starts_with($arg, '--')) {
    $csvPath = $arg;
  }
}

if (!is_file($csvPath)) {
  fwrite(STDERR, "CSV not found: {$csvPath}\n");
  fwrite(STDERR, "Expected path example: {$defaultCsvPath}\n");
  exit(1);
}

if ($dryRun) {
  fwrite(STDOUT, "Running in preview mode (--dry-run). No changes will be saved.\n\n");
}

$allowedDelegateTitles = ['prof', 'dr', 'mr', 'mrs', 'miss', 'ms'];
$allowedValues = [
  'registration_status' => ['pending', 'confirmed', 'cancelled', 'waitlisted'],
  'checkin_status' => ['not_checked_in', 'checked_in'],
  'monday_attendance' => ['not_present', 'present'],
  'tuesday_attendance' => ['not_present', 'present'],
  'wednesday_attendance' => ['not_present', 'present'],
  'certificate_eligibility' => ['not_eligible', 'eligible'],
  'certificate_status' => ['not_generated', 'generated', 'issued'],
  'conference_gift' => ['not_received', 'received'],
];

$requiredHeaders = [
  'delegate_first_name',
  'delegate_last_name',
  'delegate_email',
  'delegate_job_title',
  'delegate_institution_nid_or_title',
  'registration_title',
];

$handle = fopen($csvPath, 'r');
if ($handle === FALSE) {
  fwrite(STDERR, "Unable to open CSV: {$csvPath}\n");
  exit(1);
}

$headerRow = fgetcsv($handle);
if ($headerRow === FALSE) {
  fclose($handle);
  fwrite(STDERR, "CSV is empty: {$csvPath}\n");
  exit(1);
}

$headers = array_map(static fn($header) => trim((string) $header), $headerRow);
$headerIndex = array_flip($headers);
$missing = array_values(array_diff($requiredHeaders, $headers));
if (!empty($missing)) {
  fclose($handle);
  fwrite(STDERR, "CSV is missing required headers: " . implode(', ', $missing) . "\n");
  exit(1);
}

$nodeStorage = \Drupal::entityTypeManager()->getStorage('node');
$termStorage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

$get = static function (array $rowData, string $column): string {
  return isset($rowData[$column]) ? trim((string) $rowData[$column]) : '';
};

$resolveNodeId = static function (string $value, string $bundle) use ($nodeStorage): int {
  $value = trim($value);
  if ($value === '') {
    return 0;
  }

  if (ctype_digit($value)) {
    $node = $nodeStorage->load((int) $value);
    return $node && $node->bundle() === $bundle ? (int) $node->id() : 0;
  }

  $ids = \Drupal::entityQuery('node')
    ->accessCheck(FALSE)
    ->condition('type', $bundle)
    ->condition('title', $value)
    ->range(0, 1)
    ->execute();

  return $ids ? (int) reset($ids) : 0;
};

$resolveTermId = static function (string $value, string $vocabulary) use ($termStorage): int {
  $value = trim($value);
  if ($value === '') {
    return 0;
  }

  if (ctype_digit($value)) {
    $term = $termStorage->load((int) $value);
    return $term && $term->bundle() === $vocabulary ? (int) $term->id() : 0;
  }

  $ids = \Drupal::entityQuery('taxonomy_term')
    ->accessCheck(FALSE)
    ->condition('vid', $vocabulary)
    ->condition('name', $value)
    ->range(0, 1)
    ->execute();

  return $ids ? (int) reset($ids) : 0;
};

$normalizeListValue = static function (string $fieldName, string $value) use ($allowedValues): string {
  $value = trim(strtolower($value));
  if ($value === '') {
    return '';
  }

  if (!isset($allowedValues[$fieldName])) {
    return $value;
  }

  if (!in_array($value, $allowedValues[$fieldName], TRUE)) {
    throw new RuntimeException("Invalid value for {$fieldName}: {$value}");
  }

  return $value;
};

$normalizeDelegateTitle = static function (string $value) use ($allowedDelegateTitles): string {
  $value = trim(strtolower($value));
  if ($value === '') {
    return '';
  }

  if (!in_array($value, $allowedDelegateTitles, TRUE)) {
    throw new RuntimeException("Invalid delegate_title: {$value}");
  }

  return $value;
};

$stats = [
  'rows' => 0,
  'delegates_created' => 0,
  'delegates_updated' => 0,
  'registrations_created' => 0,
  'registrations_updated' => 0,
  'skipped' => 0,
  'errors' => 0,
];

$line = 1;
while (($row = fgetcsv($handle)) !== FALSE) {
  $line++;
  $stats['rows']++;

  $rowData = [];
  foreach ($headers as $header) {
    $rowData[$header] = isset($row[$headerIndex[$header]]) ? trim((string) $row[$headerIndex[$header]]) : '';
  }

  if ($get($rowData, 'delegate_first_name') === '' && $get($rowData, 'delegate_last_name') === '' && $get($rowData, 'delegate_email') === '') {
    continue;
  }

  try {
    $conferenceValue = $get($rowData, 'conference_nid');
    $conferenceNid = $conferenceValue !== '' ? $resolveNodeId($conferenceValue, 'conference') : $defaultConferenceNid;

    $delegateTitleField = $normalizeDelegateTitle($get($rowData, 'delegate_title'));
    $delegateFirstName = $get($rowData, 'delegate_first_name');
    $delegateLastName = $get($rowData, 'delegate_last_name');
    $delegateEmail = strtolower($get($rowData, 'delegate_email'));
    $delegateJobTitle = $get($rowData, 'delegate_job_title');
    $delegateMobile = $get($rowData, 'delegate_mobile');
    $delegateReference = $get($rowData, 'delegate_reference');
    $delegateInstitutionValue = $get($rowData, 'delegate_institution_nid_or_title');
    $delegateInstitutionNid = $resolveNodeId($delegateInstitutionValue, 'institution');

    if ($conferenceNid === 0) {
      throw new RuntimeException('Could not resolve conference_nid.');
    }

    if ($delegateFirstName === '' || $delegateLastName === '' || $delegateEmail === '' || $delegateJobTitle === '') {
      throw new RuntimeException('delegate_first_name, delegate_last_name, delegate_email, and delegate_job_title are required.');
    }

    if (!filter_var($delegateEmail, FILTER_VALIDATE_EMAIL)) {
      throw new RuntimeException("Invalid delegate_email '{$delegateEmail}'.");
    }

    if ($delegateInstitutionNid === 0) {
      throw new RuntimeException("Could not resolve delegate_institution_nid_or_title '{$delegateInstitutionValue}'.");
    }

    $delegateNodeTitle = trim($delegateFirstName . ' ' . $delegateLastName);

    $delegateIds = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'delegate')
      ->condition('field_email', $delegateEmail)
      ->range(0, 1)
      ->execute();

    if (!empty($delegateIds)) {
      $delegateNode = $nodeStorage->load((int) reset($delegateIds));
      if (!$delegateNode) {
        throw new RuntimeException('Existing delegate node could not be loaded.');
      }

      $stats['delegates_updated']++;
    }
    else {
      $delegateNode = Node::create([
        'type' => 'delegate',
        'status' => 1,
      ]);
      $stats['delegates_created']++;
    }

    $delegateNode->setTitle($delegateNodeTitle);
    $delegateNode->set('field_first_name', $delegateFirstName);
    $delegateNode->set('field_last_name', $delegateLastName);
    $delegateNode->set('field_email', $delegateEmail);
    $delegateNode->set('field_job_title', $delegateJobTitle);
    $delegateNode->set('field_institution', [['target_id' => $delegateInstitutionNid]]);

    if ($delegateMobile !== '') {
      $delegateNode->set('field_mobile', $delegateMobile);
    }

    if ($delegateReference !== '') {
      $delegateNode->set('field_delegate_reference', $delegateReference);
    }

    if ($delegateTitleField !== '') {
      $delegateNode->set('field_title', $delegateTitleField);
    }

    $countryTid = $resolveTermId($get($rowData, 'delegate_country_nid_or_title'), 'countries');
    if ($countryTid) {
      $delegateNode->set('field_country', [['target_id' => $countryTid]]);
    }

    $dietryTid = $resolveTermId($get($rowData, 'delegate_dietry_requirements_nid_or_title'), 'dietry_requirements');
    if ($dietryTid) {
      $delegateNode->set('field_dietry_requirements', [['target_id' => $dietryTid]]);
    }

    $trackTid = $resolveTermId($get($rowData, 'delegate_track_preference_nid_or_title'), 'conference_tracks');
    if ($trackTid) {
      $delegateNode->set('field_track_preference', [['target_id' => $trackTid]]);
    }

    if (!$dryRun) {
      $delegateNode->save();
    }

    $delegateId = (int) $delegateNode->id();

    $registrationTitle = $get($rowData, 'registration_title') !== ''
      ? $get($rowData, 'registration_title')
      : $delegateNodeTitle;

    $registrationPayload = [
      'type' => 'conference_registration',
      'title' => $registrationTitle,
      'status' => 1,
      'field_conference' => [['target_id' => $conferenceNid]],
      'field_delegate' => [['target_id' => $delegateId]],
      'field_institution1' => [['target_id' => $delegateInstitutionNid]],
    ];

    foreach (array_keys($allowedValues) as $fieldName) {
      $normalized = $normalizeListValue($fieldName, $get($rowData, $fieldName));
      if ($normalized !== '') {
        $registrationPayload[$fieldName] = $normalized;
      }
    }

    foreach (['qr_code', 'checkin_datetime', 'monday_datetime', 'tuesday_datetime', 'wednesday_datetime', 'gift_datetime'] as $fieldName) {
      $value = $get($rowData, $fieldName);
      if ($value !== '') {
        $registrationPayload[$fieldName] = $value;
      }
    }

    $registrationIds = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'conference_registration')
      ->condition('field_conference', $conferenceNid)
      ->condition('field_delegate', $delegateId)
      ->condition('field_institution1', $delegateInstitutionNid)
      ->range(0, 1)
      ->execute();

    if (!empty($registrationIds)) {
      $registrationNode = $nodeStorage->load((int) reset($registrationIds));
      if (!$registrationNode) {
        throw new RuntimeException('Existing registration node could not be loaded.');
      }

      $stats['registrations_updated']++;
    }
    else {
      $registrationNode = Node::create([
        'type' => 'conference_registration',
        'status' => 1,
      ] + $registrationPayload);
      $stats['registrations_created']++;
    }

    foreach ($registrationPayload as $fieldName => $value) {
      if ($fieldName !== 'type') {
        $registrationNode->set($fieldName, $value);
      }
    }

    if (!$dryRun) {
      $registrationNode->save();
    }

    fwrite(STDOUT, "Processed: {$delegateNodeTitle} / {$registrationTitle}\n");
  }
  catch (Throwable $e) {
    $stats['errors']++;
    $stats['skipped']++;
    fwrite(STDERR, "Row {$line}: {$e->getMessage()}\n");
  }
}

fclose($handle);

fwrite(STDOUT, "\nDone. Rows: {$stats['rows']}, Delegates created: {$stats['delegates_created']}, Delegates updated: {$stats['delegates_updated']}, Registrations created: {$stats['registrations_created']}, Registrations updated: {$stats['registrations_updated']}, Skipped: {$stats['skipped']}, Errors: {$stats['errors']}\n");
