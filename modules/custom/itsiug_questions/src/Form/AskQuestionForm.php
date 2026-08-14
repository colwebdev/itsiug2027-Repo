<?php

declare(strict_types=1);

namespace Drupal\itsiug_questions\Form;

use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\itsiug_questions\Service\QuestionManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Lets a delegate submit a question for a session.
 */
class AskQuestionForm extends FormBase {

  /**
   * Flood event name for question submissions.
   */
  protected const FLOOD_EVENT = 'itsiug_questions.submit';

  public function __construct(
    protected QuestionManager $questionManager,
    protected FloodInterface $flood,
    protected PrivateTempStoreFactory $tempStoreFactory,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('itsiug_questions.manager'),
      $container->get('flood'),
      $container->get('tempstore.private'),
    );
  }

  public function getFormId(): string {
    return 'itsiug_questions_ask';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $session_number = trim((string) $this->getRequest()->query->get('s', ''));
    $prefilled_session = null;

    if ($session_number !== '' && ctype_digit($session_number)) {
      $prefilled_session = $this->questionManager->findSessionByNumber((int) $session_number);
    }

    $form['#attached']['library'][] = 'itsiug_questions/ask';

    $form['#prefix'] = '<div class="itsiug-ask-page">';
    $form['#suffix'] = '</div>';

    $form['heading'] = [
      '#markup' => '<h1>' . $this->t('Ask a question') . '</h1>',
    ];

    if ($prefilled_session) {
      $form['session_heading'] = [
        '#markup' => '<p class="itsiug-ask-session">'
          . $this->t('@heading', ['@heading' => $this->questionManager->getSessionHeading($prefilled_session)])
          . '</p>',
      ];
    }

    $form['scan'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['itsiug-ask-scan']],
      'button' => [
        '#type' => 'html_tag',
        '#tag' => 'button',
        '#value' => $this->t('Scan my badge'),
        '#attributes' => [
          'type' => 'button',
          'id' => 'itsiug-ask-scan-start',
          'class' => ['button', 'button--primary', 'itsiug-ask-scan-button'],
        ],
      ],
      'reader' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => '',
        '#attributes' => [
          'id' => 'itsiug-ask-qr-reader',
          'class' => ['itsiug-ask-qr-reader'],
        ],
      ],
      'identity' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => '',
        '#attributes' => [
          'id' => 'itsiug-ask-identity',
          'class' => ['itsiug-ask-identity'],
          'aria-live' => 'polite',
        ],
      ],
    ];

    $form['qr_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your QR Code ID'),
      '#description' => $this->t('Printed on your badge. Scanning fills this in for you.'),
      '#required' => TRUE,
      '#maxlength' => 32,
      '#size' => 24,
      '#attributes' => [
        'id' => 'itsiug-ask-qr-code',
        'autocomplete' => 'off',
        'autocapitalize' => 'characters',
      ],
    ];

    $form['session_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Session number'),
      '#required' => TRUE,
      '#maxlength' => 5,
      '#size' => 8,
      '#default_value' => $session_number,
      '#attributes' => [
        'inputmode' => 'numeric',
        'autocomplete' => 'off',
      ],
    ];

    $form['question'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Pose your question'),
      '#required' => TRUE,
      '#rows' => 5,
      '#maxlength' => QuestionManager::MAX_QUESTION_LENGTH,
      '#description' => $this->t(
        'Up to @count characters.',
        ['@count' => QuestionManager::MAX_QUESTION_LENGTH],
      ),
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
        '#button_type' => 'primary',
      ],
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $ip = $this->getRequest()->getClientIp();

    if (!$this->flood->isAllowed(self::FLOOD_EVENT, 20, 3600, $ip)) {
      $form_state->setErrorByName('question', $this->t(
        'Too many questions have been submitted from this device. Please try again later.'
      ));
      return;
    }

    $qr_code = $this->questionManager->normaliseQrCode(
      (string) $form_state->getValue('qr_code')
    );

    if ($qr_code === '') {
      $form_state->setErrorByName('qr_code', $this->t('Please enter or scan your QR Code ID.'));
      return;
    }

    $delegate = $this->questionManager->findDelegateByQrCode($qr_code);

    if (!$delegate) {
      $form_state->setErrorByName('qr_code', $this->t(
        'That QR Code ID was not recognised. Please check your badge and try again.'
      ));
      return;
    }

    $session_number = trim((string) $form_state->getValue('session_number'));

    if (!ctype_digit($session_number)) {
      $form_state->setErrorByName('session_number', $this->t('The session number must be digits only.'));
      return;
    }

    $session = $this->questionManager->findSessionByNumber((int) $session_number);

    if (!$session) {
      $form_state->setErrorByName('session_number', $this->t(
        'Session @number was not found. Please check the number on your table.',
        ['@number' => $session_number],
      ));
      return;
    }

    $already = $this->questionManager->countDelegateQuestions($session, $delegate);

    if ($already >= QuestionManager::MAX_PER_DELEGATE_PER_SESSION) {
      $form_state->setErrorByName('question', $this->t(
        'You have already submitted @count questions for this session.',
        ['@count' => $already],
      ));
      return;
    }

    if (trim((string) $form_state->getValue('question')) === '') {
      $form_state->setErrorByName('question', $this->t('Please type your question.'));
      return;
    }

    $form_state->set('delegate', $delegate);
    $form_state->set('session', $session);
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $delegate = $form_state->get('delegate');
    $session = $form_state->get('session');

    $question = $this->questionManager->createQuestion(
      $session,
      $delegate,
      (string) $form_state->getValue('question'),
    );

    $this->flood->register(
      self::FLOOD_EVENT,
      3600,
      $this->getRequest()->getClientIp(),
    );

    $this->tempStoreFactory->get('itsiug_questions')->set('last_submission', [
      'question_number' => (int) $question->get('field_question_number')->value,
      'delegate_name' => $this->questionManager->getDelegateDisplayName($delegate),
      'session_heading' => $this->questionManager->getSessionHeading($session),
      'session_number' => $session->get('field_session_number')->isEmpty()
        ? ''
        : (string) $session->get('field_session_number')->value,
    ]);

    $form_state->setRedirectUrl(Url::fromRoute('itsiug_questions.submitted'));
  }

}
