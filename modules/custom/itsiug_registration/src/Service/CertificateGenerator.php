<?php

namespace Drupal\itsiug_registration\Service;

use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\FileRepositoryInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Mail\MailManagerInterface;
use TCPDF;

/**
 * Generates ITSIUG conference attendance certificates.
 */
class CertificateGenerator {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

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
 * The file URL generator.
 *
 * @var \Drupal\Core\File\FileUrlGeneratorInterface
 */
protected FileUrlGeneratorInterface $fileUrlGenerator;

/**
 * The mail manager.
 *
 * @var \Drupal\Core\Mail\MailManagerInterface
 */
protected MailManagerInterface $mailManager;

  /**
   * Constructs the certificate generator.
   */
public function __construct(
  EntityTypeManagerInterface $entity_type_manager,
  FileRepositoryInterface $file_repository,
  FileSystemInterface $file_system,
  FileUrlGeneratorInterface $file_url_generator,
  MailManagerInterface $mail_manager,
) {
  $this->entityTypeManager = $entity_type_manager;
  $this->fileRepository = $file_repository;
  $this->fileSystem = $file_system;
  $this->fileUrlGenerator = $file_url_generator;
  $this->mailManager = $mail_manager;
}

  /**
   * Generates a certificate for a conference registration.
   *
   * @param \Drupal\node\NodeInterface $registration
   *   The conference registration.
   *
   * @return array
   *   Result information.
   *
   * @throws \Exception
   *   If certificate generation fails.
   */
  public function generate(NodeInterface $registration): array {

    // ------------------------------------------------------------
    // Basic validation.
    // ------------------------------------------------------------

    if ($registration->bundle() !== 'conference_registration') {
      throw new \Exception(
        'The supplied node is not a conference registration.'
      );
    }

    // ------------------------------------------------------------
    // Certificate eligibility.
    // ------------------------------------------------------------

    $eligibility = $registration
      ->get('field_certificate_eligibility')
      ->value;

    if ($eligibility !== 'eligible') {
      return [
        'success' => FALSE,
        'message' => 'This delegate is not eligible for a certificate.',
      ];
    }

    // ------------------------------------------------------------
    // Existing certificate protection.
    // ------------------------------------------------------------

    $status = $registration
      ->get('field_certificate_status')
      ->value;

    if (
      in_array(
        $status,
        ['generated', 'issued'],
        TRUE
      )
    ) {
      return [
        'success' => FALSE,
        'message' => 'A certificate has already been generated for this delegate.',
      ];
    }

    // ------------------------------------------------------------
    // Load delegate.
    // ------------------------------------------------------------

    if ($registration->get('field_delegate')->isEmpty()) {
      throw new \Exception(
        'The registration has no delegate.'
      );
    }

    $delegate = $registration
      ->get('field_delegate')
      ->entity;

    if (!$delegate) {
      throw new \Exception(
        'The delegate could not be loaded.'
      );
    }

    // ------------------------------------------------------------
    // Load institution.
    // ------------------------------------------------------------

    $institution_name = '';

    if (!$registration->get('field_institution1')->isEmpty()) {
      $institution = $registration
        ->get('field_institution1')
        ->entity;

      if ($institution) {
        $institution_name = $institution->label();
      }
    }

    // ------------------------------------------------------------
    // Load conference.
    // ------------------------------------------------------------

    if ($registration->get('field_conference')->isEmpty()) {
      throw new \Exception(
        'The registration has no conference.'
      );
    }

    $conference = $registration
      ->get('field_conference')
      ->entity;

    if (!$conference) {
      throw new \Exception(
        'The conference could not be loaded.'
      );
    }

    $conference_name = $conference->label();

    // ------------------------------------------------------------
    // Conference dates.
    // ------------------------------------------------------------

    $start_date = '';
    $end_date = '';

    if (!$conference->get('field_start_date')->isEmpty()) {
      $start_date = $conference
        ->get('field_start_date')
        ->date
        ->format('d F Y');
    }

    if (!$conference->get('field_end_date')->isEmpty()) {
      $end_date = $conference
        ->get('field_end_date')
        ->date
        ->format('d F Y');
    }

    $conference_dates = $start_date;

    if ($start_date && $end_date) {
      $conference_dates .= ' – ' . $end_date;
    }
    elseif ($end_date) {
      $conference_dates = $end_date;
    }

    // ------------------------------------------------------------
    // Certificate number.
    //
    // Use the registration QR/reference if available.
    // ------------------------------------------------------------

    $certificate_number = '';

    if (!$registration->get('field_qr_code')->isEmpty()) {
      $certificate_number = $registration
        ->get('field_qr_code')
        ->value;
    }

    if (!$certificate_number) {
      $certificate_number = 'ITSIUG-' . $registration->id();
    }

    // ------------------------------------------------------------
    // Conference logo.
    // ------------------------------------------------------------

    $logo_path = NULL;

    if (!$conference->get('field_conference_logo')->isEmpty()) {

      $logo_entity = $conference
        ->get('field_conference_logo')
        ->entity;

      if ($logo_entity) {

        // Handle an image/media entity.
        if ($logo_entity->hasField('field_media_image')) {

          if (!$logo_entity->get('field_media_image')->isEmpty()) {

            $file = $logo_entity
              ->get('field_media_image')
              ->entity;

            if ($file) {
              $logo_path = $this->fileSystem
                ->realpath($file->getFileUri());
            }

          }

        }

        // Handle a direct file entity.
        elseif ($logo_entity->getEntityTypeId() === 'file') {
          $logo_path = $this->fileSystem
            ->realpath($logo_entity->getFileUri());
        }
      }
    }

    // ------------------------------------------------------------
    // Create PDF.
    // ------------------------------------------------------------

    $pdf = new TCPDF(
      'L',
      'mm',
      'A4',
      TRUE,
      'UTF-8',
      FALSE
    );

    $pdf->SetCreator('ITSIUG 2027');
    $pdf->SetAuthor('ITSIUG');
    $pdf->SetTitle('Certificate of Attendance - ' . $delegate->label());
    $pdf->SetSubject('ITSIUG 2027 Certificate of Attendance');

    $pdf->setPrintHeader(FALSE);
    $pdf->setPrintFooter(FALSE);

    $pdf->SetMargins(20, 15, 20);
    $pdf->SetAutoPageBreak(FALSE);

    $pdf->AddPage();

    // ------------------------------------------------------------
    // Colours.
    // ------------------------------------------------------------

    $navy = [31, 75, 107];
    $gold = [181, 145, 65];
    $grey = [90, 90, 90];

    // ------------------------------------------------------------
    // Decorative border.
    // ------------------------------------------------------------

    $pdf->SetDrawColor(
      $navy[0],
      $navy[1],
      $navy[2]
    );

    $pdf->SetLineWidth(1.2);

    $pdf->Rect(
      12,
      12,
      273,
      186
    );

    $pdf->SetDrawColor(
      $gold[0],
      $gold[1],
      $gold[2]
    );

    $pdf->SetLineWidth(0.4);

    $pdf->Rect(
      16,
      16,
      265,
      178
    );

    // ------------------------------------------------------------
    // Logo.
    // ------------------------------------------------------------

    if ($logo_path && file_exists($logo_path)) {
      $pdf->Image(
        $logo_path,
        126,
        23,
        42,
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

    // ------------------------------------------------------------
    // Conference title.
    // ------------------------------------------------------------

    $pdf->SetTextColor(
      $navy[0],
      $navy[1],
      $navy[2]
    );

    $pdf->SetFont(
      'helvetica',
      'B',
      20
    );

    $pdf->SetXY(25, 48);

    $pdf->Cell(
      247,
      10,
      strtoupper($conference_name),
      0,
      1,
      'C'
    );

    // ------------------------------------------------------------
    // Certificate heading.
    // ------------------------------------------------------------

    $pdf->SetTextColor(
      $gold[0],
      $gold[1],
      $gold[2]
    );

    $pdf->SetFont(
      'helvetica',
      'B',
      26
    );

    $pdf->SetXY(25, 66);

    $pdf->Cell(
      247,
      12,
      'CERTIFICATE OF ATTENDANCE',
      0,
      1,
      'C'
    );

    // ------------------------------------------------------------
    // Presentation text.
    // ------------------------------------------------------------

    $pdf->SetTextColor(
      $grey[0],
      $grey[1],
      $grey[2]
    );

    $pdf->SetFont(
      'helvetica',
      '',
      12
    );

    $pdf->SetXY(25, 83);

    $pdf->Cell(
      247,
      8,
      'This certificate is proudly presented to',
      0,
      1,
      'C'
    );

    // ------------------------------------------------------------
    // Delegate name.
    // ------------------------------------------------------------

    $pdf->SetTextColor(
      $navy[0],
      $navy[1],
      $navy[2]
    );

    $pdf->SetFont(
      'helvetica',
      'B',
      25
    );

    $pdf->SetXY(25, 94);

    $pdf->Cell(
      247,
      12,
      $delegate->label(),
      0,
      1,
      'C'
    );

    // ------------------------------------------------------------
    // Institution.
    // ------------------------------------------------------------

    if ($institution_name) {

      $pdf->SetTextColor(
        $grey[0],
        $grey[1],
        $grey[2]
      );

      $pdf->SetFont(
        'helvetica',
        'I',
        13
      );

      $pdf->SetXY(25, 108);

      $pdf->Cell(
        247,
        8,
        $institution_name,
        0,
        1,
        'C'
      );
    }

    // ------------------------------------------------------------
    // Recognition text.
    // ------------------------------------------------------------

    $pdf->SetFont(
      'helvetica',
      '',
      11
    );

    $recognition = 'in recognition of attendance and participation at the ' .
      $conference_name . ' Conference.';

    $pdf->SetXY(35, 124);

    $pdf->MultiCell(
      227,
      7,
      $recognition,
      0,
      'C'
    );

    // ------------------------------------------------------------
    // Dates.
    // ------------------------------------------------------------

    if ($conference_dates) {

      $pdf->SetFont(
        'helvetica',
        'B',
        12
      );

      $pdf->SetTextColor(
        $navy[0],
        $navy[1],
        $navy[2]
      );

      $pdf->SetXY(25, 143);

      $pdf->Cell(
        247,
        8,
        $conference_dates,
        0,
        1,
        'C'
      );
    }

    // ------------------------------------------------------------
    // Certificate number.
    // ------------------------------------------------------------

    $pdf->SetTextColor(
      $grey[0],
      $grey[1],
      $grey[2]
    );

    $pdf->SetFont(
      'helvetica',
      '',
      8
    );

    $pdf->SetXY(25, 154);

    $pdf->Cell(
      247,
      6,
      'Certificate No.: ' . $certificate_number,
      0,
      1,
      'C'
    );

    // ------------------------------------------------------------
    // Signature lines.
    // ------------------------------------------------------------

    $pdf->SetDrawColor(
      $grey[0],
      $grey[1],
      $grey[2]
    );

    $pdf->SetLineWidth(0.3);

    $pdf->Line(
      65,
      178,
      115,
      178
    );

    $pdf->Line(
      165,
      178,
      215,
      178
    );

    $pdf->SetFont(
      'helvetica',
      '',
      9
    );

    $pdf->SetXY(45, 180);

    $pdf->Cell(
      90,
      6,
      'Conference Chair',
      0,
      0,
      'C'
    );

    $pdf->SetXY(145, 180);

    $pdf->Cell(
      90,
      6,
      'ITSIUG Representative',
      0,
      0,
      'C'
    );

    // ------------------------------------------------------------
    // Get PDF contents.
    // ------------------------------------------------------------

    $pdf_data = $pdf->Output(
      '',
      'S'
    );

    // ------------------------------------------------------------
    // Ensure certificate directory exists.
    // ------------------------------------------------------------

    $year = date('Y');

    $directory = 'public://certificates/' . $year;

    $this->fileSystem->prepareDirectory(
      $directory,
      FileSystemInterface::CREATE_DIRECTORY |
      FileSystemInterface::MODIFY_PERMISSIONS
    );

    // ------------------------------------------------------------
    // Filename.
    // ------------------------------------------------------------

    $safe_name = preg_replace(
      '/[^a-zA-Z0-9_-]+/',
      '_',
      $delegate->label()
    );

    $filename =
      'ITSIUG-2027-' .
      $registration->id() .
      '-' .
      $safe_name .
      '.pdf';

    $destination =
      $directory . '/' . $filename;

    // ------------------------------------------------------------
    // Create managed Drupal file.
    // ------------------------------------------------------------

    $file = $this->fileRepository->writeData(
      $pdf_data,
      $destination,
      FileExists::Replace
    );

    $file->setPermanent();
    $file->save();

    // ------------------------------------------------------------
    // Attach certificate to registration.
    // ------------------------------------------------------------

    $registration->set(
      'field_certificate_file',
      [
        'target_id' => $file->id(),
        'display' => 1,
        'description' => 'ITSIUG 2027 Certificate of Attendance',
      ]
    );

    $registration->set(
      'field_certificate_status',
      'generated'
    );

$registration->save();

// ------------------------------------------------------------
// Email certificate download link.
// ------------------------------------------------------------

$delegate_email = '';

if ($delegate->hasField('field_email') &&
    !$delegate->get('field_email')->isEmpty()) {
  $delegate_email = trim(
    $delegate->get('field_email')->value
  );
}

if ($delegate_email && filter_var($delegate_email, FILTER_VALIDATE_EMAIL)) {

  $download_url = $this->fileUrlGenerator
    ->generate($file->getFileUri())
    ->setAbsolute()
    ->toString();

  $mail_result = $this->mailManager->mail(
    'itsiug_registration',
    'certificate',
    $delegate_email,
    $registration->language()->getId(),
    [
      'delegate' => $delegate->label(),
      'conference' => $conference_name,
      'certificate_number' => $certificate_number,
      'download_url' => $download_url,
    ]
  );

  if (empty($mail_result['result'])) {
    \Drupal::logger('itsiug_registration')->error(
      'Certificate email could not be sent to @email for registration @registration.',
      [
        '@email' => $delegate_email,
        '@registration' => $registration->id(),
      ]
    );
  }
  else {
    \Drupal::logger('itsiug_registration')->notice(
      'Certificate email sent to @email for registration @registration.',
      [
        '@email' => $delegate_email,
        '@registration' => $registration->id(),
      ]
    );
  }
}

// ------------------------------------------------------------
// Email certificate download link.
// ------------------------------------------------------------

$email_sent = FALSE;
$email = '';

if (
  $delegate->hasField('field_email') &&
  !$delegate->get('field_email')->isEmpty()
) {
  $email = trim($delegate->get('field_email')->value);

  if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {

    $download_url = $this->fileUrlGenerator
      ->generate($file->getFileUri())
      ->setAbsolute(TRUE)
      ->toString();

    $mail_result = $this->mailManager->mail(
      'itsiug_registration',
      'certificate',
      $email,
      'en',
      [
        'delegate' => $delegate->label(),
        'conference' => $conference_name,
        'certificate_number' => $certificate_number,
        'download_url' => $download_url,
      ],
      NULL,
      TRUE
    );

    $email_sent = !empty($mail_result['result']);

    if (!$email_sent) {
      \Drupal::logger('itsiug_registration')->error(
        'Certificate generated for @delegate, but certificate email could not be sent to @email.',
        [
          '@delegate' => $delegate->label(),
          '@email' => $email,
        ]
      );
    }
  }
}

// ------------------------------------------------------------
// Return result.
// ------------------------------------------------------------

return [
  'success' => TRUE,
  'message' => $email_sent
    ? 'Certificate generated successfully and emailed to the delegate.'
    : 'Certificate generated successfully, but the certificate email could not be sent.',
  'file_id' => $file->id(),
  'filename' => $filename,
  'uri' => $file->getFileUri(),
  'certificate_number' => $certificate_number,
  'email_sent' => $email_sent,
  'email' => $email,
];
  }

}
