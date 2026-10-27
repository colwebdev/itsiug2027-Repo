<?php

namespace Drupal\itsiug_registration\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

/**
 * Confirmation form for generating an ITSIUG 2027 certificate.
 */
class CertificateGenerationConfirmForm extends ConfirmFormBase {

  /**
   * The registration node ID.
   *
   * @var int
   */
  protected int $registrationId;

  /**
   * The registration node.
   *
   * @var \Drupal\node\Entity\Node|null
   */
  protected ?Node $registration = NULL;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'itsiug_registration_certificate_generation_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {

    $delegate = $this->registration
      ? $this->registration
        ->get('field_delegate')
        ->entity
      : NULL;

    $name = $delegate
      ? $delegate->label()
      : 'this delegate';

    return $this->t(
      'Generate a certificate for @name?',
      [
        '@name' => $name,
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute(
      'itsiug_registration.admin_certificates'
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t(
      'This will generate the official ITSIUG 2027 Certificate of Attendance. A PDF certificate will be created and attached to the delegate registration.'
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Generate Certificate');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    $registration = NULL
  ) {

    $this->registrationId = (int) $registration;

    $this->registration = Node::load(
      $this->registrationId
    );

    if (
      !$this->registration ||
      $this->registration->bundle() !== 'conference_registration'
    ) {

      $form['error'] = [
        '#markup' => '<p>' .
          $this->t(
            'The conference registration could not be found.'
          ) .
          '</p>',
      ];

      return $form;
    }

    return parent::buildForm(
      $form,
      $form_state
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(
    array &$form,
    FormStateInterface $form_state
  ) {

    if (!$this->registration) {
      $this->messenger()->addError(
        $this->t(
          'The conference registration could not be found.'
        )
      );

      $form_state->setRedirect(
        'itsiug_registration.admin_certificates'
      );

      return;
    }

    try {

      /** @var \Drupal\itsiug_registration\Service\CertificateGenerator $generator */
      $generator = \Drupal::service(
        'itsiug_registration.certificate_generator'
      );

      $result = $generator->generate(
        $this->registration
      );

      if (!empty($result['success'])) {

        $delegate = NULL;

        if (
          !$this->registration
            ->get('field_delegate')
            ->isEmpty()
        ) {
          $delegate =
            $this->registration
              ->get('field_delegate')
              ->entity;
        }

        $this->messenger()->addStatus(
          $this->t(
            'Certificate generated successfully for @delegate.',
            [
              '@delegate' => $delegate
                ? $delegate->label()
                : 'the delegate',
            ]
          )
        );

      }
      else {

        $this->messenger()->addWarning(
          $this->t(
            $result['message']
            ?? 'The certificate could not be generated.'
          )
        );

      }

    }
    catch (\Throwable $e) {

      \Drupal::logger(
        'itsiug_registration'
      )->error(
        'Certificate generation failed for registration @registration: @message',
        [
          '@registration' =>
            $this->registration->id(),
          '@message' =>
            $e->getMessage(),
        ]
      );

      $this->messenger()->addError(
        $this->t(
          'Certificate generation failed. Please check the Drupal log.'
        )
      );

    }

    $form_state->setRedirect(
      'itsiug_registration.admin_certificates'
    );
  }

}