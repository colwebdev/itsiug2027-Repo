<?php

namespace Drupal\itsiug_registration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\Request;

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
    $request = \Drupal::request();
    $search = trim((string) $request->query->get('search', ''));

    if ($search === '') {
      $search = $this->findSearchValueRecursive((array) $request->request->all());
    }

    $registration_ids = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'conference_registration')
      ->condition('field_conference', 2)
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

      'filters' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'itsiug-admin-filters',
            'itsiug-badge-filters',
          ],
        ],

        'search' => [
          '#type' => 'textfield',
          '#title' => $this->t('Find Delegate'),
          '#default_value' => $search,
          '#parents' => ['badge_search'],
          '#size' => 40,
          '#attributes' => [
            'class' => [
              'itsiug-badge-filter-input',
            ],
          ],
          '#description' => $this->t('Type any part of first name, last name, QR ID, or institution.'),
        ],

        'filter_actions' => [
          '#type' => 'actions',

          'apply' => [
            '#type' => 'submit',
            '#value' => $this->t('Apply Filter'),
            '#button_type' => 'primary',
            '#limit_validation_errors' => [],
            '#attributes' => [
              'class' => [
                'button',
                'button--primary',
              ],
            ],
            '#submit' => ['::applyFilterSubmit'],
          ],

          'clear' => [
            '#type' => 'submit',
            '#value' => $this->t('Clear Filter'),
            '#button_type' => 'primary',
            '#limit_validation_errors' => [],
            '#attributes' => [
              'class' => [
                'button',
              ],
            ],
            '#submit' => ['::clearFilterSubmit'],
          ],
        ],
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

      $first_name = '';
      if ($delegate->hasField('field_first_name') && !$delegate->get('field_first_name')->isEmpty()) {
        $first_name = trim((string) $delegate->get('field_first_name')->value);
      }

      $last_name = '';
      if ($delegate->hasField('field_last_name') && !$delegate->get('field_last_name')->isEmpty()) {
        $last_name = trim((string) $delegate->get('field_last_name')->value);
      }

      $current_status = 'delegate';

      if (!$registration->get('field_conference_status')->isEmpty()) {
        $current_status = (string) $registration->get('field_conference_status')->value;
      }

      $qr_code = (string) ($registration->get('field_qr_code')->value ?? '');

      $search_haystack = implode(' ', [
        $delegate->label(),
        $first_name,
        $last_name,
        $institution_label,
        $qr_code,
      ]);

      if ($search !== '' && stripos($search_haystack, $search) === FALSE) {
        continue;
      }

      $rows[] = [
        'registration' => $registration,
        'delegate' => $delegate,
        'institution_label' => $institution_label,
        'qr_code' => $qr_code,
        'current_status' => $current_status,
        'sort_last_name' => $last_name !== '' ? $last_name : $delegate->label(),
        'sort_first_name' => $first_name !== '' ? $first_name : $delegate->label(),
      ];
    }

    usort($rows, static function (array $left, array $right): int {
      $last_name_compare = strcasecmp((string) $left['sort_last_name'], (string) $right['sort_last_name']);

      if ($last_name_compare !== 0) {
        return $last_name_compare;
      }

      $first_name_compare = strcasecmp((string) $left['sort_first_name'], (string) $right['sort_first_name']);

      if ($first_name_compare !== 0) {
        return $first_name_compare;
      }

      return ((int) $left['registration']->id()) <=> ((int) $right['registration']->id());
    });

    foreach ($rows as $row) {
      /** @var \Drupal\node\Entity\Node $registration */
      $registration = $row['registration'];
      $delegate = $row['delegate'];

      $form['badge_management']['delegates'][$registration->id()]['delegate'] = [
        '#markup' => $delegate->toLink()->toString(),
      ];

      $form['badge_management']['delegates'][$registration->id()]['institution'] = [
        '#markup' => $row['institution_label'],
      ];

      $form['badge_management']['delegates'][$registration->id()]['qr'] = [
        '#markup' => $row['qr_code'],
      ];

      $form['badge_management']['delegates'][$registration->id()]['conference_status'] = [
        '#type' => 'select',
        '#options' => $options,
        '#default_value' => $row['current_status'],
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

      'reset_late_numbers' => [
        '#type' => 'submit',
        '#value' => $this->t('Reset Late Numbers'),
        '#button_type' => 'primary',
        '#limit_validation_errors' => [],
        '#submit' => ['::resetLateNumbersSubmit'],
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
   * Apply the badge management delegate filter.
   */
  public function applyFilterSubmit(array &$form, FormStateInterface $form_state): void {

    $user_input = (array) $form_state->getUserInput();
    $search = trim((string) ($user_input['badge_search'] ?? ''));

    if ($search === '') {
      $search = trim((string) $form_state->getValue('badge_search', ''));
    }

    if ($search === '') {
      $search = $this->extractSubmittedSearch($form_state, \Drupal::request());
    }

    if ($search === '') {
      $form_state->setRedirect('itsiug_registration.admin_badges');
      return;
    }

    $form_state->setRedirect(
      'itsiug_registration.admin_badges',
      [],
      [
        'query' => [
          'search' => $search,
        ],
      ]
    );
  }

  /**
   * Extract the submitted search text from form values or request payload.
   */
  private function extractSubmittedSearch(FormStateInterface $form_state, Request $request): string {

    $candidates = [
      (string) $form_state->getValue([
        'badge_management',
        'filters',
        'search',
      ], ''),
      (string) $form_state->getValue([
        'filters',
        'search',
      ], ''),
      (string) $form_state->getValue('search', ''),
      (string) $request->request->get('search', ''),
    ];

    foreach ($candidates as $candidate) {
      $candidate = trim($candidate);
      if ($candidate !== '') {
        return $candidate;
      }
    }

    $all_values = (array) $form_state->getValues();
    $found = $this->findSearchValueRecursive($all_values);

    if ($found !== '') {
      return $found;
    }

    return trim((string) $request->query->get('search', ''));
  }

  /**
   * Recursively find a non-empty value for a search key.
   */
  private function findSearchValueRecursive(array $values): string {

    foreach ($values as $key => $value) {
      if ($key === 'search' && !is_array($value)) {
        $candidate = trim((string) $value);
        if ($candidate !== '') {
          return $candidate;
        }
      }

      if (is_array($value)) {
        $candidate = $this->findSearchValueRecursive($value);

        if ($candidate !== '') {
          return $candidate;
        }
      }
    }

    return '';
  }

  /**
   * Clear the badge management delegate filter.
   */
  public function clearFilterSubmit(array &$form, FormStateInterface $form_state): void {

    $form_state->setRedirect('itsiug_registration.admin_badges');
  }

  /**
   * Reset late badge numbering for this conference.
   */
  public function resetLateNumbersSubmit(array &$form, FormStateInterface $form_state): void {

    /** @var \Drupal\itsiug_registration\Service\BadgeGenerator $badge_generator */
    $badge_generator = \Drupal::service('itsiug_registration.badge_generator');
    $result = $badge_generator->resetLateNumbering(2);

    if (!empty($result['success'])) {
      $this->messenger()->addStatus(
        $this->t('Late badge numbers were reset successfully.')
      );
    }
    else {
      $this->messenger()->addWarning(
        $this->t('Late badge numbers could not be reset right now.')
      );
    }

    $form_state->setRebuild(TRUE);
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