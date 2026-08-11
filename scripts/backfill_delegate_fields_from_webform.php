<?php

declare(strict_types=1);

use Drupal\node\Entity\Node;

/**
 * Backfill Delegate fields from delegate_registration webform submissions.
 *
 * Usage:
 *   ddev drush php:script scripts/backfill_delegate_fields_from_webform.php
 *   ddev drush php:script scripts/backfill_delegate_fields_from_webform.php -- --apply
 *
 * Default mode is preview (no writes). Pass --apply to save changes.
 */

$dryRun = TRUE;
$extraArgs = [];

if (isset($extra) && is_array($extra)) {
  $extraArgs = $extra;
}

foreach ($extraArgs as $arg) {
  if (trim((string) $arg) === '--apply') {
    $dryRun = FALSE;
  }
}

if ($dryRun) {
  fwrite(STDOUT, "Running in preview mode (--dry-run). No changes will be saved.\n\n");
}

$submissionIds = \Drupal::entityQuery('webform_submission')
  ->accessCheck(FALSE)
  ->condition('webform_id', 'delegate_registration')
  ->sort('sid', 'DESC')
  ->execute();

if (empty($submissionIds)) {
  fwrite(STDOUT, "No delegate_registration submissions found.\n");
  exit(0);
}

$submissions = \Drupal::entityTypeManager()
  ->getStorage('webform_submission')
  ->loadMultiple($submissionIds);

// Keep most recent submission per email.
$submissionByEmail = [];
foreach ($submissions as $submission) {
  $data = $submission->getData();
  $email = strtolower(trim((string) ($data['email'] ?? '')));

  if ($email === '' || isset($submissionByEmail[$email])) {
    continue;
  }

  $submissionByEmail[$email] = $data;
}

$delegateIds = \Drupal::entityQuery('node')
  ->accessCheck(FALSE)
  ->condition('type', 'delegate')
  ->execute();

if (empty($delegateIds)) {
  fwrite(STDOUT, "No delegate nodes found.\n");
  exit(0);
}

$stats = [
  'delegates_total' => count($delegateIds),
  'delegates_with_matching_submission' => 0,
  'delegates_changed' => 0,
  'field_title_set' => 0,
  'field_job_title_set' => 0,
  'field_track_preference_set' => 0,
  'errors' => 0,
];

$allowedTitles = [
  'prof' => 'Prof',
  'dr' => 'Dr',
  'mr' => 'Mr',
  'mrs' => 'Mrs',
  'miss' => 'Miss',
  'ms' => 'Ms',
];

foreach ($delegateIds as $delegateId) {
  /** @var \Drupal\node\Entity\Node|null $delegate */
  $delegate = Node::load((int) $delegateId);

  if (!$delegate) {
    continue;
  }

  try {
    $delegateEmail = strtolower(trim((string) ($delegate->get('field_email')->value ?? '')));

    if ($delegateEmail === '' || !isset($submissionByEmail[$delegateEmail])) {
      continue;
    }

    $stats['delegates_with_matching_submission']++;

    $data = $submissionByEmail[$delegateEmail];
    $changed = FALSE;

    if ($delegate->get('field_title')->isEmpty()) {
      $mappedTitle = itsiug_map_list_field_value($data['title'] ?? NULL, $allowedTitles);
      if ($mappedTitle !== '') {
        $delegate->set('field_title', $mappedTitle);
        $changed = TRUE;
        $stats['field_title_set']++;
      }
    }

    if ($delegate->get('field_job_title')->isEmpty()) {
      $jobTitle = trim((string) ($data['job_title'] ?? ''));
      if ($jobTitle !== '') {
        $delegate->set('field_job_title', $jobTitle);
        $changed = TRUE;
        $stats['field_job_title_set']++;
      }
    }

    if ($delegate->get('field_track_preference')->isEmpty()) {
      $trackTid = itsiug_find_taxonomy_term($data['track_preference'] ?? NULL, 'conference_tracks');
      if (!empty($trackTid)) {
        $delegate->set('field_track_preference', ['target_id' => $trackTid]);
        $changed = TRUE;
        $stats['field_track_preference_set']++;
      }
    }

    if ($changed) {
      $stats['delegates_changed']++;

      if (!$dryRun) {
        $delegate->save();
      }

      fwrite(
        STDOUT,
        sprintf(
          "%s delegate %d (%s)\n",
          $dryRun ? 'Would update' : 'Updated',
          (int) $delegate->id(),
          $delegate->label()
        )
      );
    }
  }
  catch (\Throwable $e) {
    $stats['errors']++;

    fwrite(
      STDERR,
      sprintf(
        "Error processing delegate %d: %s\n",
        (int) $delegateId,
        $e->getMessage()
      )
    );
  }
}

fwrite(STDOUT, "\nSummary:\n");
foreach ($stats as $key => $value) {
  fwrite(STDOUT, sprintf("- %s: %d\n", $key, $value));
}

if ($dryRun) {
  fwrite(STDOUT, "\nPreview complete. Re-run with --apply to save updates.\n");
}
