<?php

namespace Drupal\conference_registration\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

class InstitutionPinForm extends FormBase {

  protected EntityTypeManagerInterface $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  public function getFormId() {
    return 'conference_registration_pin_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $pin = trim((string) $form_state->getValue('registration_pin', ''));
    if (!$pin) {
      $pin = trim((string) $form_state->get('submitted_pin', ''));
    }
    $closed_institution = NULL;
    $closed_institution_id = $form_state->get('closed_institution_id');

    if ($closed_institution_id) {
      $closed_institution = $this->entityTypeManager->getStorage('node')->load($closed_institution_id);
    } elseif (preg_match('/^\d{6}$/', $pin)) {
      $closed_institution = $this->loadInstitutionByPin($pin);
    }

    if ($closed_institution && !$closed_institution->get('field_registration_active')->value) {
      $representative = '';
      if ($closed_institution->hasField('field_user_group_representative') && !$closed_institution->get('field_user_group_representative')->isEmpty()) {
        $representative = $closed_institution->get('field_user_group_representative')->value;
      }
      elseif ($closed_institution->hasField('field_representative') && !$closed_institution->get('field_representative')->isEmpty()) {
        $representative = $closed_institution->get('field_representative')->value;
      }

      $representative_email = '';
      if ($closed_institution->hasField('field_representative_email') && !$closed_institution->get('field_representative_email')->isEmpty()) {
        $representative_email = $closed_institution->get('field_representative_email')->value;
      }

      $form['closed_message'] = [
        '#type' => 'markup',
        '#markup' => 'Registration is currently closed for <strong>' . $closed_institution->label() . '</strong>Please contact your institution representative for next steps',
      ];
      
      $details_html = '<br>Institution PIN: ' . $pin . '<br>';
      $details_html .= 'Representative: ' . ($representative ?: 'N/A') . '<br>';
      $details_html .= 'Email: ' . ($representative_email ?: 'N/A');
      
      $form['closed_details'] = [
        '#type' => 'markup',
        '#markup' => $details_html,
      ];
      $form['#theme'] = 'conference_registration_pin_closed';
      $form['#attached']['library'][] = 'conference_registration/pin_form';
      return $form;
    }

    $form['help'] = [
      '#markup' => $this->t('<p>Enter the six-digit registration PIN provided by your institution to continue to the conference registration form.</p>'),
    ];

    $form['registration_pin'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Registration PIN'),
      '#required' => TRUE,
      '#maxlength' => 6,
      '#size' => 6,
      '#attributes' => [
        'pattern' => '\d*',
        'inputmode' => 'numeric',
      ],
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Continue'),
      '#button_type' => 'primary',
    ];

    $form['#theme'] = 'conference_registration_pin_form';
    $form['#attached']['library'][] = 'conference_registration/pin_form';

    return $form;
  }

  protected function loadInstitutionByPin(string $pin) {
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $query->accessCheck(TRUE)
      ->condition('type', 'institution')
      ->condition('field_registration_pin', $pin);

    $nids = $query->execute();
    if (count($nids) !== 1) {
      return NULL;
    }

    return $this->entityTypeManager->getStorage('node')->load(reset($nids));
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $pin = trim($form_state->getValue('registration_pin'));
    $form_state->set('submitted_pin', $pin);
    if (!preg_match('/^\d{6}$/', $pin)) {
      $form_state->setErrorByName('registration_pin', $this->t('Please enter a valid 6-digit PIN.'));
      return;
    }

    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $query->accessCheck(TRUE)
      ->condition('type', 'institution')
      ->condition('field_registration_pin', $pin);

    $nids = $query->execute();

    if (empty($nids)) {
      $form_state->setErrorByName('registration_pin', $this->t('The PIN you entered is not valid. Please check and try again.'));
      return;
    }

    if (count($nids) > 1) {
      $form_state->setErrorByName('registration_pin', $this->t('The PIN entered matches more than one institution. Please contact the conference administrator.'));
      return;
    }

    $institution = $this->entityTypeManager->getStorage('node')->load(reset($nids));
    if (!$institution) {
      $form_state->setErrorByName('registration_pin', $this->t('Unable to load the institution. Please contact support.'));
      return;
    }

    $registration_active = $institution->get('field_registration_active')->value;
    if (!$registration_active) {
      $form_state->set('closed_institution_id', $institution->id());
      $form_state->setRebuild();
      return;
    }

    $delegate_quota = (int) $institution->get('field_delegate_quota')->value;
    if ($delegate_quota <= 0) {
      $form_state->setErrorByName('registration_pin', $this->t('This institution does not have an active delegate quota. Please contact the conference administrator.'));
      return;
    }

    $institution_id = $institution->id();
    $submission_storage = $this->entityTypeManager->getStorage('webform_submission');
    $submissions = $submission_storage->loadByProperties(['webform_id' => 'event_registration']);

    $registration_count = 0;
    foreach ($submissions as $submission) {
      $data = $submission->getData();
      if (!empty($data['institution_id']) && (string) $data['institution_id'] === (string) $institution_id) {
        $registration_count++;
      }
      elseif (!empty($data['institution']) && $data['institution'] === $institution->label()) {
        $registration_count++;
      }
    }

    if ($registration_count >= $delegate_quota) {
      $form_state->setErrorByName('registration_pin', $this->t('The delegate quota for your institution has been reached. Registration is closed.'));
      return;
    }

    $form_state->set('institution', $institution);
    $form_state->set('registration_count', $registration_count);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $institution = $form_state->get('institution');
    $registration_count = $form_state->get('registration_count');
    if (!$institution) {
      // No institution means validation failed or something went wrong.
      return;
    }

    $delegate_quota = (int) $institution->get('field_delegate_quota')->value;
    $next_registration_number = $registration_count + 1;
    $institution_acronym = $institution->get('field_institution_acronym')->value ?? '';
    $params = [
      'institution' => $institution->label(),
      'institution_id' => $institution->id(),
      'acronym' => $institution_acronym,
      'representative' => $institution->hasField('field_user_group_representative') ? $institution->get('field_user_group_representative')->value : '',
      'representative_email' => $institution->get('field_representative_email')->value ?? '',
      'alternate_representative' => $institution->hasField('field_alternative_representative') ? $institution->get('field_alternative_representative')->value : '',
      'alternate_representative_email' => $institution->get('field_alt_representative_email')->value ?? '',
      'registration_number' => $this->t('@current of @quota', [
        '@current' => $next_registration_number,
        '@quota' => $delegate_quota,
      ]),
      'delegate_quota' => $delegate_quota,
      'registration_count' => $registration_count,
    ];

    $url = Url::fromRoute('entity.webform.canonical', ['webform' => 'event_registration'], ['query' => $params]);
    $form_state->setRedirectUrl($url);
  }
}
