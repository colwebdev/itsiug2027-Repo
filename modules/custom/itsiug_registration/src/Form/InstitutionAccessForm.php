<?php

namespace Drupal\itsiug_registration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

/**
 * Institution registration access form.
 */
class InstitutionAccessForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'itsiug_registration_institution_access';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['institution_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Institution Code'),
      '#required' => TRUE,
      '#maxlength' => 50,
      '#attributes' => [
        'autocomplete' => 'off',
      ],
    ];

    $form['registration_pin'] = [
      '#type' => 'password',
      '#title' => $this->t('Registration PIN'),
      '#required' => TRUE,
      '#maxlength' => 50,
      '#attributes' => [
        'autocomplete' => 'off',
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Continue'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $institution_code = trim($form_state->getValue('institution_code'));
    $registration_pin = trim($form_state->getValue('registration_pin'));

    // Find the institution using the institution code.
    $nids = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'institution')
      ->condition('field_institution_code', $institution_code)
      ->range(0, 1)
      ->execute();

    if (empty($nids)) {
      $this->accessDenied();
      return;
    }

    $nid = reset($nids);
    $institution = Node::load($nid);

    if (!$institution) {
      $this->accessDenied();
      return;
    }

    // Make sure the institution is active.
    $status = $institution->get('field_institution_status')->value;

    if ($status !== 'active') {
      $this->messenger()->addError(
        $this->t('Registration access is not currently available for this institution.')
      );
      return;
    }

    // Get the stored PIN.
    $stored_pin = $institution->get('field_registration_pin')->value;

    // Validate the PIN.
    if (!hash_equals((string) $stored_pin, (string) $registration_pin)) {
      $this->accessDenied();
      return;
    }

    // Store the institution context in the current browser session.
    $session = \Drupal::request()->getSession();

    $session->set('itsiug_registration', [
      'institution_nid' => $institution->id(),
      'authenticated_at' => \Drupal::time()->getRequestTime(),
    ]);

    // Redirect to the Delegate Registration Webform.
    $url = Url::fromRoute('entity.webform.canonical', [
      'webform' => 'delegate_registration',
    ]);

    $form_state->setRedirect('itsiug_registration.delegate');
  }

  /**
   * Display a generic access-denied message.
   */
  protected function accessDenied() {
    $this->messenger()->addError(
      $this->t('The Institution Code or Registration PIN is incorrect.')
    );
  }

}