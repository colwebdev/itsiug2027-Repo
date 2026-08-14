<?php

declare(strict_types=1);

namespace Drupal\itsiug_questions\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\itsiug_questions\Service\QuestionManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Lets a chair mark questions as asked, answered or hidden.
 */
class QuestionModerationForm extends FormBase {

  public function __construct(
    protected QuestionManager $questionManager,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('itsiug_questions.manager'),
      $container->get('entity_type.manager'),
    );
  }

  public function getFormId(): string {
    return 'itsiug_questions_moderation';
  }

  public function buildForm(array $form, FormStateInterface $form_state, string $session_number = ''): array {
    $session = ctype_digit($session_number)
      ? $this->questionManager->findSessionByNumber((int) $session_number)
      : null;

    if (!$session) {
      throw new NotFoundHttpException();
    }

    $form_state->set('session_number', $session_number);

    $form['#attached']['library'][] = 'itsiug_theme/global-styling';
    $form['#prefix'] = '<div class="itsiug-admin-page itsiug-questions-admin">';
    $form['#suffix'] = '</div>';

    $form['heading'] = [
      '#markup' => '<h1>' . $this->questionManager->getSessionHeading($session) . '</h1>',
    ];

    $form['intro'] = [
      '#markup' => '<p class="itsiug-admin-intro">'
        . $this->t('Set a question to Hidden to remove it from the presenter board.')
        . '</p>',
    ];

    $form['board_link'] = [
      '#type' => 'link',
      '#title' => $this->t('Open presenter board'),
      '#url' => Url::fromRoute('itsiug_questions.board', ['session_number' => $session_number]),
      '#attributes' => ['class' => ['button']],
    ];

    $form['questions'] = [
      '#type' => 'table',
      '#attributes' => ['class' => ['itsiug-delegate-management-table']],
      '#header' => [
        $this->t('No.'),
        $this->t('Display name'),
        $this->t('Institution'),
        $this->t('Question'),
        $this->t('State'),
      ],
      '#empty' => $this->t('No questions have been submitted for this session.'),
    ];

    foreach ($this->questionManager->loadSessionQuestions($session) as $question) {
      $delegate = $question->get('field_question_delegate')->entity;
      $id = (int) $question->id();

      $form['questions'][$id]['number'] = [
        '#plain_text' => $question->get('field_question_number')->value,
      ];

      $form['questions'][$id]['name'] = [
        '#plain_text' => $delegate
          ? $this->questionManager->getDelegateDisplayName($delegate)
          : '',
      ];

      $form['questions'][$id]['institution'] = [
        '#plain_text' => $delegate
          ? $this->questionManager->getDelegateInstitutionName($delegate)
          : '',
      ];

      $form['questions'][$id]['question'] = [
        '#plain_text' => $question->get('field_question_text')->value,
      ];

      $form['questions'][$id]['state'] = [
        '#type' => 'select',
        '#title' => $this->t('State'),
        '#title_display' => 'invisible',
        '#options' => [
          'new' => $this->t('New'),
          'asked' => $this->t('Asked'),
          'answered' => $this->t('Answered'),
          'hidden' => $this->t('Hidden'),
        ],
        '#default_value' => $question->get('field_question_state')->isEmpty()
          ? 'new'
          : $question->get('field_question_state')->value,
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Save changes'),
        '#button_type' => 'primary',
      ],
      'back' => [
        '#type' => 'link',
        '#title' => $this->t('← Back to Session Questions'),
        '#url' => Url::fromRoute('itsiug_questions.admin'),
        '#attributes' => ['class' => ['button']],
      ],
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $storage = $this->entityTypeManager->getStorage('node');
    $changed = 0;

    foreach ((array) $form_state->getValue('questions') as $id => $values) {
      $question = $storage->load($id);

      if (!$question || $question->bundle() !== 'question') {
        continue;
      }

      $current = $question->get('field_question_state')->isEmpty()
        ? 'new'
        : $question->get('field_question_state')->value;

      if ($current === $values['state']) {
        continue;
      }

      $question->set('field_question_state', $values['state'])->save();
      $changed++;
    }

    $this->messenger()->addStatus($this->t('Updated @count question(s).', ['@count' => $changed]));

    $form_state->setRedirect('itsiug_questions.admin_session', [
      'session_number' => $form_state->get('session_number'),
    ]);
  }

}
