<?php

namespace Drupal\itsiug_registration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

/**
 * Badge management form.
 */
class BadgeManagementForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'itsiug_registration_badge_management_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['#tree'] = TRUE;

    $registration_ids = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'conference_registration')
      ->condition('field_conference', 2)
      ->sort('created', 'ASC')
      ->execute();

    $options = itsiug_conference_status_options();
    $form['badge_management'] = [
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
            'Manage and review all registered ITSIUG 2027 delegate badges.'
          ) .
          '</p>',
      ],
    ];

    $form['badge_management']['delegates'] = [
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
        $this->t('Conference Status'),
        $this->t('Badge'),
      ],
      '#empty' => $this->t('No ITSIUG 2027 registrations were found.'),
      '#tree' => TRUE,
    ];

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

      $current_status = 'delegate';

      if (!$registration->get('field_conference_status')->isEmpty()) {
        $current_status = (string) $registration->get('field_conference_status')->value;
      }

      $form['badge_management']['delegates'][$registration->id()]['delegate'] = [
        '#markup' => $delegate->toLink()->toString(),
      ];

      $form['badge_management']['delegates'][$registration->id()]['institution'] = [
        '#markup' => $institution ? $institution->label() : '',
      ];

      $form['badge_management']['delegates'][$registration->id()]['qr'] = [
        '#markup' => $registration->get('field_qr_code')->value ?? '',
      ];

      $form['badge_management']['delegates'][$registration->id()]['conference_status'] = [
        '#type' => 'select',
        '#options' => $options,
        '#default_value' => $current_status,
      ];

      $form['badge_management']['delegates'][$registration->id()]['badge'] = $this->buildBadgeCell($registration);
    }

    $form['badge_management']['actions'] = [
      '#type' => 'actions',

      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Save Statuses'),
        '#button_type' => 'primary',
      ],

      'bulk_download' => [
        '#type' => 'submit',
        '#value' => $this->t('Download All Badges (PDF)'),
        '#button_type' => 'primary',
        '#limit_validation_errors' => [],
        '#submit' => ['::bulkDownloadSubmit'],
      ],
    ];

    $form['badge_management']['back'] = [
      '#type' => 'link',
      '#title' => $this->t('← Back to Administration'),
      '#url' => Url::fromRoute('itsiug_registration.admin'),
      '#attributes' => [
        'class' => [
          'button',
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $updated = 0;
    $submitted_statuses = (array) $form_state->getValue('delegates', []);

    if (empty($submitted_statuses)) {
      $submitted_statuses = (array) $form_state->getValue([
        'badge_management',
        'delegates',
      ], []);
    }

    if (empty($submitted_statuses)) {
      $this->messenger()->addWarning(
        $this->t('No conference statuses were submitted. Please try again.')
      );
      return;
    }

    foreach ($submitted_statuses as $registration_id => $row_values) {
      if (!is_array($row_values) || !isset($row_values['conference_status'])) {
        continue;
      }

      $status = (string) $row_values['conference_status'];
      $registration = Node::load((int) $registration_id);

      if (!$registration || $registration->bundle() !== 'conference_registration') {
        continue;
      }

      if ($registration->hasField('field_conference_status')) {
        $current_status = $registration->get('field_conference_status')->value ?? '';

        if ($current_status !== $status) {
          $registration->set('field_conference_status', $status);

          if ($registration->hasField('field_badge_status')) {
            $registration->set('field_badge_status', 'not_generated');
          }

          if ($registration->hasField('field_badge_file')) {
            $registration->set('field_badge_file', []);
          }

          $registration->save();
          $updated++;
        }
      }
    }

    $this->messenger()->addStatus($this->formatPlural($updated, 'Updated 1 conference status.', 'Updated @count conference statuses.'));
  }

  /**
   * Redirect to the bulk badge download action.
   */
  public function bulkDownloadSubmit(array &$form, FormStateInterface $form_state): void {

    $form_state->setRedirect('itsiug_registration.admin_badges_bulk_download');
  }

  /**
   * Build the badge action cell.
   */
  private function buildBadgeCell(Node $registration): array {

    $badge = NULL;

    if (
      !$registration->get('field_badge_file')->isEmpty() &&
      !$registration->get('field_badge_status')->isEmpty()
    ) {
      $status = (string) $registration->get('field_badge_status')->value;

      if (in_array($status, ['generated', 'issued'], TRUE)) {
        $badge = [
          'data' => [
            '#markup' => '<span class="button button--small is-disabled">Generated</span>',
          ],
        ];
      }
    }

    if ($badge) {
      return $badge;
    }

    return [
      'data' => [
        '#type' => 'link',
        '#title' => $this->t('Generate Badge'),
        '#url' => Url::fromRoute('itsiug_registration.admin_badge_generate', ['registration' => $registration->id()]),
        '#attributes' => [
          'class' => [
            'button',
            'button--primary',
          ],
        ],
      ],
    ];
  }

}