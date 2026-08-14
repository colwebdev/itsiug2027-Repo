<?php

declare(strict_types=1);

namespace Drupal\itsiug_questions\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\itsiug_questions\Service\QuestionManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Review and export pages for submitted questions.
 */
class QuestionAdminController extends ControllerBase {

  public function __construct(
    protected QuestionManager $questionManager,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static($container->get('itsiug_questions.manager'));
  }

  /**
   * List sessions that have received questions.
   */
  public function index(): array {
    $rows = [];

    foreach ($this->questionManager->getSessionsWithQuestions() as $item) {
      $session_number = (string) $item['number'];

      $rows[] = [
        'session' => $this->questionManager->getSessionHeading($item['session']),
        'count' => $item['count'],
        'board' => [
          'data' => [
            '#type' => 'link',
            '#title' => $this->t('Presenter board'),
            '#url' => Url::fromRoute('itsiug_questions.board', ['session_number' => $session_number]),
          ],
        ],
        'review' => [
          'data' => [
            '#type' => 'link',
            '#title' => $this->t('Review'),
            '#url' => Url::fromRoute('itsiug_questions.admin_session', ['session_number' => $session_number]),
          ],
        ],
        'csv' => [
          'data' => [
            '#type' => 'link',
            '#title' => $this->t('CSV'),
            '#url' => Url::fromRoute('itsiug_questions.admin_csv', ['session_number' => $session_number]),
          ],
        ],
      ];
    }

    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['itsiug-admin-page', 'itsiug-questions-admin']],
      '#attached' => ['library' => ['itsiug_theme/global-styling']],
      '#cache' => ['max-age' => 0],
      'heading' => [
        '#markup' => '<h1>' . $this->t('Session questions') . '</h1>',
      ],
      'intro' => [
        '#markup' => '<p class="itsiug-admin-intro">'
          . $this->t('Review the questions delegates submitted during each session.')
          . '</p>',
      ],
      'table' => [
        '#type' => 'table',
        '#attributes' => ['class' => ['itsiug-delegate-management-table']],
        '#header' => [
          $this->t('Session'),
          $this->t('Questions'),
          $this->t('Board'),
          $this->t('Review'),
          $this->t('Export'),
        ],
        '#rows' => $rows,
        '#empty' => $this->t('No questions have been submitted yet.'),
      ],
    ];
  }

  /**
   * Download the questions for one session as CSV.
   */
  public function csv(string $session_number): StreamedResponse {
    $session = ctype_digit($session_number)
      ? $this->questionManager->findSessionByNumber((int) $session_number)
      : null;

    if (!$session) {
      throw new NotFoundHttpException();
    }

    $questions = $this->questionManager->loadSessionQuestions($session);

    $response = new StreamedResponse(function () use ($questions): void {
      $handle = fopen('php://output', 'w');

      fputcsv($handle, ['Number', 'Display name', 'Institution', 'Question', 'State', 'Submitted']);

      foreach ($questions as $question) {
        $delegate = $question->get('field_question_delegate')->entity;

        fputcsv($handle, [
          $question->get('field_question_number')->value,
          $delegate ? $this->questionManager->getDelegateDisplayName($delegate) : '',
          $delegate ? $this->questionManager->getDelegateInstitutionName($delegate) : '',
          $question->get('field_question_text')->value,
          $question->get('field_question_state')->value,
          date('Y-m-d H:i', (int) $question->getCreatedTime()),
        ]);
      }

      fclose($handle);
    });

    $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
    $response->headers->set(
      'Content-Disposition',
      'attachment; filename="session-' . $session_number . '-questions.csv"',
    );

    return $response;
  }

}
