<?php

namespace Drupal\itsiug_registration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\webform\Entity\Webform;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for the ITSIUG registration workflow.
 */
class RegistrationController extends ControllerBase {

  /**
   * Display the delegate registration Webform.
   */
  public function delegateForm() {

    $webform = Webform::load('delegate_registration');

    if (!$webform) {
      throw new \RuntimeException(
        'The Delegate Registration Webform could not be found.'
      );
    }

    return [
      '#type' => 'webform',
      '#webform' => $webform,
    ];
  }

/**
 * Process a scanned conference QR code.
 */
public function scannerProcess(Request $request) {

  $data = json_decode($request->getContent(), TRUE);

  $qr_code = trim($data['qr_code'] ?? '');

  if ($qr_code === '') {
    return new JsonResponse([
      'success' => FALSE,
      'message' => 'No QR code was received.',
    ], 400);
  }

  // Find the Conference Registration using the QR code.
  $registration_ids = \Drupal::entityQuery('node')
    ->accessCheck(FALSE)
    ->condition('type', 'conference_registration')
    ->condition('field_qr_code', $qr_code)
    ->range(0, 1)
    ->execute();

  if (empty($registration_ids)) {
    return new JsonResponse([
      'success' => FALSE,
      'message' => 'QR code not recognised.',
    ], 404);
  }

  $registration_nid = reset($registration_ids);

  $registration = Node::load($registration_nid);

  if (!$registration) {
    return new JsonResponse([
      'success' => FALSE,
      'message' => 'Conference registration could not be loaded.',
    ], 404);
  }

  // Get the Delegate.
  $delegate = NULL;

  if (!$registration->get('field_delegate')->isEmpty()) {
    $delegate = $registration->get('field_delegate')->entity;
  }

  if (!$delegate) {
    return new JsonResponse([
      'success' => FALSE,
      'message' => 'Delegate could not be found.',
    ], 404);
  }

  // Get the Institution.
  $institution = NULL;

  if (!$registration->get('field_institution1')->isEmpty()) {
    $institution = $registration->get('field_institution1')->entity;
  }

  // Determine whether this is a Staff Scanner.
  $account = \Drupal::currentUser();

  $is_staff_scanner = $account->hasPermission(
    'perform conference qr checkin'
  );

/*
 * ------------------------------------------------------------
 * Determine the current conference day.
 * ------------------------------------------------------------
 *
 * The Conference content type contains the actual dates:
 *
 * field_checkin_date = Sunday arrival / check-in day
 * field_day1_date    = Monday
 * field_day2_date    = Tuesday
 * field_day3_date    = Wednesday
 */

$timezone = new \DateTimeZone('Africa/Johannesburg');

$now = new \DateTime('now', $timezone);

/*
 * ------------------------------------------------------------
 * DEVELOPMENT TEST DATE
 * ------------------------------------------------------------
 *
 * The scanner JavaScript sends test_date in the JSON POST
 * while we are testing the March 2027 conference locally.
 *
 * Remove this test-date override before production deployment.
 */
$test_date = $data['test_date'] ?? NULL;

if (
  is_string($test_date) &&
  preg_match('/^\d{4}-\d{2}-\d{2}$/', $test_date)
) {
  $today = $test_date;
}
else {
  $today = $now->format('Y-m-d');
}

$day_field = NULL;
$day_label = NULL;

/*
 * Find the active Conference.
 */
$conference_ids = \Drupal::entityQuery('node')
  ->accessCheck(FALSE)
  ->condition('type', 'conference')
  ->condition('field_active_conference', 1)
  ->range(0, 1)
  ->execute();

$conference = NULL;

if (!empty($conference_ids)) {
  $conference = Node::load(reset($conference_ids));
}

/*
 * Determine which conference day today represents.
 */
if ($conference) {

  /*
   * Day 1.
   */
  if (!$conference->get('field_day1_date')->isEmpty()) {

    $day1 = substr(
      $conference->get('field_day1_date')->value,
      0,
      10
    );

    if ($today === $day1) {

      $day_field = 'field_monday_attendance';
      $day_label = 'Monday';
    }
  }

  /*
   * Day 2.
   */
  if (!$day_field && !$conference->get('field_day2_date')->isEmpty()) {

    $day2 = substr(
      $conference->get('field_day2_date')->value,
      0,
      10
    );

    if ($today === $day2) {

      $day_field = 'field_tuesday_attendance';
      $day_label = 'Tuesday';
    }
  }

  /*
   * Day 3.
   */
  if (!$day_field && !$conference->get('field_day3_date')->isEmpty()) {

    $day3 = substr(
      $conference->get('field_day3_date')->value,
      0,
      10
    );

    if ($today === $day3) {

      $day_field = 'field_wednesday_attendance';
      $day_label = 'Wednesday';
    }
  }
}

/*
 * ------------------------------------------------------------
 * STAFF SCAN
 * ------------------------------------------------------------
 */

if ($is_staff_scanner) {

  $changes = [];

  /*
   * Check-in.
   *
   * Only record the first check-in.
   */
  if (
    $registration->get('field_checkin_status')->isEmpty() ||
    $registration->get('field_checkin_status')->value !== 'checked_in'
  ) {

    $registration->set(
      'field_checkin_status',
      'checked_in'
    );

    $registration->set(
      'field_checkin_datetime',
      $now->format('Y-m-d\TH:i:s')
    );

    $changes[] = 'checked in';
  }

  /*
   * Conference gift.
   *
   * Only record the first gift receipt.
   */
  if (
    $registration->get('field_conference_gift')->isEmpty() ||
    $registration->get('field_conference_gift')->value !== 'received'
  ) {

    $registration->set(
      'field_conference_gift',
      'received'
    );

    $registration->set(
      'field_gift_datetime',
      $now->format('Y-m-d\TH:i:s')
    );

    $changes[] = 'gift received';
  }

  /*
   * Conference attendance.
   *
   * Only record the first attendance for the day.
   */
  if ($day_field) {

    if (
      $registration->get($day_field)->isEmpty() ||
      $registration->get($day_field)->value !== 'present'
    ) {

      $registration->set(
        $day_field,
        'present'
      );

      $datetime_field = str_replace(
        '_attendance',
        '_datetime',
        $day_field
      );

      $registration->set(
        $datetime_field,
        $now->format('Y-m-d\TH:i:s')
      );

      $changes[] = $day_label . ' attendance';
    }
  }

  /*
   * Only save if something actually changed.
   */
  if (!empty($changes)) {
    $registration->save();
  }

  /*
   * Build a useful response for the Scanner screen.
   */
  if (empty($changes)) {

    $message =
      'No changes made. ' .
      'The delegate is already checked in, ' .
      'has received the conference gift' .
      ($day_label
        ? ' and is already marked Present for ' . $day_label . '.'
        : '.');

  }
  else {

    $message =
      'Staff scan processed: ' .
      implode(', ', $changes) . '.';
  }

  return new JsonResponse([
    'success' => TRUE,
    'mode' => 'staff',
    'delegate' => $delegate->label(),
    'institution' => $institution
      ? $institution->label()
      : '',
    'message' => $message,
  ]);
}

  /*
   * ------------------------------------------------------------
   * DELEGATE SELF-SCAN
   * ------------------------------------------------------------
   */

  if (!$day_field) {

    return new JsonResponse([
      'success' => FALSE,
      'mode' => 'delegate',
      'delegate' => $delegate->label(),
      'message' => 'Delegate self-scanning is available Monday to Wednesday.',
    ]);
  }

/*
 * Record attendance only if the delegate
 * has not already been marked Present.
 */
if (
  $registration->get($day_field)->isEmpty() ||
  $registration->get($day_field)->value !== 'present'
) {

  $registration->set(
    $day_field,
    'present'
  );

  $datetime_field = str_replace(
    '_attendance',
    '_datetime',
    $day_field
  );

  $registration->set(
    $datetime_field,
    $now->format('Y-m-d\TH:i:s')
  );

  $registration->save();

  $message =
    $day_label . ' attendance recorded.';

}
else {

  $message =
    $day_label .
    ' attendance was already recorded. ' .
    'The original attendance time has been retained.';
}

return new JsonResponse([
  'success' => TRUE,
  'mode' => 'delegate',
  'delegate' => $delegate->label(),
  'institution' => $institution
    ? $institution->label()
    : '',
  'message' => $message,
]);

}

/**
 * Staff Conference Scanner.
 */
public function scanner() {

  return [
    '#type' => 'container',

    '#attributes' => [
      'class' => [
        'itsiug-scanner-page',
      ],
    ],

    'heading' => [
      '#markup' => '<h1>Conference Scanner</h1>',
    ],

    'instructions' => [
      '#markup' => '<p>Point the camera at the delegate QR code.</p>',
    ],

    'reader' => [
      '#markup' => '<div id="itsiug-qr-reader"></div>',
    ],

    'result' => [
      '#markup' => '<div id="itsiug-scan-result"><p>Starting camera...</p></div>',
    ],

    '#attached' => [
      'library' => [
        'itsiug_registration/scanner',
      ],
    ],
  ];
}

/**
 * Test certificate generation for a registration.
 *
 * Temporary development route. Remove after certificate generation
 * has been integrated into the production workflow.
 */
public function testCertificate($registration) {

  $node = \Drupal\node\Entity\Node::load($registration);

  if (!$node) {
    return new \Symfony\Component\HttpFoundation\Response(
      'Registration not found.',
      404
    );
  }

  if ($node->bundle() !== 'conference_registration') {
    return new \Symfony\Component\HttpFoundation\Response(
      'The supplied node is not a conference registration.',
      400
    );
  }

  try {

    $generator = \Drupal::service(
      'itsiug_registration.certificate_generator'
    );

    $result = $generator->generate($node);

    if (!$result['success']) {
      return new \Symfony\Component\HttpFoundation\Response(
        $result['message'],
        400
      );
    }

    return new \Symfony\Component\HttpFoundation\Response(
      '<h1>Certificate generated successfully</h1>' .
      '<p>' . htmlspecialchars($result['message']) . '</p>' .
      '<p><strong>File:</strong> ' .
      htmlspecialchars($result['filename']) .
      '</p>' .
      '<p><strong>Certificate number:</strong> ' .
      htmlspecialchars($result['certificate_number']) .
      '</p>' .
      '<p><strong>File ID:</strong> ' .
      (int) $result['file_id'] .
      '</p>'
    );

  }
  catch (\Throwable $e) {

    \Drupal::logger('itsiug_registration')->error(
      'Certificate generation failed for registration @nid: @message',
      [
        '@nid' => $registration,
        '@message' => $e->getMessage(),
      ]
    );

    return new \Symfony\Component\HttpFoundation\Response(
      'Certificate generation failed: ' .
      htmlspecialchars($e->getMessage()),
      500
    );
  }

}

/**
 * Builds the certificate download link for a registration.
 */
private function buildCertificateLink($registration) {

  if (
    $registration->get('field_certificate_file')->isEmpty() ||
    $registration->get('field_certificate_status')->isEmpty()
  ) {
    return '';
  }

  $status = $registration
    ->get('field_certificate_status')
    ->value;

  if (!in_array($status, ['generated', 'issued'], TRUE)) {
    return '';
  }

  $fid = $registration
    ->get('field_certificate_file')
    ->target_id;

  if (!$fid) {
    return '';
  }

  $file = \Drupal\file\Entity\File::load($fid);

  if (!$file) {
    return '';
  }

  $file_url = \Drupal::service('file_url_generator')
    ->generateAbsoluteString($file->getFileUri());

  return [
    'data' => [
      '#markup' =>
        '<a href="' .
        htmlspecialchars($file_url, ENT_QUOTES, 'UTF-8') .
        '" target="_blank" rel="noopener" class="button button--small">' .
        $this->t('Download Certificate') .
        '</a>',
    ],
  ];
}

public function logout() {

  $session = \Drupal::request()->getSession();

  // Clear the institution registration session.
  $session->remove('itsiug_registration');

  $this->messenger()->addStatus(
    $this->t('Registration access has been closed.')
  );

  $url = Url::fromRoute('itsiug_registration.access');

  return new RedirectResponse($url->toString());
}
  
  /**
   * Display the delegate dashboard for the current institution.
   */
  public function dashboard() {

    $account = \Drupal::currentUser();

    if ($account->isAnonymous()) {
      return [
        '#markup' => $this->t(
          'You must be logged in to view the delegate dashboard.'
        ),
      ];
    }

    $uid = $account->id();

    \Drupal::logger('itsiug_registration')->notice(
      'DASHBOARD DEBUG: UID @uid',
      ['@uid' => $uid]
    );

    /**
     * Find the institution represented by the current user.
     *
     * The Institution content type stores the representative
     * as an entity reference in field_representative.
     */
    $institution_ids = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'institution')
      ->condition('field_representative', $uid)
      ->range(0, 1)
      ->execute();

    if (empty($institution_ids)) {
      return [
        '#markup' => $this->t(
          'No institution is associated with your representative account.'
        ),
      ];
    }

    $institution_nid = reset($institution_ids);

    $institution = \Drupal\node\Entity\Node::load($institution_nid);

    if (!$institution) {
      return [
        '#markup' => $this->t(
          'The institution could not be found.'
        ),
      ];
    }

    // Find all ITSIUG 2027 registrations belonging to this institution.
    $registration_nids = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'conference_registration')
      ->condition('field_conference', 2)
      ->condition('field_institution1', $institution_nid)
      ->sort('created', 'ASC')
      ->execute();

    $rows = [];

    foreach ($registration_nids as $registration_nid) {

      $registration = \Drupal\node\Entity\Node::load(
        $registration_nid
      );

      if (!$registration) {
        continue;
      }

      $delegate_nid = $registration
        ->get('field_delegate')
        ->target_id;

      $delegate = $delegate_nid
        ? \Drupal\node\Entity\Node::load($delegate_nid)
        : NULL;

      if (!$delegate) {
        continue;
      }

      $rows[] = [

        'delegate' => [
          'data' => [
            '#type' => 'link',
            '#title' => $delegate->label(),
            '#url' => \Drupal\Core\Url::fromRoute(
              'entity.node.canonical',
              ['node' => $delegate->id()]
            ),
          ],
        ],

        'email' => $delegate->get('field_email')->value,

        'registration' => $registration
          ->get('field_registration_status')
          ->first()
          ->getFieldDefinition()
          ->getSetting('allowed_values')[
            $registration->get('field_registration_status')->value
          ] ?? $registration->get('field_registration_status')->value,

        'checkin' => $registration
          ->get('field_checkin_status')
          ->first()
          ->getFieldDefinition()
          ->getSetting('allowed_values')[
            $registration->get('field_checkin_status')->value
          ] ?? $registration->get('field_checkin_status')->value,

        'monday' => $registration
          ->get('field_monday_attendance')
          ->first()
          ->getFieldDefinition()
          ->getSetting('allowed_values')[
            $registration->get('field_monday_attendance')->value
          ] ?? $registration->get('field_monday_attendance')->value,

        'tuesday' => $registration
          ->get('field_tuesday_attendance')
          ->first()
          ->getFieldDefinition()
          ->getSetting('allowed_values')[
            $registration->get('field_tuesday_attendance')->value
          ] ?? $registration->get('field_tuesday_attendance')->value,

        'wednesday' => $registration
          ->get('field_wednesday_attendance')
          ->first()
          ->getFieldDefinition()
          ->getSetting('allowed_values')[
            $registration->get('field_wednesday_attendance')->value
          ] ?? $registration->get('field_wednesday_attendance')->value,

        'certificate' => $this->buildCertificateLink($registration),

      ];
    }

    \Drupal::logger('itsiug_registration')->notice(
      'DASHBOARD DEBUG: institution=@institution registrations=@registrations rows=@rows',
      [
        '@institution' => $institution_nid,
        '@registrations' => implode(',', $registration_nids),
        '@rows' => count($rows),
      ]
    );

    return [

      'header' => [
        '#markup' =>
          '<h2>' .
          $this->t(
            '@institution — ITSIUG 2027',
            ['@institution' => $institution->label()]
          ) .
          '</h2>',
      ],

      'intro' => [
        '#markup' =>
          '<p>' .
          $this->t(
            'The following delegates are registered for your institution.'
          ) .
          '</p>',
      ],

      'delegates' => [
        '#type' => 'table',

        '#header' => [
          $this->t('Delegate'),
          $this->t('Email'),
          $this->t('Registration'),
          $this->t('Check-in'),
          $this->t('Monday'),
          $this->t('Tuesday'),
          $this->t('Wednesday'),
          $this->t('Certificate'),
        ],

        '#rows' => $rows,

        '#empty' => $this->t(
          'No delegates have been registered yet.'
        ),
      ],

      'actions' => [
        '#type' => 'container',

        '#attributes' => [
          'class' => [
            'itsiug-registration-actions',
          ],
        ],

        'register' => [
          '#type' => 'link',
          '#title' => $this->t(
            'Register Another Delegate'
          ),
          '#url' => \Drupal\Core\Url::fromRoute(
            'itsiug_registration.delegate'
          ),
          '#attributes' => [
            'class' => [
              'button',
              'button--primary',
            ],
          ],
        ],

        'logout' => [
          '#type' => 'link',
          '#title' => $this->t('Logout'),
          '#url' => \Drupal\Core\Url::fromRoute(
            'itsiug_registration.logout'
          ),
          '#attributes' => [
            'class' => [
              'button',
            ],
          ],
        ],

      ],

    ];
  }

  /**
   * Display the ITSIUG 2027 reporting dashboard.
   */
  public function reports() {

    // Get all ITSIUG 2027 registrations.
    $registration_nids = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'conference_registration')
      ->condition('field_conference', 2)
      ->sort('created', 'ASC')
      ->execute();

    $total = count($registration_nids);

    $checked_in = 0;
    $monday = 0;
    $tuesday = 0;
    $wednesday = 0;
    $fully_attended = 0;
    $certificates_generated = 0;

    // Institution summary.
    $institutions = [];

    // Detailed delegate report.
    $delegate_rows = [];

    foreach ($registration_nids as $registration_nid) {

      $registration = \Drupal\node\Entity\Node::load(
        $registration_nid
      );

      if (!$registration) {
        continue;
      }

      /*
       * Institution.
       */
      $institution_nid = $registration
        ->get('field_institution1')
        ->target_id;

      $institution = $institution_nid
        ? \Drupal\node\Entity\Node::load($institution_nid)
        : NULL;

      $institution_name = $institution
        ? $institution->label()
        : $this->t('Unknown institution');

      /*
       * Delegate.
       */
      $delegate_nid = $registration
        ->get('field_delegate')
        ->target_id;

      $delegate = $delegate_nid
        ? \Drupal\node\Entity\Node::load($delegate_nid)
        : NULL;

      $delegate_name = $delegate
        ? $delegate->label()
        : $this->t('Unknown delegate');

      $delegate_email = '';

      if (
        $delegate &&
        !$delegate->get('field_email')->isEmpty()
      ) {
        $delegate_email = $delegate
          ->get('field_email')
          ->value;
      }

      /*
       * Registration status.
       */
      $registration_status = '';

      if (
        !$registration->get('field_registration_status')->isEmpty()
      ) {
        $status_value = $registration
          ->get('field_registration_status')
          ->value;

        $allowed_values = $registration
          ->get('field_registration_status')
          ->first()
          ->getFieldDefinition()
          ->getSetting('allowed_values');

        $registration_status =
          $allowed_values[$status_value]
          ?? $status_value;
      }

      /*
       * Check-in.
       */
      $checkin_status = '';

      if (
        !$registration->get('field_checkin_status')->isEmpty()
      ) {
        $checkin_value = $registration
          ->get('field_checkin_status')
          ->value;

        $allowed_values = $registration
          ->get('field_checkin_status')
          ->first()
          ->getFieldDefinition()
          ->getSetting('allowed_values');

        $checkin_status =
          $allowed_values[$checkin_value]
          ?? $checkin_value;

        if ($checkin_value === 'checked_in') {
          $checked_in++;
        }
      }

      /*
       * Monday attendance.
       */
      $monday_status = '';

      if (
        !$registration->get('field_monday_attendance')->isEmpty()
      ) {
        $monday_value = $registration
          ->get('field_monday_attendance')
          ->value;

        $allowed_values = $registration
          ->get('field_monday_attendance')
          ->first()
          ->getFieldDefinition()
          ->getSetting('allowed_values');

        $monday_status =
          $allowed_values[$monday_value]
          ?? $monday_value;

        if ($monday_value === 'present') {
          $monday++;
        }
      }

      /*
       * Tuesday attendance.
       */
      $tuesday_status = '';

      if (
        !$registration->get('field_tuesday_attendance')->isEmpty()
      ) {
        $tuesday_value = $registration
          ->get('field_tuesday_attendance')
          ->value;

        $allowed_values = $registration
          ->get('field_tuesday_attendance')
          ->first()
          ->getFieldDefinition()
          ->getSetting('allowed_values');

        $tuesday_status =
          $allowed_values[$tuesday_value]
          ?? $tuesday_value;

        if ($tuesday_value === 'present') {
          $tuesday++;
        }
      }

      /*
       * Wednesday attendance.
       */
      $wednesday_status = '';

      if (
        !$registration->get('field_wednesday_attendance')->isEmpty()
      ) {
        $wednesday_value = $registration
          ->get('field_wednesday_attendance')
          ->value;

        $allowed_values = $registration
          ->get('field_wednesday_attendance')
          ->first()
          ->getFieldDefinition()
          ->getSetting('allowed_values');

        $wednesday_status =
          $allowed_values[$wednesday_value]
          ?? $wednesday_value;

        if ($wednesday_value === 'present') {
          $wednesday++;
        }
      }

      /*
       * Certificate status.
       */
      $certificate_status = '';

      if (
        !$registration->get('field_certificate_status')->isEmpty()
      ) {
        $certificate_value = $registration
          ->get('field_certificate_status')
          ->value;

        $allowed_values = $registration
          ->get('field_certificate_status')
          ->first()
          ->getFieldDefinition()
          ->getSetting('allowed_values');

        $certificate_status =
          $allowed_values[$certificate_value]
          ?? $certificate_value;

        if (
          in_array(
            $certificate_value,
            ['generated', 'issued'],
            TRUE
          )
        ) {
          $certificates_generated++;
        }
      }

      /*
       * Certificate eligibility.
       */
      if (
        !$registration->get('field_certificate_eligibility')->isEmpty() &&
        $registration->get('field_certificate_eligibility')->value === 'eligible'
      ) {
        $fully_attended++;
      }

      /*
       * Build institution summary.
       */
      if (!isset($institutions[$institution_nid])) {
        $institutions[$institution_nid] = [
          'name' => $institution_name,
          'registered' => 0,
          'checked_in' => 0,
          'monday' => 0,
          'tuesday' => 0,
          'wednesday' => 0,
          'certificates' => 0,
        ];
      }

      $institutions[$institution_nid]['registered']++;

      if ($checkin_status === 'Checked in') {
        $institutions[$institution_nid]['checked_in']++;
      }

      if ($monday_status === 'Present') {
        $institutions[$institution_nid]['monday']++;
      }

      if ($tuesday_status === 'Present') {
        $institutions[$institution_nid]['tuesday']++;
      }

      if ($wednesday_status === 'Present') {
        $institutions[$institution_nid]['wednesday']++;
      }

      if (
        in_array(
          $certificate_status,
          ['Generated', 'Issued'],
          TRUE
        )
      ) {
        $institutions[$institution_nid]['certificates']++;
      }

      /*
       * Build detailed delegate report.
       */
      $delegate_rows[] = [
        'delegate' => $delegate_name,
        'institution' => $institution_name,
        'email' => $delegate_email,
        'registration' => $registration_status,
        'checkin' => $checkin_status,
        'monday' => $monday_status,
        'tuesday' => $tuesday_status,
        'wednesday' => $wednesday_status,
        'certificate' => $certificate_status,
      ];
    }

    /*
     * Convert institution summary to table rows.
     */
    $institution_rows = [];

    foreach ($institutions as $institution) {

      $institution_rows[] = [
        $institution['name'],
        $institution['registered'],
        $institution['checked_in'],
        $institution['monday'],
        $institution['tuesday'],
        $institution['wednesday'],
        $institution['certificates'],
      ];
    }

    return [

      /*
       * Page heading.
       */
      'header' => [
        '#markup' =>
          '<h2>' .
          $this->t('ITSIUG 2027 Reports') .
          '</h2>',
      ],

      /*
       * CSV export button.
       */
      'export' => [
        '#type' => 'link',
        '#title' => $this->t('Download Delegate Report (CSV)'),
        '#url' => \Drupal\Core\Url::fromRoute(
          'itsiug_registration.reports_csv'
        ),
        '#attributes' => [
          'class' => [
            'button',
            'button--primary',
          ],
        ],
      ],

      /*
       * Overall conference summary.
       */
      'summary_title' => [
        '#markup' =>
          '<h3>' .
          $this->t('Conference Summary') .
          '</h3>',
      ],

      'summary' => [
        '#type' => 'table',

        '#header' => [
          $this->t('Measure'),
          $this->t('Total'),
        ],

        '#rows' => [

          [
            $this->t('Total registrations'),
            $total,
          ],

          [
            $this->t('Checked in'),
            $checked_in,
          ],

          [
            $this->t('Monday attendance'),
            $monday,
          ],

          [
            $this->t('Tuesday attendance'),
            $tuesday,
          ],

          [
            $this->t('Wednesday attendance'),
            $wednesday,
          ],

          [
            $this->t('Eligible for certificate'),
            $fully_attended,
          ],

          [
            $this->t('Certificates generated'),
            $certificates_generated,
          ],

        ],
      ],

      /*
       * Institution summary.
       */
      'institution_title' => [
        '#markup' =>
          '<h3>' .
          $this->t('Institution Summary') .
          '</h3>',
      ],

      'institutions' => [
        '#type' => 'table',

        '#header' => [
          $this->t('Institution'),
          $this->t('Registered'),
          $this->t('Checked in'),
          $this->t('Monday'),
          $this->t('Tuesday'),
          $this->t('Wednesday'),
          $this->t('Certificates'),
        ],

        '#rows' => $institution_rows,

        '#empty' => $this->t(
          'No institution data is available.'
        ),
      ],

      /*
       * Detailed delegate report.
       */
      'delegate_title' => [
        '#markup' =>
          '<h3>' .
          $this->t('Delegate Detail') .
          '</h3>',
      ],

      'delegates' => [
        '#type' => 'table',

        '#header' => [
          $this->t('Delegate'),
          $this->t('Institution'),
          $this->t('Email'),
          $this->t('Registration'),
          $this->t('Check-in'),
          $this->t('Monday'),
          $this->t('Tuesday'),
          $this->t('Wednesday'),
          $this->t('Certificate'),
        ],

        '#rows' => $delegate_rows,

        '#empty' => $this->t(
          'No delegate data is available.'
        ),

        '#sticky' => TRUE,
      ],

    ];
  }

  /**
   * Download the ITSIUG 2027 delegate report as CSV.
   */
  public function reportsCsv() {

    $registration_nids = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'conference_registration')
      ->condition('field_conference', 2)
      ->sort('created', 'ASC')
      ->execute();

    $handle = fopen('php://temp', 'w+');

    // CSV header.
    fputcsv($handle, [
      'Delegate',
      'Institution',
      'Email',
      'Registration',
      'Check-in',
      'Monday',
      'Tuesday',
      'Wednesday',
      'Certificate',
    ]);

    foreach ($registration_nids as $registration_nid) {

      $registration = \Drupal\node\Entity\Node::load(
        $registration_nid
      );

      if (!$registration) {
        continue;
      }

      /*
       * Institution.
       */
      $institution_nid = $registration
        ->get('field_institution1')
        ->target_id;

      $institution = $institution_nid
        ? \Drupal\node\Entity\Node::load($institution_nid)
        : NULL;

      $institution_name = $institution
        ? $institution->label()
        : 'Unknown institution';

      /*
       * Delegate.
       */
      $delegate_nid = $registration
        ->get('field_delegate')
        ->target_id;

      $delegate = $delegate_nid
        ? \Drupal\node\Entity\Node::load($delegate_nid)
        : NULL;

      if (!$delegate) {
        continue;
      }

      $delegate_name = $delegate->label();

      $delegate_email = '';

      if (!$delegate->get('field_email')->isEmpty()) {
        $delegate_email = $delegate
          ->get('field_email')
          ->value;
      }

      /*
       * Registration status.
       */
      $registration_status = '';

      if (!$registration->get('field_registration_status')->isEmpty()) {

        $value = $registration
          ->get('field_registration_status')
          ->value;

        $allowed_values = $registration
          ->get('field_registration_status')
          ->first()
          ->getFieldDefinition()
          ->getSetting('allowed_values');

        $registration_status =
          $allowed_values[$value] ?? $value;
      }

      /*
       * Check-in status.
       */
      $checkin_status = '';

      if (!$registration->get('field_checkin_status')->isEmpty()) {

        $value = $registration
          ->get('field_checkin_status')
          ->value;

        $allowed_values = $registration
          ->get('field_checkin_status')
          ->first()
          ->getFieldDefinition()
          ->getSetting('allowed_values');

        $checkin_status =
          $allowed_values[$value] ?? $value;
      }

      /*
       * Monday attendance.
       */
      $monday_status = '';

      if (!$registration->get('field_monday_attendance')->isEmpty()) {

        $value = $registration
          ->get('field_monday_attendance')
          ->value;

        $allowed_values = $registration
          ->get('field_monday_attendance')
          ->first()
          ->getFieldDefinition()
          ->getSetting('allowed_values');

        $monday_status =
          $allowed_values[$value] ?? $value;
      }

      /*
       * Tuesday attendance.
       */
      $tuesday_status = '';

      if (!$registration->get('field_tuesday_attendance')->isEmpty()) {

        $value = $registration
          ->get('field_tuesday_attendance')
          ->value;

        $allowed_values = $registration
          ->get('field_tuesday_attendance')
          ->first()
          ->getFieldDefinition()
          ->getSetting('allowed_values');

        $tuesday_status =
          $allowed_values[$value] ?? $value;
      }

      /*
       * Wednesday attendance.
       */
      $wednesday_status = '';

      if (!$registration->get('field_wednesday_attendance')->isEmpty()) {

        $value = $registration
          ->get('field_wednesday_attendance')
          ->value;

        $allowed_values = $registration
          ->get('field_wednesday_attendance')
          ->first()
          ->getFieldDefinition()
          ->getSetting('allowed_values');

        $wednesday_status =
          $allowed_values[$value] ?? $value;
      }

      /*
       * Certificate status.
       */
      $certificate_status = '';

      if (!$registration->get('field_certificate_status')->isEmpty()) {

        $value = $registration
          ->get('field_certificate_status')
          ->value;

        $allowed_values = $registration
          ->get('field_certificate_status')
          ->first()
          ->getFieldDefinition()
          ->getSetting('allowed_values');

        $certificate_status =
          $allowed_values[$value] ?? $value;
      }

      fputcsv($handle, [
        $delegate_name,
        $institution_name,
        $delegate_email,
        $registration_status,
        $checkin_status,
        $monday_status,
        $tuesday_status,
        $wednesday_status,
        $certificate_status,
      ]);
    }

    rewind($handle);

    $csv = stream_get_contents($handle);

    fclose($handle);

    $response = new \Symfony\Component\HttpFoundation\Response(
      $csv
    );

    $response->headers->set(
      'Content-Type',
      'text/csv; charset=UTF-8'
    );

    $response->headers->set(
      'Content-Disposition',
      'attachment; filename="ITSIUG-2027-Delegate-Report.csv"'
    );

    return $response;
  }

}