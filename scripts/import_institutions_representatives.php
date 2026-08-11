<?php

declare(strict_types=1);

use Drupal\Core\Entity\EntityStorageException;
use Drupal\user\Entity\User;
use Drupal\node\Entity\Node;

/**
 * Bulk import institutions and representative users from CSV.
 *
 * Usage:
 *   ddev drush php:script scripts/import_institutions_representatives.php -- assets/imports/institutions_representatives.csv
 *   ddev drush php:script scripts/import_institutions_representatives.php -- --apply assets/imports/institutions_representatives.csv
 *
 * CSV columns:
 *   institution_title
 *   institution_code
 *   registration_pin
 *   institution_status      (active|inactive|suspended)
 *   payment_status          (pending|paid|partially_paid|cancelled)
 *   delegate_limit
 *   contact_email
 *   representative_email
 *   representative_first_name
 *   representative_last_name
 *   representative_username (optional)
 */

$defaultCsvPath = 'assets/imports/institutions_representatives.csv';
$csvPath = $defaultCsvPath;

// Default to safe preview mode unless --apply is provided.
$dryRun = TRUE;

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

$allowedInstitutionStatus = [
  'active' => TRUE,
  'inactive' => TRUE,
  'suspended' => TRUE,
];

$allowedPaymentStatus = [
  'pending' => TRUE,
  'paid' => TRUE,
  'partially_paid' => TRUE,
  'cancelled' => TRUE,
];

$requiredHeaders = [
  'institution_title',
  'institution_code',
  'registration_pin',
  'institution_status',
  'payment_status',
  'delegate_limit',
  'contact_email',
  'representative_email',
  'representative_first_name',
  'representative_last_name',
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

$headers = array_map(
  static fn($h) => trim((string) $h),
  $headerRow
);

$missing = array_values(array_diff($requiredHeaders, $headers));
if (!empty($missing)) {
  fclose($handle);
  fwrite(STDERR, "CSV is missing required headers: " . implode(', ', $missing) . "\n");
  exit(1);
}

$headerIndex = array_flip($headers);

$userStorage = \Drupal::entityTypeManager()->getStorage('user');

$stats = [
  'rows' => 0,
  'users_created' => 0,
  'users_updated' => 0,
  'institutions_created' => 0,
  'institutions_updated' => 0,
  'errors' => 0,
];

$line = 1; // Header is line 1.
while (($row = fgetcsv($handle)) !== FALSE) {
  $line++;
  $stats['rows']++;

  $data = [];
  foreach ($headers as $h) {
    $idx = $headerIndex[$h];
    $data[$h] = isset($row[$idx]) ? trim((string) $row[$idx]) : '';
  }

  // Skip completely empty rows.
  if (
    $data['institution_title'] === '' &&
    $data['institution_code'] === '' &&
    $data['representative_email'] === ''
  ) {
    continue;
  }

  try {
    $institutionTitle = $data['institution_title'];
    $institutionCode = $data['institution_code'];
    $registrationPin = $data['registration_pin'];
    $institutionStatus = strtolower($data['institution_status']);
    $paymentStatus = strtolower($data['payment_status']);
    $delegateLimit = (int) $data['delegate_limit'];
    $contactEmail = strtolower($data['contact_email']);
    $repEmail = strtolower($data['representative_email']);
    $repFirst = $data['representative_first_name'];
    $repLast = $data['representative_last_name'];
    $repUsername = $data['representative_username'] ?? '';

    if ($institutionTitle === '' || $institutionCode === '' || $repEmail === '') {
      throw new RuntimeException('institution_title, institution_code, and representative_email are required.');
    }

    if (!isset($allowedInstitutionStatus[$institutionStatus])) {
      throw new RuntimeException("Invalid institution_status '{$institutionStatus}'.");
    }

    if (!isset($allowedPaymentStatus[$paymentStatus])) {
      throw new RuntimeException("Invalid payment_status '{$paymentStatus}'.");
    }

    if (!filter_var($repEmail, FILTER_VALIDATE_EMAIL)) {
      throw new RuntimeException("Invalid representative_email '{$repEmail}'.");
    }

    if ($contactEmail !== '' && !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
      throw new RuntimeException("Invalid contact_email '{$contactEmail}'.");
    }

    if ($repUsername === '') {
      $repUsername = $repEmail;
    }

    // Load or create representative user by email.
    $users = $userStorage->loadByProperties(['mail' => $repEmail]);
    /** @var \Drupal\user\Entity\User $user */
    $user = $users ? reset($users) : NULL;

    if (!$user) {
      $usersByName = $userStorage->loadByProperties(['name' => $repUsername]);
      if (!empty($usersByName)) {
        $repUsername .= '+' . substr(sha1($repEmail), 0, 8);
      }

      $user = User::create([
        'name' => $repUsername,
        'mail' => $repEmail,
        'status' => 1,
        'pass' => \Drupal::service('password_generator')->generate(20),
      ]);
      $user->addRole('institution_representative');
      if (!$dryRun) {
        $user->save();
        $stats['users_created']++;
      }
      $actionUser = 'created';
    }
    else {
      $changed = FALSE;
      if (!$user->isActive()) {
        $user->activate();
        $changed = TRUE;
      }
      if (!$user->hasRole('institution_representative')) {
        $user->addRole('institution_representative');
        $changed = TRUE;
      }
      if ($user->getEmail() !== $repEmail) {
        $user->setEmail($repEmail);
        $changed = TRUE;
      }
      if ($changed && !$dryRun) {
        $user->save();
      }
      if (!$dryRun) {
        $stats['users_updated']++;
      }
      $actionUser = 'updated';
    }

    // Load or create institution node by institution code.
    $institutionIds = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'institution')
      ->condition('field_institution_code', $institutionCode)
      ->range(0, 1)
      ->execute();

    /** @var \Drupal\node\Entity\Node $institution */
    $institution = NULL;
    if (!empty($institutionIds)) {
      $institution = Node::load((int) reset($institutionIds));
    }

    $isNewInstitution = FALSE;
    if (!$institution) {
      $institution = Node::create([
        'type' => 'institution',
        'status' => 1,
      ]);
      $isNewInstitution = TRUE;
    }

    $institution->setTitle($institutionTitle);
    $institution->set('field_institution_code', $institutionCode);
    $institution->set('field_registration_pin', $registrationPin);
    $institution->set('field_institution_status', $institutionStatus);
    $institution->set('field_payment_status', $paymentStatus);
    $institution->set('field_delegate_limit', $delegateLimit);

    if ($contactEmail !== '') {
      $institution->set('field_contact_email', $contactEmail);
    }

    if (!$dryRun) {
      $institution->set('field_representative', ['target_id' => (int) $user->id()]);
      $institution->save();

      if ($isNewInstitution) {
        $stats['institutions_created']++;
        $actionInstitution = 'created';
      }
      else {
        $stats['institutions_updated']++;
        $actionInstitution = 'updated';
      }

      fwrite(
        STDOUT,
        "[line {$line}] OK: {$actionUser} user {$repEmail}; {$actionInstitution} institution {$institutionCode} ({$institutionTitle})\n"
      );
    }
    else {
      $actionInstitution = $isNewInstitution ? 'create' : 'update';
      $actionUserPreview = ($actionUser === 'created') ? 'create' : 'update';
      fwrite(
        STDOUT,
        "[line {$line}] PREVIEW: {$actionUserPreview} user {$repEmail}; {$actionInstitution} institution {$institutionCode} ({$institutionTitle})\n"
      );
    }
  }
  catch (Throwable $e) {
    $stats['errors']++;
    fwrite(STDERR, "[line {$line}] ERROR: {$e->getMessage()}\n");
  }
}

fclose($handle);

fwrite(STDOUT, "\nImport summary\n");
fwrite(STDOUT, "Rows processed: {$stats['rows']}\n");
if ($dryRun) {
  fwrite(STDOUT, "Preview mode: no records were saved.\n");
}
else {
  fwrite(STDOUT, "Users created: {$stats['users_created']}\n");
  fwrite(STDOUT, "Users updated: {$stats['users_updated']}\n");
  fwrite(STDOUT, "Institutions created: {$stats['institutions_created']}\n");
  fwrite(STDOUT, "Institutions updated: {$stats['institutions_updated']}\n");
}
fwrite(STDOUT, "Errors: {$stats['errors']}\n");

if ($stats['errors'] > 0) {
  exit(2);
}
