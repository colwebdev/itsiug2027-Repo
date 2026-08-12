<?php

namespace Drupal\itsiug_registration\Service;

use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Url;
use Drupal\file\FileRepositoryInterface;
use Drupal\node\NodeInterface;
use TCPDF;

/**
 * Generates ITSIUG conference badges.
 */
class BadgeGenerator {

  /**
   * Conference field for the badge background image/media reference.
   */
  private const BADGE_BACKGROUND_FIELD = 'field_badge_background_image';

  /**
   * Default background image for badges while testing.
   */
  private const BADGE_BACKGROUND_URI = 'themes/contrib/mercury/components/hero-billboard/assets/luke-chesser-pJadQetzTkI-unsplash.jpg';

  /**
   * The file repository.
   *
   * @var \Drupal\file\FileRepositoryInterface
   */
  protected FileRepositoryInterface $fileRepository;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected FileSystemInterface $fileSystem;

  /**
   * Constructs the badge generator.
   */
  public function __construct(
    FileRepositoryInterface $file_repository,
    FileSystemInterface $file_system
  ) {
    $this->fileRepository = $file_repository;
    $this->fileSystem = $file_system;
  }

  /**
   * Generates a badge for a conference registration.
   */
  public function generate(NodeInterface $registration): array {

    $page_data = $this->buildBadgePageData($registration);

    if (!$page_data['success']) {
      return $page_data;
    }

    $delegate = $page_data['delegate'];
    $badge_number = $page_data['badge_number'];
    $badge_url = $page_data['badge_url'];
    $badge_sequence_label = $this->getIndividualBadgeSequenceLabel(
      $registration,
      $badge_number
    );

    if ($badge_sequence_label !== NULL && $badge_sequence_label !== '') {
      $page_data['badge_sequence_label'] = $badge_sequence_label;
    }

    $pdf = new TCPDF(
      'P',
      'mm',
      [98, 120],
      TRUE,
      'UTF-8',
      FALSE
    );

    $pdf->SetCreator('ITSIUG 2027');
    $pdf->SetAuthor('ITSIUG');
    $pdf->SetTitle('Conference Badge - ' . $delegate->label());
    $pdf->SetSubject('ITSIUG 2027 Conference Badge');

    $pdf->setPrintHeader(FALSE);
    $pdf->setPrintFooter(FALSE);
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetAutoPageBreak(FALSE);
    $pdf->AddPage();

    $this->renderBadgePage($pdf, $page_data);

    $pdf_data = $pdf->Output('', 'S');

    $year = date('Y');
    $directory = 'public://certificates/' . $year . '/Badges';

    $this->fileSystem->prepareDirectory(
      $directory,
      FileSystemInterface::CREATE_DIRECTORY |
      FileSystemInterface::MODIFY_PERMISSIONS
    );

    $safe_name = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $delegate->label());

    $filename = 'ITSIUG-2027-BADGE-' . $registration->id() . '-' . $safe_name . '.pdf';

    $destination = $directory . '/' . $filename;

    $file = $this->fileRepository->writeData(
      $pdf_data,
      $destination,
      FileExists::Replace
    );

    $file->setPermanent();
    $file->save();

    $registration->set(
      'field_badge_file',
      [
        'target_id' => $file->id(),
        'display' => 1,
        'description' => 'ITSIUG 2027 Conference Badge',
      ]
    );

    $registration->set('field_badge_status', 'generated');
    $registration->save();

    return [
      'success' => TRUE,
      'message' => 'Badge generated successfully.',
      'file_id' => $file->id(),
      'filename' => $filename,
      'uri' => $file->getFileUri(),
      'badge_number' => $badge_number,
      'badge_url' => $badge_url,
    ];
  }

  /**
   * Generates one multi-page PDF containing all conference badges.
   */
  public function generateBulk(int $conference_id = 2): array {

    $registration_ids = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'conference_registration')
      ->condition('field_conference', $conference_id)
      ->execute();

    if (empty($registration_ids)) {
      return [
        'success' => FALSE,
        'message' => 'No registrations were found for bulk badge generation.',
      ];
    }

    $pdf = new TCPDF(
      'P',
      'mm',
      [98, 120],
      TRUE,
      'UTF-8',
      FALSE
    );

    $pdf->SetCreator('ITSIUG 2027');
    $pdf->SetAuthor('ITSIUG');
    $pdf->SetTitle('ITSIUG 2027 Bulk Badge Pack');
    $pdf->SetSubject('ITSIUG 2027 Conference Badges');
    $pdf->setPrintHeader(FALSE);
    $pdf->setPrintFooter(FALSE);
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetAutoPageBreak(FALSE);

    $badge_pages = [];

    foreach ($registration_ids as $registration_id) {
      $registration = \Drupal\node\Entity\Node::load($registration_id);

      if (!$registration) {
        continue;
      }

      // Skip cancelled registrations in final print pack.
      if (
        $registration->hasField('field_registration_status') &&
        !$registration->get('field_registration_status')->isEmpty() &&
        $registration->get('field_registration_status')->value === 'cancelled'
      ) {
        continue;
      }

      $page_data = $this->buildBadgePageData($registration);

      if (empty($page_data['success'])) {
        continue;
      }

      $badge_pages[] = $page_data;
    }

    if (empty($badge_pages)) {
      return [
        'success' => FALSE,
        'message' => 'No valid badges could be generated for the bulk PDF.',
      ];
    }

    usort($badge_pages, static function (array $left, array $right): int {
      $left_last_name = strtolower((string) ($left['sort_last_name'] ?? ''));
      $right_last_name = strtolower((string) ($right['sort_last_name'] ?? ''));

      if ($left_last_name !== $right_last_name) {
        return $left_last_name <=> $right_last_name;
      }

      $left_first_name = strtolower((string) ($left['sort_first_name'] ?? ''));
      $right_first_name = strtolower((string) ($right['sort_first_name'] ?? ''));

      if ($left_first_name !== $right_first_name) {
        return $left_first_name <=> $right_first_name;
      }

      $left_badge_number = strtolower((string) ($left['badge_number'] ?? ''));
      $right_badge_number = strtolower((string) ($right['badge_number'] ?? ''));

      if ($left_badge_number !== $right_badge_number) {
        return $left_badge_number <=> $right_badge_number;
      }

      return ((int) ($left['registration_id'] ?? 0)) <=> ((int) ($right['registration_id'] ?? 0));
    });

    $count = count($badge_pages);

    foreach ($badge_pages as $index => $page_data) {
      $page_data['badge_sequence_label'] = sprintf('%03d', $index + 1);
      $pdf->AddPage();
      $this->renderBadgePage($pdf, $page_data);
    }

    $bulk_registration_ids = array_values(array_map(
      static fn (array $page): int => (int) ($page['registration_id'] ?? 0),
      $badge_pages
    ));
    $bulk_registration_ids = array_values(array_filter(
      $bulk_registration_ids,
      static fn (int $id): bool => $id > 0
    ));

    $state = \Drupal::state();
    $state_base = 'itsiug_registration.badges.' . $conference_id . '.';
    $state->set($state_base . 'bulk_finalized', TRUE);
    $state->set($state_base . 'bulk_registration_ids', $bulk_registration_ids);
    $state->set($state_base . 'late_map', []);
    $state->set($state_base . 'late_last', 0);

    $pdf_data = $pdf->Output('', 'S');

    $year = date('Y');
    $directory = 'public://certificates/' . $year . '/Badges';

    $this->fileSystem->prepareDirectory(
      $directory,
      FileSystemInterface::CREATE_DIRECTORY |
      FileSystemInterface::MODIFY_PERMISSIONS
    );

    $filename = 'ITSIUG-2027-BADGES-BULK-' . date('Ymd-His') . '.pdf';
    $destination = $directory . '/' . $filename;

    $file = $this->fileRepository->writeData(
      $pdf_data,
      $destination,
      FileExists::Replace
    );

    $file->setPermanent();
    $file->save();

    return [
      'success' => TRUE,
      'message' => 'Bulk badge PDF generated successfully.',
      'file_id' => $file->id(),
      'filename' => $filename,
      'uri' => $file->getFileUri(),
      'count' => $count,
    ];
  }

  /**
   * Reset only late badge numbering for a conference.
   */
  public function resetLateNumbering(int $conference_id = 2): array {

    if ($conference_id <= 0) {
      return [
        'success' => FALSE,
        'message' => 'Invalid conference ID.',
      ];
    }

    $state = \Drupal::state();
    $state_base = 'itsiug_registration.badges.' . $conference_id . '.';

    $state->set($state_base . 'late_map', []);
    $state->set($state_base . 'late_last', 0);

    return [
      'success' => TRUE,
      'message' => 'Late badge numbering reset.',
    ];
  }

  /**
   * Build all page data needed for badge rendering.
   */
  private function buildBadgePageData(NodeInterface $registration): array {

    if ($registration->bundle() !== 'conference_registration') {
      return [
        'success' => FALSE,
        'message' => 'The supplied node is not a conference registration.',
      ];
    }

    if ($registration->get('field_delegate')->isEmpty()) {
      return [
        'success' => FALSE,
        'message' => 'The registration has no delegate.',
      ];
    }

    $delegate = $registration->get('field_delegate')->entity;

    if (!$delegate) {
      return [
        'success' => FALSE,
        'message' => 'The delegate could not be loaded.',
      ];
    }

    if ($registration->get('field_conference')->isEmpty()) {
      return [
        'success' => FALSE,
        'message' => 'The registration has no conference.',
      ];
    }

    $conference = $registration->get('field_conference')->entity;

    if (!$conference) {
      return [
        'success' => FALSE,
        'message' => 'The conference could not be loaded.',
      ];
    }

    $conference_name = $conference->label();

    $badge_number = '';

    if (!$registration->get('field_qr_code')->isEmpty()) {
      $badge_number = (string) $registration->get('field_qr_code')->value;
    }

    if ($badge_number === '') {
      $badge_number = 'ITSIUG-' . $registration->id();
    }

    $badge_url = Url::fromRoute(
      'itsiug_registration.badge_scanner',
      [],
      [
        'query' => [
          'qr' => $badge_number,
        ],
        'absolute' => TRUE,
      ]
    )->toString();

    $logo_path = NULL;

    if (!$conference->get('field_conference_logo')->isEmpty()) {
      $logo_path = $this->resolveConferenceImagePath(
        $conference,
        'field_conference_logo',
        NULL
      );
    }

    $background_path = $this->resolveConferenceImagePath(
      $conference,
      self::BADGE_BACKGROUND_FIELD,
      self::BADGE_BACKGROUND_URI
    );

    $institution_name = '';

    if (!$registration->get('field_institution1')->isEmpty()) {
      $institution = $registration->get('field_institution1')->entity;

      if ($institution) {
        $institution_name = $institution->label();
      }
    }

    $first_name = '';
    if ($delegate->hasField('field_first_name') && !$delegate->get('field_first_name')->isEmpty()) {
      $first_name = trim((string) $delegate->get('field_first_name')->value);
    }

    $last_name = '';
    if ($delegate->hasField('field_last_name') && !$delegate->get('field_last_name')->isEmpty()) {
      $last_name = trim((string) $delegate->get('field_last_name')->value);
    }

    $title_label = '';
    if ($delegate->hasField('field_title') && !$delegate->get('field_title')->isEmpty()) {
      $title_item = $delegate->get('field_title')->first();
      $title_value = (string) $title_item->value;
      $title_allowed_values = $title_item->getFieldDefinition()->getSetting('allowed_values');
      $title_label = $title_allowed_values[$title_value] ?? $title_value;
    }

    $title_last_name = trim(trim($title_label) . ' ' . $last_name);

    if ($first_name === '') {
      $first_name = $delegate->label();
    }

    if ($title_last_name === '') {
      $title_last_name = $delegate->label();
    }

    $job_title = '';
    if ($delegate->hasField('field_job_title') && !$delegate->get('field_job_title')->isEmpty()) {
      $job_title = trim((string) $delegate->get('field_job_title')->value);
    }

    $sort_first_name = $first_name;
    $sort_last_name = $last_name;

    if ($sort_last_name === '') {
      $sort_last_name = $delegate->label();
    }

    if ($sort_first_name === '') {
      $sort_first_name = $delegate->label();
    }

    $conference_status_value = 'delegate';
    $conference_status_label = 'DELEGATE';

    if (!$registration->get('field_conference_status')->isEmpty()) {
      $conference_status_value = (string) $registration
        ->get('field_conference_status')
        ->value;

      $allowed_values = $registration
        ->get('field_conference_status')
        ->first()
        ->getFieldDefinition()
        ->getSetting('allowed_values');

      $conference_status_label = $allowed_values[$conference_status_value]
        ?? strtoupper(str_replace('_', ' ', $conference_status_value));
    }

    return [
      'success' => TRUE,
      'delegate' => $delegate,
      'registration_id' => (int) $registration->id(),
      'conference_name' => $conference_name,
      'badge_number' => $badge_number,
      'badge_url' => $badge_url,
      'logo_path' => $logo_path,
      'background_path' => $background_path,
      'first_name' => $first_name,
      'sort_first_name' => $sort_first_name,
      'sort_last_name' => $sort_last_name,
      'title_last_name' => $title_last_name,
      'institution_name' => $institution_name,
      'job_title' => $job_title,
      'conference_status_label' => $conference_status_label,
    ];
  }

  /**
   * Render one badge page on the supplied PDF object.
   */
  private function renderBadgePage(TCPDF $pdf, array $page_data): void {

    $conference_name = (string) $page_data['conference_name'];
    $badge_number = (string) $page_data['badge_number'];
    $badge_sequence_label = isset($page_data['badge_sequence_label'])
      ? trim((string) $page_data['badge_sequence_label'])
      : '';
    $logo_path = $page_data['logo_path'];
    $background_path = $page_data['background_path'];
    $first_name = (string) $page_data['first_name'];
    $title_last_name = (string) $page_data['title_last_name'];
    $institution_name = (string) $page_data['institution_name'];
    $job_title = (string) $page_data['job_title'];
    $conference_status_label = (string) $page_data['conference_status_label'];
    $badge_url = (string) $page_data['badge_url'];

    $navy = [31, 75, 107];
    $cyan = [91, 191, 217];
    $grey = [78, 78, 78];

    if ($background_path && file_exists($background_path)) {
      $pdf->SetAlpha(0.38);
      $pdf->Image(
        $background_path,
        0,
        0,
        98,
        120,
        '',
        '',
        '',
        FALSE,
        300,
        '',
        FALSE,
        FALSE,
        0,
        FALSE,
        FALSE,
        FALSE
      );
      $pdf->SetAlpha(1);
    }

    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetAlpha(0.88);
    $pdf->Rect(4, 4, 90, 34, 'F');
    $pdf->SetAlpha(1);

    $pdf->SetFillColor($cyan[0], $cyan[1], $cyan[2]);
    $pdf->SetAlpha(0.18);
    $pdf->Rect(0, 0, 56, 120, 'F');
    $pdf->SetAlpha(1);

    $pdf->SetDrawColor($navy[0], $navy[1], $navy[2]);
    $pdf->SetLineWidth(0.35);
    $pdf->Rect(1.5, 1.5, 95, 117);

    $pdf->SetTextColor($navy[0], $navy[1], $navy[2]);

    if ($logo_path && file_exists($logo_path)) {
      $pdf->Image(
        $logo_path,
        26,
        6,
        46,
        0,
        '',
        '',
        '',
        TRUE,
        300,
        '',
        FALSE,
        FALSE,
        0,
        FALSE,
        FALSE,
        FALSE
      );
    }

    if ($badge_sequence_label !== '') {
      $pdf->SetFont('helvetica', '', 7.5);
      $pdf->SetXY(70, 5);
      $pdf->Cell(22, 5, 'Badge No: ' . $badge_sequence_label, 0, 0, 'R');
    }
    else {
      $pdf->SetFont('helvetica', '', 7);
      $pdf->SetXY(68, 5);
      $pdf->Cell(24, 5, 'QR Code ID: ' . $badge_number, 0, 0, 'R');
    }

    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetXY(8, 23);
    $pdf->Cell(82, 8, $first_name, 0, 0, 'C');

    $pdf->SetTextColor($grey[0], $grey[1], $grey[2]);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->SetXY(8, 33);
    $pdf->Cell(82, 6, $title_last_name, 0, 0, 'C');

    $pdf->SetDrawColor($navy[0], $navy[1], $navy[2]);
    $pdf->SetLineWidth(0.25);
    $pdf->Line(7, 44, 91, 44);

    $pdf->SetTextColor($navy[0], $navy[1], $navy[2]);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetXY(8, 47);
    $pdf->Cell(82, 7, $institution_name ?: $conference_name, 0, 0, 'C');

    $pdf->SetTextColor($grey[0], $grey[1], $grey[2]);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetXY(8, 55);
    $pdf->Cell(82, 6, $job_title, 0, 0, 'C');

    $pdf->SetDrawColor($navy[0], $navy[1], $navy[2]);
    $pdf->SetLineWidth(0.25);
    $pdf->Line(7, 63, 91, 63);

    $pdf->SetTextColor($navy[0], $navy[1], $navy[2]);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetXY(8, 68);
    $pdf->Cell(82, 7, $conference_status_label, 0, 0, 'C');

    $pdf->SetTextColor($grey[0], $grey[1], $grey[2]);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetXY(8, 75);
    $pdf->Cell(82, 6, 'ITSIUG 2027', 0, 0, 'C');

    $pdf->SetTextColor($grey[0], $grey[1], $grey[2]);
    $pdf->SetFont('helvetica', '', 7.5);
    $pdf->SetXY(7, 87);
    $pdf->MultiCell(
      54,
      4,
      "Present this badge at the\nregistration desk for check- in,\nand scan it yourself dailyto record\nyour Attendance",
      0,
      'L',
      FALSE,
      1,
      '',
      '',
      TRUE,
      0,
      FALSE,
      TRUE,
      0,
      'T'
    );

    // Keep the QR compact and anchored at the lower-right corner.
    $qr_size = 26;
    $qr_x = 64;
    $qr_y = 86;
    $qr_padding = 1;

    $pdf->SetFillColor(255, 255, 255);
    $pdf->Rect(
      $qr_x - $qr_padding,
      $qr_y - $qr_padding,
      $qr_size + ($qr_padding * 2),
      $qr_size + ($qr_padding * 2),
      'F'
    );

    $pdf->write2DBarcode(
      $badge_url,
      'QRCODE,H',
      $qr_x,
      $qr_y,
      $qr_size,
      $qr_size,
      [
        'border' => FALSE,
        'padding' => 0,
        'fgcolor' => [0, 0, 0],
        'bgcolor' => FALSE,
      ],
      'N'
    );

    $pdf->SetDrawColor($navy[0], $navy[1], $navy[2]);
    $pdf->Rect($qr_x - $qr_padding, $qr_y - $qr_padding, $qr_size + ($qr_padding * 2), $qr_size + ($qr_padding * 2));

    if ($badge_sequence_label !== '') {
      $pdf->SetTextColor($grey[0], $grey[1], $grey[2]);
      $pdf->SetFont('helvetica', '', 7);
      $pdf->SetXY(62, 113.5);
      $pdf->Cell(30, 4, $badge_number, 0, 0, 'R');
    }
  }

  /**
   * Return badge sequence label for individual badge generation.
   */
  private function getIndividualBadgeSequenceLabel(
    NodeInterface $registration,
    string $badge_number
  ): ?string {

    if ($registration->get('field_conference')->isEmpty()) {
      return $this->buildDefaultBadgeSequenceLabel($badge_number);
    }

    $conference_id = (int) $registration->get('field_conference')->target_id;

    if ($conference_id <= 0) {
      return $this->buildDefaultBadgeSequenceLabel($badge_number);
    }

    $state = \Drupal::state();
    $state_base = 'itsiug_registration.badges.' . $conference_id . '.';
    $bulk_finalized = (bool) $state->get($state_base . 'bulk_finalized', FALSE);

    if (!$bulk_finalized) {
      return $this->buildDefaultBadgeSequenceLabel($badge_number);
    }

    $registration_id = (int) $registration->id();
    $bulk_registration_ids = array_map(
      'intval',
      (array) $state->get($state_base . 'bulk_registration_ids', [])
    );

    $bulk_position = array_search($registration_id, $bulk_registration_ids, TRUE);

    if ($bulk_position !== FALSE) {
      return sprintf('%03d', ((int) $bulk_position) + 1);
    }

    $late_map = (array) $state->get($state_base . 'late_map', []);

    if (isset($late_map[$registration_id])) {
      $late_number = (int) $late_map[$registration_id];
      return 'L' . str_pad((string) $late_number, 3, '0', STR_PAD_LEFT);
    }

    $late_last = (int) $state->get($state_base . 'late_last', 0);
    $late_number = $late_last + 1;

    $late_map[$registration_id] = $late_number;

    $state->set($state_base . 'late_map', $late_map);
    $state->set($state_base . 'late_last', $late_number);

    return 'L' . str_pad((string) $late_number, 3, '0', STR_PAD_LEFT);
  }

  /**
   * Build a compact fallback badge sequence label from the QR code ID.
   */
  private function buildDefaultBadgeSequenceLabel(string $badge_number): ?string {

    if (preg_match('/(\d+)$/', $badge_number, $matches)) {
      $numeric_suffix = (int) $matches[1];

      if ($numeric_suffix > 0) {
        return str_pad((string) $numeric_suffix, 3, '0', STR_PAD_LEFT);
      }
    }

    $compact = preg_replace('/[^A-Za-z0-9]/', '', $badge_number);

    if ($compact === '') {
      return NULL;
    }

    return strtoupper(substr($compact, -4));
  }

  /**
   * Resolve an image path from a conference field or fallback URI.
   */
  private function resolveConferenceImagePath(
    NodeInterface $conference,
    string $field_name,
    ?string $fallback_uri
  ): ?string {

    if (
      $conference->hasField($field_name) &&
      !$conference->get($field_name)->isEmpty()
    ) {
      $image_entity = $conference->get($field_name)->entity;

      if ($image_entity) {

        if ($image_entity->hasField('field_media_image')) {

          if (!$image_entity->get('field_media_image')->isEmpty()) {
            $file = $image_entity->get('field_media_image')->entity;

            if ($file) {
              $path = $this->fileSystem->realpath($file->getFileUri());

              if ($path && file_exists($path)) {
                return $path;
              }
            }
          }
        }
        elseif ($image_entity->getEntityTypeId() === 'file') {
          $path = $this->fileSystem->realpath($image_entity->getFileUri());

          if ($path && file_exists($path)) {
            return $path;
          }
        }
      }
    }

    if ($fallback_uri === NULL) {
      return NULL;
    }

    $fallback_path = str_starts_with($fallback_uri, 'public://')
      ? $this->fileSystem->realpath($fallback_uri)
      : DRUPAL_ROOT . '/' . ltrim($fallback_uri, '/');

    return ($fallback_path && file_exists($fallback_path))
      ? $fallback_path
      : NULL;
  }

}