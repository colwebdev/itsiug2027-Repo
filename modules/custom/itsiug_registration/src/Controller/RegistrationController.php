<?php

namespace Drupal\itsiug_registration\Controller;

use Drupal\Component\Utility\Html;
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
   * Display ITSIUG 2027 badge management.
   */
  public function adminBadges() {

  $registration_ids = \Drupal::entityQuery('node')
    ->accessCheck(FALSE)
    ->condition('type', 'conference_registration')
    ->condition('field_conference', 2)
    ->sort('created', 'ASC')
    ->execute();

  $rows = [];

  foreach ($registration_ids as $registration_id) {

    $registration = Node::load($registration_id);

    if (!$registration) {
      continue;
    }

    $delegate = NULL;

    if (!$registration->get('field_delegate')->isEmpty()) {
      $delegate = $registration->get('field_delegate')->entity;
    }

    if (!$delegate) {
      continue;
    }

    $institution = NULL;

    if (!$registration->get('field_institution1')->isEmpty()) {
      $institution = $registration->get('field_institution1')->entity;
    }

    $badge_status_value = '';
    $badge_status = '';

    if (!$registration->get('field_badge_status')->isEmpty()) {
      $badge_status_value = $registration->get('field_badge_status')->value;
      $allowed_values = $registration
        ->get('field_badge_status')
        ->first()
        ->getFieldDefinition()
        ->getSetting('allowed_values');
      $badge_status = $allowed_values[$badge_status_value] ?? $badge_status_value;
    }

    $badge = $this->buildBadgeLink($registration);

    $badge_action = $badge;

    if (!$badge_action) {
      $badge_action = [
        'data' => [
          '#type' => 'link',
          '#title' => $this->t('Generate Badge'),
          '#url' => Url::fromRoute(
            'itsiug_registration.admin_badge_generate',
            [
              'registration' => $registration->id(),
            ]
          ),
          '#attributes' => [
            'class' => [
              'button',
              'button--primary',
            ],
          ],
        ],
      ];
    }

    $rows[] = [
      'delegate' => [
        'data' => [
          '#type' => 'link',
          '#title' => $delegate->label(),
          '#url' => Url::fromRoute(
            'entity.node.canonical',
            [
              'node' => $delegate->id(),
            ]
          ),
        ],
      ],

      'institution' => $institution
        ? $institution->label()
        : '',

      'qr' => $registration->get('field_qr_code')->value ?? '',

      'status' => $badge_status,

      'badge' => $badge_action,
    ];
  }

return [

'badge_management' => [
  '#type' => 'container',

  '#attached' => [
    'library' => [
      'itsiug_theme/global-styling',
    ],
  ],

  '#attributes' => [
    'class' => [
      'itsiug-admin-page',
      'itsiug-badge-management',
    ],
  ],

    'heading' => [
      '#markup' =>
        '<h1>' .
        $this->t('ITSIUG 2027 Badge Management') .
        '</h1>',
    ],

    'intro' => [
      '#markup' =>
        '<p class="itsiug-admin-intro">' .
        $this->t(
          'Generate and download conference badges for registered delegates.'
        ) .
        '</p>',
    ],

    'badges' => [
      '#type' => 'table',

      '#attributes' => [
        'class' => [
          'itsiug-delegate-management-table',
        ],
      ],

      '#header' => [
        $this->t('Delegate'),
        $this->t('Institution'),
        $this->t('QR Code'),
        $this->t('Status'),
        $this->t('Badge'),
      ],

      '#rows' => $rows,

      '#empty' => $this->t(
        'No ITSIUG 2027 registrations were found.'
      ),
    ],

    'back' => [
      '#type' => 'link',
      '#title' => $this->t('← Back to Administration'),
      '#url' => Url::fromRoute(
        'itsiug_registration.admin'
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
 * Display the registration information modal page.
 */
public function registerInfo() {

  return [
    '#type' => 'container',
    '#attributes' => [
      'class' => [
        'itsiug-registerinfo-page',
      ],
    ],

    'overlay' => [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'itsiug-registerinfo-modal-overlay',
        ],
      ],

      'modal' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'itsiug-registerinfo-modal',
          ],
        ],

        'message' => [
          '#markup' =>
            '<p><strong>Welcome to the Registration Pages.</strong></p>' .
            '<p>Your registration wil remain PENDING until the Good Standing of the Institutional Membership is confirmed.</p>',
        ],

        'actions' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => [
              'itsiug-registerinfo-actions',
            ],
          ],

          'register' => [
            '#type' => 'link',
            '#title' => $this->t('Register Now!'),
            '#url' => Url::fromRoute('itsiug_registration.access'),
            '#attributes' => [
              'class' => [
                'itsiug-registerinfo-button',
              ],
            ],
          ],
        ],
      ],
    ],

    '#attached' => [
      'library' => [
        'itsiug_theme/global-styling',
        'itsiug_registration/registerinfo_modal',
      ],
    ],
  ];
}

/**
 * Display the delegate registration Webform.
 */
public function delegateForm() {

  // Require an established institution registration session.
  $session = \Drupal::request()->getSession();
  $registration = $session->get('itsiug_registration');

  if (empty($registration['institution_nid'])) {
    return new RedirectResponse(
      Url::fromRoute('itsiug_registration.access')->toString()
    );
  }

  $webform = Webform::load('delegate_registration');

  if (!$webform) {
    throw new \RuntimeException(
      'The Delegate Registration Webform could not be found.'
    );
  }

  return [
    '#type' => 'webform',
    '#webform' => $webform,

    '#attributes' => [
      'class' => [
        'itsiug-delegate-registration',
      ],
    ],

    '#attached' => [
      'library' => [
        'itsiug_theme/global-styling',
      ],
    ],
  ];
}

/**
 * Display the delegate registration confirmation page.
 */
public function delegateConfirmation() {

  $session = \Drupal::request()->getSession();
  $registration = $session->get('itsiug_registration');

  // The institution session must still be active.
  if (empty($registration['institution_nid'])) {
    return new RedirectResponse(
      Url::fromRoute('itsiug_registration.access')->toString()
    );
  }

  return [
    '#type' => 'container',
    '#attributes' => [
      'class' => [
        'itsiug-admin-page',
        'itsiug-admin-dashboard',
        'itsiug-reports-page',
        'itsiug-registration-confirmation',
      ],
    ],

    'title' => [
      '#markup' => '<h2>' .
        $this->t('Delegate Registration Complete') .
        '</h2>',
    ],

    'message' => [
      '#markup' => '<p class="itsiug-admin-intro">' .
        $this->t('New submission added to Delegate Registration.') .
        '</p>',
    ],

    'actions' => [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'itsiug-admin-actions',
          'itsiug-registration-confirmation-actions',
        ],
      ],

      'register' => [
        '#type' => 'link',
        '#title' => $this->t('Register Another Delegate'),
        '#url' => Url::fromRoute(
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
        '#url' => Url::fromRoute(
          'itsiug_registration.logout',
          [],
          ['query' => ['destination' => '/home']]
        ),
        '#attributes' => [
          'class' => [
            'button',
          ],
        ],
      ],
    ],

    '#attached' => [
      'library' => [
        'itsiug_theme/global-styling',
      ],
    ],
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
      'result_type' => 'no_qr_received',
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
      'result_type' => 'qr_not_recognised',
      'message' => 'QR code not recognised.',
    ], 404);
  }

  $registration_nid = reset($registration_ids);

  $registration = Node::load($registration_nid);

  if (!$registration) {
    return new JsonResponse([
      'success' => FALSE,
      'result_type' => 'registration_not_found',
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
      'result_type' => 'delegate_not_found',
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
    'result_type' => empty($changes) ? 'already_recorded' : 'scan_recorded',
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
      'result_type' => 'outside_scan_days',
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
  'result_type' => $registration->get($day_field)->value === 'present' && str_contains($message, 'already recorded')
    ? 'already_recorded'
    : 'scan_recorded',
  'mode' => 'delegate',
  'delegate' => $delegate->label(),
  'institution' => $institution
    ? $institution->label()
    : '',
  'message' => $message,
]);

}

/**
 * Display the badge QR processing page.
 *
 * The QR code is supplied as:
 * /badge/scanner?qr=UP-2027-0002
 *
 * During development a test date may also be supplied:
 * /badge/scanner?qr=UP-2027-0002&test_date=2027-03-15
 */
public function badgeScanner() {

  $request = \Drupal::request();

  $qr_code = trim(
    (string) $request->query->get('qr', '')
  );

  $test_date = trim(
    (string) $request->query->get('test_date', '')
  );

  return [
    '#type' => 'container',

    '#attributes' => [
      'class' => [
        'itsiug-badge-scanner-page',
      ],
    ],

    'heading' => [
      '#markup' => '<h1>ITSIUG 2027</h1>',
    ],

    'message' => [
      '#markup' =>
        '<div id="itsiug-badge-message" class="itsiug-scan-status">Processing your badge...</div>',
    ],

    '#attached' => [
      'library' => [
        'itsiug_registration/scanner',
      ],
      'drupalSettings' => [
        'itsiugRegistration' => [
          'qrCode' => $qr_code,
          'testDate' => $test_date,
        ],
      ],
    ],
  ];
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
      '#markup' => '<div id="itsiug-scan-result" class="itsiug-scan-status"><p>Starting camera...</p></div>',
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
   * Test badge generation for a registration.
   */
  public function testBadge($registration) {

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
        'itsiug_registration.badge_generator'
      );

      $result = $generator->generate($node);

      if (!$result['success']) {
        return new \Symfony\Component\HttpFoundation\Response(
          $result['message'],
          400
        );
      }

      return new \Symfony\Component\HttpFoundation\Response(
        '<h1>Badge generated successfully</h1>' .
        '<p>' . htmlspecialchars($result['message']) . '</p>' .
        '<p><strong>File:</strong> ' .
        htmlspecialchars($result['filename']) .
        '</p>' .
        '<p><strong>Badge number:</strong> ' .
        htmlspecialchars($result['badge_number']) .
        '</p>' .
        '<p><strong>File ID:</strong> ' .
        (int) $result['file_id'] .
        '</p>'
      );

    }
    catch (\Throwable $e) {

      \Drupal::logger('itsiug_registration')->error(
        'Badge generation failed for registration @nid: @message',
        [
          '@nid' => $registration,
          '@message' => $e->getMessage(),
        ]
      );

      return new \Symfony\Component\HttpFoundation\Response(
        'Badge generation failed: ' .
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

/**
 * Builds the badge download link for a registration.
 */
private function buildBadgeLink($registration) {

  if (
    $registration->get('field_badge_file')->isEmpty() ||
    $registration->get('field_badge_status')->isEmpty()
  ) {
    return '';
  }

  $status = $registration
    ->get('field_badge_status')
    ->value;

  if (!in_array($status, ['generated', 'issued'], TRUE)) {
    return '';
  }

  $fid = $registration
    ->get('field_badge_file')
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
        $this->t('Download Badge') .
        '</a>',
    ],
  ];
}

public function logout() {

  $session = \Drupal::request()->getSession();

  // Clear the institution registration session.
  $session->remove('itsiug_registration');

  if (!\Drupal::currentUser()->isAnonymous()) {
    user_logout();
  }

  // Prevent query-string destination overrides from sending users to login.
  \Drupal::service('redirect_response_subscriber')->setIgnoreDestination(TRUE);

  // Explicitly redirect to the public home alias after logout.
  $url = Url::fromUserInput('/home');

  return new RedirectResponse($url->toString());
}

/**
 * Cancel a delegate's linked conference registration(s).
 */
public function cancelDelegate($delegate) {

  $account = \Drupal::currentUser();

  if ($account->isAnonymous()) {
    return new RedirectResponse(
      Url::fromRoute('itsiug_registration.access')->toString()
    );
  }

  $delegate_node = Node::load((int) $delegate);

  if (!$delegate_node || $delegate_node->bundle() !== 'delegate') {
    throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
  }

  $can_cancel = $account->hasPermission('access itsiug admin');

  if (!$can_cancel) {
    $institution_id = $delegate_node->get('field_institution')->target_id ?? NULL;

    if ($institution_id) {
      $institution_ids = \Drupal::entityQuery('node')
        ->accessCheck(FALSE)
        ->condition('type', 'institution')
        ->condition('nid', $institution_id)
        ->condition('field_representative', $account->id())
        ->range(0, 1)
        ->execute();

      $can_cancel = !empty($institution_ids);
    }
  }

  if (!$can_cancel) {
    throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
  }

  $registration_ids = \Drupal::entityQuery('node')
    ->accessCheck(FALSE)
    ->condition('type', 'conference_registration')
    ->condition('field_delegate', $delegate_node->id())
    ->execute();

  foreach ($registration_ids as $registration_id) {
    $registration = Node::load($registration_id);

    if (!$registration) {
      continue;
    }

    if (
      $registration->hasField('field_checkin_status') &&
      $registration->get('field_checkin_status')->value === 'checked_in'
    ) {
      $this->messenger()->addWarning(
        $this->t('Delegate cannot be cancelled after check-in.')
      );

      return new RedirectResponse(
        Url::fromRoute('itsiug_registration.dashboard')->toString()
      );
    }
  }

  $updated = 0;

  foreach ($registration_ids as $registration_id) {
    $registration = Node::load($registration_id);

    if (!$registration) {
      continue;
    }

    if (
      $registration->hasField('field_registration_status') &&
      $registration->get('field_registration_status')->value !== 'cancelled'
    ) {
      $registration->set('field_registration_status', 'cancelled');
      $registration->save();
      $updated++;
    }
  }

  if ($updated > 0) {
    $this->messenger()->addStatus(
      $this->t('Delegate has been marked as Cancelled.')
    );
  }
  else {
    $this->messenger()->addStatus(
      $this->t('Delegate was already marked as Cancelled.')
    );
  }

  return new RedirectResponse(
    Url::fromRoute('itsiug_registration.dashboard')->toString()
  );
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

    $sortable_rows = [];

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

      $is_checked_in =
        $registration->hasField('field_checkin_status') &&
        $registration->get('field_checkin_status')->value === 'checked_in';

      $name_data = $this->getDelegateSortData($delegate);

      $certificate_action = $this->buildCertificateLink($registration);

      if (!$certificate_action) {
        $certificate_action = [
          'data' => [
            '#markup' => $this->t('Not Generated'),
          ],
        ];
      }

      $sortable_rows[] = [
        'sort_last_name' => $name_data['last_name'],
        'sort_first_name' => $name_data['first_name'],
        'registration_id' => (int) $registration->id(),
        'row' => [
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
          'certificate' => $certificate_action,
          'actions' => [
            'data' => $delegate->access('update', $account)
              ? [
                '#type' => 'container',
                '#attributes' => [
                  'class' => [
                    'itsiug-row-actions',
                  ],
                ],
                'edit' => [
                  '#type' => 'link',
                  '#title' => $this->t('Edit Delegate'),
                  '#url' => Url::fromRoute(
                    'entity.node.edit_form',
                    ['node' => $delegate->id()]
                  ),
                  '#attributes' => [
                    'class' => [
                      'button',
                      'button--primary',
                      'itsiug-edit-delegate-button',
                    ],
                  ],
                ],
                'cancel' => !$is_checked_in
                  ? [
                    '#type' => 'link',
                    '#title' => $this->t('Cancel Delegate'),
                    '#url' => Url::fromRoute(
                      'itsiug_registration.delegate_cancel',
                      ['delegate' => $delegate->id()]
                    ),
                    '#attributes' => [
                      'class' => [
                        'button',
                        'itsiug-edit-delegate-button',
                      ],
                    ],
                  ]
                  : [],
              ]
              : '',
          ],
        ],
      ];
    }

    usort($sortable_rows, static function (array $left, array $right): int {
      $last_name_compare = strcasecmp(
        (string) ($left['sort_last_name'] ?? ''),
        (string) ($right['sort_last_name'] ?? '')
      );

      if ($last_name_compare !== 0) {
        return $last_name_compare;
      }

      $first_name_compare = strcasecmp(
        (string) ($left['sort_first_name'] ?? ''),
        (string) ($right['sort_first_name'] ?? '')
      );

      if ($first_name_compare !== 0) {
        return $first_name_compare;
      }

      return ((int) ($left['registration_id'] ?? 0)) <=> ((int) ($right['registration_id'] ?? 0));
    });

    $rows = array_map(
      static fn (array $sortable_row): array => $sortable_row['row'],
      $sortable_rows
    );

    \Drupal::logger('itsiug_registration')->notice(
      'DASHBOARD DEBUG: institution=@institution registrations=@registrations rows=@rows',
      [
        '@institution' => $institution_nid,
        '@registrations' => implode(',', $registration_nids),
        '@rows' => count($rows),
      ]
    );

    return [

  '#type' => 'container',

'#attached' => [
  'library' => [
    'itsiug_theme/global-styling',
  ],
],

  '#attributes' => [
    'class' => [
      'itsiug-admin-page',
      'itsiug-admin-dashboard',
      'itsiug-institution-dashboard',
    ],
  ],

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

  '#attributes' => [
    'class' => [
      'itsiug-admin-table',
      'itsiug-delegate-management-table',
    ],
  ],

  '#responsive' => TRUE,

  '#header' => [
    $this->t('Delegate'),
    $this->t('Registration'),
    $this->t('Check-in'),
    $this->t('Monday'),
    $this->t('Tuesday'),
    $this->t('Wednesday'),
    $this->t('Certificate'),
    $this->t('Actions'),
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
            'itsiug-admin-actions',
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
            'itsiug_registration.logout',
            [],
            ['query' => ['destination' => '/home']]
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

    $search = $this->getDelegateSearchTerm();

    // Get all ITSIUG 2027 registrations.
    $registration_nids = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'conference_registration')
      ->condition('field_conference', 2)
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

    // Delegate preference summaries.
    $track_choices = [];
    $dietary_requirements = [];

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

      if ($delegate) {
        $track_label = $this->getDelegateReferenceLabel(
          $delegate,
          'field_track_preference',
          (string) $this->t('Not specified')
        );
        $dietary_label = $this->getDelegateReferenceLabel(
          $delegate,
          'field_dietry_requirements',
          (string) $this->t('Not specified')
        );

        if (!isset($track_choices[$track_label])) {
          $track_choices[$track_label] = 0;
        }
        $track_choices[$track_label]++;

        if (!isset($dietary_requirements[$dietary_label])) {
          $dietary_requirements[$dietary_label] = 0;
        }
        $dietary_requirements[$dietary_label]++;
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

if (strtolower(trim($checkin_status)) === 'checked in') {
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
      if ($delegate && $this->matchesDelegateSearch($registration, $delegate, $institution_name, $search)) {
        $name_data = $this->getDelegateSortData($delegate);

        $delegate_rows[] = [
          'registration_id' => (int) $registration->id(),
          'sort_first_name' => $name_data['first_name'],
          'sort_last_name' => $name_data['last_name'],
          'row' => [
            'delegate' => $delegate_name,
            'institution' => $institution_name,
            'email' => $delegate_email,
            'registration' => $registration_status,
            'checkin' => $checkin_status,
            'monday' => $monday_status,
            'tuesday' => $tuesday_status,
            'wednesday' => $wednesday_status,
            'certificate' => $certificate_status,
          ],
        ];
      }
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

    ksort($track_choices, SORT_NATURAL | SORT_FLAG_CASE);
    ksort($dietary_requirements, SORT_NATURAL | SORT_FLAG_CASE);

    $track_rows = [];
    foreach ($track_choices as $track_label => $track_count) {
      $track_rows[] = [$track_label, $track_count];
    }

    $dietary_rows = [];
    foreach ($dietary_requirements as $dietary_label => $dietary_count) {
      $dietary_rows[] = [$dietary_label, $dietary_count];
    }

    $this->sortDelegateRows($delegate_rows);
    $delegate_rows = array_map(
      static fn (array $delegate_row): array => $delegate_row['row'],
      $delegate_rows
    );

    return [

  'report_page' => [
    '#type' => 'container',

    '#attached' => [
      'library' => [
        'itsiug_theme/global-styling',
      ],
    ],

    '#attributes' => [
      'class' => [
        'itsiug-admin-page',
        'itsiug-reports-page',
      ],
    ],

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
      'actions' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'itsiug-admin-actions',
          ],
        ],

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

  '#attributes' => [
    'class' => [
      'itsiug-report-summary-table',
    ],
  ],

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

      'preferences' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'itsiug-report-two-column',
          ],
        ],

        'tracks' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => [
              'itsiug-report-column',
            ],
          ],

          'title' => [
            '#markup' =>
              '<h3>' .
              $this->t('Track Choices') .
              '</h3>',
          ],

          'table' => [
            '#type' => 'table',
            '#attributes' => [
              'class' => [
                'itsiug-report-summary-table',
              ],
            ],
            '#header' => [
              $this->t('Track Choice'),
              $this->t('Total'),
            ],
            '#rows' => $track_rows,
            '#empty' => $this->t('No track choice data is available.'),
          ],
        ],

        'dietary' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => [
              'itsiug-report-column',
            ],
          ],

          'title' => [
            '#markup' =>
              '<h3>' .
              $this->t('Dietary Requirements') .
              '</h3>',
          ],

          'table' => [
            '#type' => 'table',
            '#attributes' => [
              'class' => [
                'itsiug-report-summary-table',
              ],
            ],
            '#header' => [
              $this->t('Dietary Requirement'),
              $this->t('Total'),
            ],
            '#rows' => $dietary_rows,
            '#empty' => $this->t('No dietary requirement data is available.'),
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

  '#attributes' => [
    'class' => [
      'itsiug-delegate-management-table',
    ],
  ],

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

      'delegate_filters' => $this->buildDelegateFilter(
        'itsiug_registration.reports',
        $search
      ),

'delegates' => [
  '#type' => 'table',

  '#attributes' => [
    'class' => [
      'itsiug-delegate-management-table',
    ],
  ],

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

      'back' => [
        '#type' => 'link',
        '#title' => $this->t('← Back to Administration'),
        '#url' => \Drupal\Core\Url::fromRoute(
          'itsiug_registration.admin'
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

  /**
   * Display the ITSIUG 2027 administration dashboard.
   */
  public function admin() {

    $account = \Drupal::currentUser();
    $access_manager = \Drupal::service('access_manager');

    return [

  'admin_page' => [
    '#type' => 'container',

    '#attached' => [
      'library' => [
        'itsiug_theme/global-styling',
      ],
    ],

    '#attributes' => [
      'class' => [
        'itsiug-admin-page',
        'itsiug-admin-dashboard',
      ],
    ],
      'heading' => [
        '#markup' => '<h1>' .
          $this->t('ITSIUG 2027 Administration') .
          '</h1>',
      ],

      'intro' => [
        '#markup' => '<p>' .
          $this->t(
            'Welcome to the ITSIUG 2027 administration area.'
          ) .
          '</p>',
      ],

      'actions' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'itsiug-admin-actions',
          ],
        ],

        'reports' => [
          '#type' => 'link',
          '#title' => $this->t('Reports'),
          '#url' => Url::fromRoute(
            'itsiug_registration.reports'
          ),
          '#access' => $access_manager->checkNamedRoute('itsiug_registration.reports', [], $account),
          '#attributes' => [
            'class' => [
              'button',
              'button--primary',
            ],
          ],
        ],

        'delegates' => [
          '#type' => 'link',
          '#title' => $this->t('Delegate Management'),
          '#url' => Url::fromRoute(
            'itsiug_registration.admin_delegates'
          ),
          '#access' => $access_manager->checkNamedRoute('itsiug_registration.admin_delegates', [], $account),
          '#attributes' => [
            'class' => [
              'button',
              'button--primary',
            ],
          ],
        ],

        'delegate_contacts' => [
          '#type' => 'link',
          '#title' => $this->t('Delegate Contact Details'),
          '#url' => Url::fromRoute(
            'itsiug_registration.admin_delegate_contacts'
          ),
          '#access' => $access_manager->checkNamedRoute('itsiug_registration.admin_delegate_contacts', [], $account),
          '#attributes' => [
            'class' => [
              'button',
              'button--primary',
            ],
          ],
        ],

        'certificates' => [
          '#type' => 'link',
          '#title' => $this->t('Certificate Management'),
          '#url' => Url::fromRoute(
            'itsiug_registration.admin_certificates'
          ),
          '#access' => $access_manager->checkNamedRoute('itsiug_registration.admin_certificates', [], $account),
          '#attributes' => [
            'class' => [
              'button',
              'button--primary',
            ],
          ],
        ],

        'badges' => [
          '#type' => 'link',
          '#title' => $this->t('Badge Management'),
          '#url' => Url::fromRoute(
            'itsiug_registration.admin_badges'
          ),
          '#access' => $access_manager->checkNamedRoute('itsiug_registration.admin_badges', [], $account),
          '#attributes' => [
            'class' => [
              'button',
              'button--primary',
            ],
          ],
        ],

        'scanner' => [
          '#type' => 'link',
          '#title' => $this->t('Conference Scanner'),
          '#url' => Url::fromRoute(
            'itsiug_registration.scanner'
          ),
          '#access' => $access_manager->checkNamedRoute('itsiug_registration.scanner', [], $account),
          '#attributes' => [
            'class' => [
              'button',
            ],
          ],
        ],

        'delegate_dashboard' => [
          '#type' => 'link',
          '#title' => $this->t('Institution Dashboard'),
          '#url' => Url::fromRoute(
            'itsiug_registration.dashboard'
          ),
          '#access' => $account->hasPermission('access itsiug admin'),
          '#attributes' => [
            'class' => [
              'button',
            ],
          ],
        ],

        ],
      ],
    ];
  }

  /**
   * Display the ITSIUG 2027 delegate management list.
   */
  public function adminDelegates() {

    $search = $this->getDelegateSearchTerm();

    $registration_ids = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'conference_registration')
      ->condition('field_conference', 2)
      ->execute();

    $rows = [];

    foreach ($registration_ids as $registration_id) {

      $registration = Node::load($registration_id);

      if (!$registration) {
        continue;
      }

      // Get delegate.
      $delegate = NULL;

      if (!$registration->get('field_delegate')->isEmpty()) {
        $delegate = $registration->get('field_delegate')->entity;
      }

      if (!$delegate) {
        continue;
      }

      // Get institution.
      $institution = NULL;

      if (!$registration->get('field_institution1')->isEmpty()) {
        $institution = $registration->get('field_institution1')->entity;
      }

      $certificate = $this->buildCertificateLink($registration);

      $institution_label = $institution ? $institution->label() : '';

      if (!$this->matchesDelegateSearch($registration, $delegate, $institution_label, $search)) {
        continue;
      }

      $name_data = $this->getDelegateSortData($delegate);

      $rows[] = [
        'registration_id' => (int) $registration->id(),
        'sort_first_name' => $name_data['first_name'],
        'sort_last_name' => $name_data['last_name'],
        'row' => [
        'delegate' => [
          'data' => [
            '#type' => 'link',
            '#title' => $delegate->label(),
            '#url' => Url::fromRoute(
              'entity.node.canonical',
              [
                'node' => $delegate->id(),
              ]
            ),
          ],
        ],

        'institution' => $institution_label,

        'registration' => $this->getRegistrationFieldLabel(
          $registration,
          'field_registration_status'
        ),

        'checkin' => $this->getRegistrationFieldLabel(
          $registration,
          'field_checkin_status'
        ),

        'monday' => $this->getRegistrationFieldLabel(
          $registration,
          'field_monday_attendance'
        ),

        'tuesday' => $this->getRegistrationFieldLabel(
          $registration,
          'field_tuesday_attendance'
        ),

        'wednesday' => $this->getRegistrationFieldLabel(
          $registration,
          'field_wednesday_attendance'
        ),

        'conference_status' => $this->getRegistrationFieldLabel(
          $registration,
          'field_conference_status'
        ),

        'certificate' => $certificate ?: [
          'data' => [
            '#markup' => $this->t('Not available'),
          ],
        ],
        ],
      ];
    }

    $this->sortDelegateRows($rows);
    $rows = array_map(
      static fn (array $row): array => $row['row'],
      $rows
    );

return [

'delegate_management' => [
  '#type' => 'container',

  '#attached' => [
    'library' => [
      'itsiug_theme/global-styling',
    ],
  ],

  '#attributes' => [
    'class' => [
      'itsiug-admin-page',
      'itsiug-delegate-management',
    ],
  ],

    'heading' => [
      '#markup' =>
        '<h1>' .
        $this->t('ITSIUG 2027 Delegate Management') .
        '</h1>',
    ],

    'intro' => [
      '#markup' =>
        '<p class="itsiug-admin-intro">' .
        $this->t(
          'Manage and review all registered ITSIUG 2027 delegates.'
        ) .
        '</p>',
    ],

    'actions' => [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'itsiug-admin-actions',
        ],
      ],

      'delegate_contacts' => [
        '#type' => 'link',
        '#title' => $this->t('Delegate Contact Details'),
        '#url' => Url::fromRoute(
          'itsiug_registration.admin_delegate_contacts'
        ),
        '#attributes' => [
          'class' => [
            'button',
            'button--primary',
          ],
        ],
      ],
    ],

    'filters' => $this->buildDelegateFilter(
      'itsiug_registration.admin_delegates',
      $search
    ),

    'delegates' => [
      '#type' => 'table',

      '#attributes' => [
        'class' => [
          'itsiug-delegate-management-table',
        ],
      ],

      '#header' => [
        $this->t('Delegate'),
        $this->t('Institution'),
        $this->t('Registration'),
        $this->t('Check-in'),
        $this->t('Monday'),
        $this->t('Tuesday'),
        $this->t('Wednesday'),
        $this->t('Conference Status'),
        $this->t('Certificate'),
      ],

      '#rows' => $rows,

      '#empty' => $this->t(
        'No ITSIUG 2027 delegates have been registered.'
      ),
    ],

    'back' => [
      '#type' => 'link',
      '#title' => $this->t('← Back to Administration'),
      '#url' => Url::fromRoute(
        'itsiug_registration.admin'
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
   * Display the ITSIUG 2027 delegate contact details report.
   */
  public function adminDelegateContacts() {

    $search = $this->getDelegateSearchTerm();

    $registration_ids = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'conference_registration')
      ->condition('field_conference', 2)
      ->execute();

    $rows = [];

    foreach ($registration_ids as $registration_id) {
      $registration = Node::load($registration_id);

      if (!$registration) {
        continue;
      }

      $delegate = $registration->get('field_delegate')->entity ?? NULL;

      if (!$delegate) {
        continue;
      }

      $institution = $registration->get('field_institution1')->entity ?? NULL;
      $institution_label = $institution ? $institution->label() : '';

      if (!$this->matchesDelegateSearch($registration, $delegate, $institution_label, $search)) {
        continue;
      }

      $name_data = $this->getDelegateSortData($delegate);
      $email = $delegate->hasField('field_email') && !$delegate->get('field_email')->isEmpty()
        ? (string) $delegate->get('field_email')->value
        : '';
      $mobile = $delegate->hasField('field_mobile') && !$delegate->get('field_mobile')->isEmpty()
        ? (string) $delegate->get('field_mobile')->value
        : '';

      $rows[] = [
        'registration_id' => (int) $registration->id(),
        'sort_first_name' => $name_data['first_name'],
        'sort_last_name' => $name_data['last_name'],
        'row' => [
          'delegate' => [
            'data' => [
              '#type' => 'link',
              '#title' => $delegate->label(),
              '#url' => Url::fromRoute(
                'entity.node.canonical',
                [
                  'node' => $delegate->id(),
                ]
              ),
            ],
          ],
          'institution' => $institution_label,
          'email' => $email,
          'mobile' => $mobile,
        ],
      ];
    }

    $this->sortDelegateRows($rows);
    $rows = array_map(
      static fn (array $row): array => $row['row'],
      $rows
    );

    return [

  'delegate_contacts' => [
    '#type' => 'container',

    '#attached' => [
      'library' => [
        'itsiug_theme/global-styling',
      ],
    ],

    '#attributes' => [
      'class' => [
        'itsiug-admin-page',
        'itsiug-delegate-management',
        'itsiug-delegate-contacts',
      ],
    ],

      'heading' => [
        '#markup' =>
          '<h1>' .
          $this->t('ITSIUG 2027 Delegate Contact Details') .
          '</h1>',
      ],

      'intro' => [
        '#markup' =>
          '<p class="itsiug-admin-intro">' .
          $this->t(
            'Review delegate contact details for ITSIUG 2027 registrations.'
          ) .
          '</p>',
      ],

      'filters' => $this->buildDelegateFilter(
        'itsiug_registration.admin_delegate_contacts',
        $search
      ),

      'delegates' => [
        '#type' => 'table',

        '#attributes' => [
          'class' => [
            'itsiug-delegate-management-table',
          ],
        ],

        '#header' => [
          $this->t('Delegate'),
          $this->t('Institution'),
          $this->t('Email'),
          $this->t('Mobile'),
        ],

        '#rows' => $rows,

        '#empty' => $this->t(
          'No ITSIUG 2027 delegate contact details are available.'
        ),
      ],

      'back' => [
        '#type' => 'link',
        '#title' => $this->t('← Back to Administration'),
        '#url' => Url::fromRoute(
          'itsiug_registration.admin'
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
   * Display ITSIUG 2027 certificate management.
   */
  public function adminCertificates() {

  $search = $this->getDelegateSearchTerm();

  $registration_ids = \Drupal::entityQuery('node')
    ->accessCheck(FALSE)
    ->condition('type', 'conference_registration')
    ->condition('field_conference', 2)
    ->execute();

  $rows = [];

  foreach ($registration_ids as $registration_id) {

    $registration = Node::load($registration_id);

    if (!$registration) {
      continue;
    }

    // Get delegate.
    $delegate = NULL;

    if (!$registration->get('field_delegate')->isEmpty()) {
      $delegate = $registration->get('field_delegate')->entity;
    }

    if (!$delegate) {
      continue;
    }

    // Get institution.
    $institution = NULL;

    if (!$registration->get('field_institution1')->isEmpty()) {
      $institution = $registration->get('field_institution1')->entity;
    }

    $institution_label = $institution ? $institution->label() : '';

    if (!$this->matchesDelegateSearch($registration, $delegate, $institution_label, $search)) {
      continue;
    }

    $name_data = $this->getDelegateSortData($delegate);

    // ----------------------------------------------------------
    // Certificate eligibility.
    // ----------------------------------------------------------

    $eligibility_value = '';
    $eligibility = '';

    if (!$registration
      ->get('field_certificate_eligibility')
      ->isEmpty()
    ) {

      $eligibility_value = $registration
        ->get('field_certificate_eligibility')
        ->value;

      $allowed_values = $registration
        ->get('field_certificate_eligibility')
        ->first()
        ->getFieldDefinition()
        ->getSetting('allowed_values');

      $eligibility =
        $allowed_values[$eligibility_value]
        ?? $eligibility_value;
    }

    // ----------------------------------------------------------
    // Certificate status.
    // ----------------------------------------------------------

    $certificate_status_value = '';
    $certificate_status = '';

    if (!$registration
      ->get('field_certificate_status')
      ->isEmpty()
    ) {

      $certificate_status_value = $registration
        ->get('field_certificate_status')
        ->value;

      $allowed_values = $registration
        ->get('field_certificate_status')
        ->first()
        ->getFieldDefinition()
        ->getSetting('allowed_values');

      $certificate_status =
        $allowed_values[$certificate_status_value]
        ?? $certificate_status_value;
    }

    // ----------------------------------------------------------
    // Existing certificate download link.
    // ----------------------------------------------------------

    $certificate = $this->buildCertificateLink(
      $registration
    );

    // ----------------------------------------------------------
    // Certificate action.
    // ----------------------------------------------------------

    $certificate_action = $certificate;

    if (!$certificate_action) {

      if ($eligibility_value === 'eligible') {

        $certificate_action = [
          'data' => [
            '#type' => 'link',
            '#title' => $this->t('Generate Certificate'),
            '#url' => Url::fromRoute(
              'itsiug_registration.admin_certificate_generate',
              [
                'registration' => $registration->id(),
              ]
            ),
            '#attributes' => [
              'class' => [
                'button',
                'button--primary',
              ],
            ],
          ],
        ];

      }
      else {

        $certificate_action = [
          'data' => [
            '#markup' => $this->t('Not generated'),
          ],
        ];

      }
    }

    // ----------------------------------------------------------
    // Attendance status.
    // ----------------------------------------------------------

    $monday_status = '';

    if (!$registration
      ->get('field_monday_attendance')
      ->isEmpty()
    ) {

      $monday_value = $registration
        ->get('field_monday_attendance')
        ->value;

      $monday_status = match ($monday_value) {
        'present' => $this->t('Present'),
        'not_present' => $this->t('Not Present'),
        default => $monday_value,
      };
    }

    $tuesday_status = '';

    if (!$registration
      ->get('field_tuesday_attendance')
      ->isEmpty()
    ) {

      $tuesday_value = $registration
        ->get('field_tuesday_attendance')
        ->value;

      $tuesday_status = match ($tuesday_value) {
        'present' => $this->t('Present'),
        'not_present' => $this->t('Not Present'),
        default => $tuesday_value,
      };
    }

    $wednesday_status = '';

    if (!$registration
      ->get('field_wednesday_attendance')
      ->isEmpty()
    ) {

      $wednesday_value = $registration
        ->get('field_wednesday_attendance')
        ->value;

      $wednesday_status = match ($wednesday_value) {
        'present' => $this->t('Present'),
        'not_present' => $this->t('Not Present'),
        default => $wednesday_value,
      };
    }

    // ----------------------------------------------------------
    // Build table row.
    // ----------------------------------------------------------

    $rows[] = [
      'registration_id' => (int) $registration->id(),
      'sort_first_name' => $name_data['first_name'],
      'sort_last_name' => $name_data['last_name'],
      'row' => [

      'delegate' => [
        'data' => [
          '#type' => 'link',
          '#title' => $delegate->label(),
          '#url' => Url::fromRoute(
            'entity.node.canonical',
            [
              'node' => $delegate->id(),
            ]
          ),
        ],
      ],

      'institution' => $institution_label,

      'monday' => $monday_status,

      'tuesday' => $tuesday_status,

      'wednesday' => $wednesday_status,

      'eligibility' => $eligibility,

      'certificate_status' => $certificate_status,

      'certificate' => $certificate_action,

      ],
    ];
  }

  $this->sortDelegateRows($rows);
  $rows = array_map(
    static fn (array $row): array => $row['row'],
    $rows
  );

return [

  'certificate_management' => [
    '#type' => 'container',

    '#attached' => [
      'library' => [
        'itsiug_theme/global-styling',
      ],
    ],
    '#attributes' => [
      'class' => [
        'itsiug-admin-page',
        'itsiug-certificate-management',
      ],
    ],

    'heading' => [
      '#markup' =>
        '<h1>' .
        $this->t('ITSIUG 2027 Certificate Management') .
        '</h1>',
    ],

    'intro' => [
      '#markup' =>
        '<p class="itsiug-admin-intro">' .
        $this->t(
          'Review certificate eligibility and download or generate certificates.'
        ) .
        '</p>',
    ],

    'filters' => $this->buildDelegateFilter(
      'itsiug_registration.admin_certificates',
      $search
    ),

    'certificates' => [
      '#type' => 'table',

      '#attributes' => [
        'class' => [
          'itsiug-delegate-management-table',
        ],
      ],

      '#header' => [
        $this->t('Delegate'),
        $this->t('Institution'),
        $this->t('Monday'),
        $this->t('Tuesday'),
        $this->t('Wednesday'),
        $this->t('Eligibility'),
        $this->t('Certificate Status'),
        $this->t('Certificate'),
      ],

      '#rows' => $rows,

      '#empty' => $this->t(
        'No ITSIUG 2027 registrations were found.'
      ),
    ],

    'back' => [
      '#type' => 'link',
      '#title' => $this->t('← Back to Administration'),
      '#url' => Url::fromRoute(
        'itsiug_registration.admin'
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
   * Generate a certificate from the Admin area.
   */
  public function adminGenerateCertificate($registration) {

    $node = Node::load($registration);

    if (!$node) {
      $this->messenger()->addError(
        $this->t('The registration could not be found.')
      );

      return new RedirectResponse(
        Url::fromRoute(
          'itsiug_registration.admin_certificates'
        )->toString()
      );
    }

    if ($node->bundle() !== 'conference_registration') {
      $this->messenger()->addError(
        $this->t(
          'The supplied node is not a conference registration.'
        )
      );

      return new RedirectResponse(
        Url::fromRoute(
          'itsiug_registration.admin_certificates'
        )->toString()
      );
    }

    try {

      /** @var \Drupal\itsiug_registration\Service\CertificateGenerator $generator */
      $generator = \Drupal::service(
        'itsiug_registration.certificate_generator'
      );

      $result = $generator->generate($node);

      if (!empty($result['success'])) {

        $this->messenger()->addStatus(
          $this->t(
            'Certificate generated successfully for @delegate.',
            [
              '@delegate' => $node
                ->get('field_delegate')
                ->entity
                ->label(),
            ]
          )
        );

      }
      else {

        $this->messenger()->addWarning(
          $this->t(
            $result['message'] ?? 'The certificate could not be generated.'
          )
        );

      }

    }
    catch (\Throwable $e) {

      \Drupal::logger('itsiug_registration')->error(
        'Certificate generation failed for registration @registration: @message',
        [
          '@registration' => $node->id(),
          '@message' => $e->getMessage(),
        ]
      );

      $this->messenger()->addError(
        $this->t(
          'Certificate generation failed. Please check the Drupal log.'
        )
      );
    }

    return new RedirectResponse(
      Url::fromRoute(
        'itsiug_registration.admin_certificates'
      )->toString()
    );
  }

  /**
   * Generate a badge from the Admin area.
   */
  public function adminGenerateBadge($registration) {

    $node = Node::load($registration);

    if (!$node) {
      $this->messenger()->addError(
        $this->t('The registration could not be found.')
      );

      return new RedirectResponse(
        Url::fromRoute(
          'itsiug_registration.admin_badges'
        )->toString()
      );
    }

    if ($node->bundle() !== 'conference_registration') {
      $this->messenger()->addError(
        $this->t(
          'The supplied node is not a conference registration.'
        )
      );

      return new RedirectResponse(
        Url::fromRoute(
          'itsiug_registration.admin_badges'
        )->toString()
      );
    }

    try {

      /** @var \Drupal\itsiug_registration\Service\BadgeGenerator $generator */
      $generator = \Drupal::service(
        'itsiug_registration.badge_generator'
      );

      $result = $generator->generate($node);

      if (!empty($result['success'])) {

        $this->messenger()->addStatus(
          $this->t(
            'Badge generated successfully for @delegate.',
            [
              '@delegate' => $node
                ->get('field_delegate')
                ->entity
                ->label(),
            ]
          )
        );

      }
      else {

        $this->messenger()->addWarning(
          $this->t(
            $result['message'] ?? 'The badge could not be generated.'
          )
        );

      }

    }
    catch (\Throwable $e) {

      \Drupal::logger('itsiug_registration')->error(
        'Badge generation failed for registration @registration: @message',
        [
          '@registration' => $node->id(),
          '@message' => $e->getMessage(),
        ]
      );

      $this->messenger()->addError(
        $this->t(
          'Badge generation failed. Please check the Drupal log.'
        )
      );
    }

    return new RedirectResponse(
      Url::fromRoute(
        'itsiug_registration.admin_badges'
      )->toString()
    );
  }

  /**
   * Generate one multi-page PDF for all conference badges.
   */
  public function adminDownloadBadgesBulk() {

    try {

      /** @var \Drupal\itsiug_registration\Service\BadgeGenerator $generator */
      $generator = \Drupal::service(
        'itsiug_registration.badge_generator'
      );

      $result = $generator->generateBulk(2);

      if (empty($result['success'])) {
        $this->messenger()->addWarning(
          $this->t(
            $result['message'] ?? 'Bulk badge PDF could not be generated.'
          )
        );

        return new RedirectResponse(
          Url::fromRoute(
            'itsiug_registration.admin_badges'
          )->toString()
        );
      }

      $file_url = \Drupal::service('file_url_generator')
        ->generateAbsoluteString($result['uri']);

      return new RedirectResponse($file_url);

    }
    catch (\Throwable $e) {

      \Drupal::logger('itsiug_registration')->error(
        'Bulk badge generation failed: @message',
        [
          '@message' => $e->getMessage(),
        ]
      );

      $this->messenger()->addError(
        $this->t(
          'Bulk badge generation failed. Please check the Drupal log.'
        )
      );

      return new RedirectResponse(
        Url::fromRoute(
          'itsiug_registration.admin_badges'
        )->toString()
      );
    }
  }

  /**
   * Get the current delegate search term from the request query.
   */
  private function getDelegateSearchTerm(): string {

    return trim((string) \Drupal::request()->query->get('search', ''));
  }

  /**
   * Build a reusable GET filter form for admin listing pages.
   */
  private function buildDelegateFilter(string $route_name, string $search): array {

    $action = Url::fromRoute($route_name)->toString();
    $clear_url = Url::fromRoute($route_name)->toString();
    $input_id = Html::getId($route_name . '-delegate-search');

    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'itsiug-admin-filters',
          'itsiug-badge-filters',
        ],
      ],
      'form' => [
        '#type' => 'inline_template',
        '#template' => '<form method="get" action="{{ action }}" class="itsiug-admin-filter-form"><div class="form-item"><label for="{{ input_id }}">{{ label }}</label><input type="text" id="{{ input_id }}" name="search" value="{{ search }}" size="40" class="itsiug-badge-filter-input" /><div class="description">{{ description }}</div></div><div class="form-actions"><input type="submit" value="{{ apply_label }}" class="button button--primary" /><a href="{{ clear_url }}" class="button">{{ clear_label }}</a></div></form>',
        '#context' => [
          'action' => $action,
          'clear_url' => $clear_url,
          'input_id' => $input_id,
          'search' => $search,
          'label' => $this->t('Find Delegate'),
          'description' => $this->t('Type any part of first name, last name, QR ID, email, or institution.'),
          'apply_label' => $this->t('Apply Filter'),
          'clear_label' => $this->t('Clear Filter'),
        ],
      ],
    ];
  }

  /**
   * Extract delegate sort names for alphabetical ordering.
   */
  private function getDelegateSortData(Node $delegate): array {

    $first_name = '';
    if ($delegate->hasField('field_first_name') && !$delegate->get('field_first_name')->isEmpty()) {
      $first_name = trim((string) $delegate->get('field_first_name')->value);
    }

    $last_name = '';
    if ($delegate->hasField('field_last_name') && !$delegate->get('field_last_name')->isEmpty()) {
      $last_name = trim((string) $delegate->get('field_last_name')->value);
    }

    $label = trim((string) $delegate->label());

    return [
      'first_name' => $first_name !== '' ? $first_name : $label,
      'last_name' => $last_name !== '' ? $last_name : $label,
      'label' => $label,
    ];
  }

  /**
   * Determine whether a registration matches a delegate search term.
   */
  private function matchesDelegateSearch(
    Node $registration,
    Node $delegate,
    string $institution_name,
    string $search
  ): bool {

    if ($search === '') {
      return TRUE;
    }

    $name_data = $this->getDelegateSortData($delegate);
    $email = $delegate->hasField('field_email') && !$delegate->get('field_email')->isEmpty()
      ? trim((string) $delegate->get('field_email')->value)
      : '';
    $qr_code = $registration->hasField('field_qr_code') && !$registration->get('field_qr_code')->isEmpty()
      ? trim((string) $registration->get('field_qr_code')->value)
      : '';

    $haystack = implode(' ', [
      $name_data['label'],
      $name_data['first_name'],
      $name_data['last_name'],
      $institution_name,
      $email,
      $qr_code,
    ]);

    return stripos($haystack, $search) !== FALSE;
  }

  /**
   * Sort prepared rows alphabetically by last name, then first name.
   */
  private function sortDelegateRows(array &$rows): void {

    usort($rows, static function (array $left, array $right): int {
      $last_name_compare = strcasecmp(
        (string) ($left['sort_last_name'] ?? ''),
        (string) ($right['sort_last_name'] ?? '')
      );

      if ($last_name_compare !== 0) {
        return $last_name_compare;
      }

      $first_name_compare = strcasecmp(
        (string) ($left['sort_first_name'] ?? ''),
        (string) ($right['sort_first_name'] ?? '')
      );

      if ($first_name_compare !== 0) {
        return $first_name_compare;
      }

      return ((int) ($left['registration_id'] ?? 0)) <=> ((int) ($right['registration_id'] ?? 0));
    });
  }

  /**
   * Resolve a list-field label from a registration field.
   */
  private function getRegistrationFieldLabel(Node $registration, string $field_name): string {

    if (!$registration->hasField($field_name) || $registration->get($field_name)->isEmpty()) {
      return '';
    }

    $value = (string) $registration->get($field_name)->value;
    $allowed_values = $registration
      ->get($field_name)
      ->first()
      ->getFieldDefinition()
      ->getSetting('allowed_values');

    if (isset($allowed_values[$value])) {
      return (string) $allowed_values[$value];
    }

    if ($field_name === 'field_conference_status') {
      $status_options = itsiug_conference_status_options();
      if (isset($status_options[$value])) {
        return $status_options[$value];
      }
    }

    return strtoupper(str_replace('_', ' ', $value));
  }

  /**
   * Get a referenced delegate field label or a fallback value.
   */
  private function getDelegateReferenceLabel(Node $delegate, string $field_name, string $fallback): string {

    if (!$delegate->hasField($field_name) || $delegate->get($field_name)->isEmpty()) {
      return $fallback;
    }

    $entity = $delegate->get($field_name)->entity;

    if ($entity) {
      return (string) $entity->label();
    }

    return $fallback;
  }
}