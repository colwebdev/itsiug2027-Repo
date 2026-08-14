<?php

declare(strict_types=1);

namespace Drupal\itsiug_questions\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\itsiug_questions\Service\QuestionManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Delegate-facing pages for the session question feature.
 */
class QuestionController extends ControllerBase {

  /**
   * Flood event name for badge lookups.
   */
  protected const LOOKUP_FLOOD_EVENT = 'itsiug_questions.lookup';

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

  /**
   * Confirm a submitted question to the delegate.
   */
  public function submitted(): array {
    $data = $this->tempStoreFactory->get('itsiug_questions')->get('last_submission');

    if (!$data) {
      return [
        '#type' => 'container',
        '#attributes' => ['class' => ['itsiug-admin-page', 'itsiug-ask-page']],
        '#attached' => ['library' => ['itsiug_questions/ask', 'itsiug_theme/global-styling']],
        'message' => [
          '#markup' => '<h1>' . $this->t('Nothing to show') . '</h1><p>'
            . $this->t('Your question was not found in this browser session.') . '</p>',
        ],
        'actions' => $this->buildConfirmationActions(''),
      ];
    }

    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['itsiug-admin-page', 'itsiug-ask-page', 'itsiug-ask-confirmation']],
      '#attached' => ['library' => ['itsiug_questions/ask', 'itsiug_theme/global-styling']],
      '#cache' => ['max-age' => 0],
      'heading' => [
        '#markup' => '<h1>' . $this->t('Thank you') . '</h1>',
      ],
      'detail' => [
        '#markup' => '<p class="itsiug-ask-confirmation-detail">' . $this->t(
          'Thanks @name, your question is number @number for @session.',
          [
            '@name' => $data['delegate_name'],
            '@number' => $data['question_number'],
            '@session' => $data['session_heading'],
          ],
        ) . '</p>',
      ],
      'actions' => $this->buildConfirmationActions((string) $data['session_number']),
    ];
  }

  /**
   * Resolve a scanned badge to a delegate name and institution.
   */
  public function lookup(Request $request): JsonResponse {
    $ip = $request->getClientIp();

    if (!$this->flood->isAllowed(self::LOOKUP_FLOOD_EVENT, 60, 3600, $ip)) {
      return new JsonResponse([
        'success' => FALSE,
        'message' => (string) $this->t('Too many badge lookups. Please try again later.'),
      ], 429);
    }

    $this->flood->register(self::LOOKUP_FLOOD_EVENT, 3600, $ip);

    $payload = json_decode((string) $request->getContent(), TRUE);
    $qr_code = $this->questionManager->normaliseQrCode(
      (string) ($payload['qr_code'] ?? '')
    );

    if ($qr_code === '') {
      return new JsonResponse([
        'success' => FALSE,
        'message' => (string) $this->t('No QR code was received.'),
      ], 400);
    }

    $delegate = $this->questionManager->findDelegateByQrCode($qr_code);

    if (!$delegate) {
      return new JsonResponse([
        'success' => FALSE,
        'message' => (string) $this->t('That badge was not recognised.'),
      ], 404);
    }

    return new JsonResponse([
      'success' => TRUE,
      'qr_code' => $qr_code,
      'delegate' => $this->questionManager->getDelegateDisplayName($delegate),
      'institution' => $this->questionManager->getInstitutionName($qr_code),
    ]);
  }

  /**
   * Build the "ask another question" link back to the form.
   */
  protected function buildAskAgainLink(string $session_number): array {
    $options = $session_number !== ''
      ? ['query' => ['s' => $session_number]]
      : [];

    return [
      '#type' => 'link',
      '#title' => $this->t('Ask another question'),
      '#url' => Url::fromRoute('itsiug_questions.ask', [], $options),
      '#attributes' => ['class' => ['button', 'button--primary']],
    ];
  }

  /**
   * Build the confirmation page buttons.
   */
  protected function buildConfirmationActions(string $session_number): array {
    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['itsiug-ask-actions']],
      'again' => $this->buildAskAgainLink($session_number),
      'done' => [
        '#type' => 'link',
        '#title' => $this->t('Done'),
        '#url' => Url::fromRoute('<front>'),
        '#attributes' => ['class' => ['button', 'itsiug-ask-done']],
      ],
    ];
  }

  /**
   * Presenter board showing the questions for one session.
   */
  public function board(string $session_number): array {
    $session = $this->loadSession($session_number);
    $rows = $this->questionManager->getSessionQuestions($session);

    $description = $session->hasField('field_description') && !$session->get('field_description')->isEmpty()
      ? (string) $session->get('field_description')->value
      : '';

    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['itsiug-board']],
      '#attached' => [
        'library' => ['itsiug_questions/board', 'itsiug_theme/global-styling'],
        'drupalSettings' => [
          'itsiugQuestions' => [
            'dataUrl' => Url::fromRoute(
              'itsiug_questions.board_data',
              ['session_number' => $session_number],
            )->toString(),
            'since' => $rows ? max(array_column($rows, 'id')) : 0,
            'interval' => 8000,
          ],
        ],
      ],
      '#cache' => ['max-age' => 0],
      'heading' => [
        '#markup' => '<h1 class="itsiug-board-heading">'
          . $this->questionManager->getSessionHeading($session) . '</h1>',
      ],
      'description' => $description === '' ? [] : [
        '#markup' => '<p class="itsiug-board-description">' . $description . '</p>',
      ],
      'table' => [
        '#type' => 'inline_template',
        '#template' => '<table class="itsiug-board-table"><thead><tr>'
          . '<th class="itsiug-board-number">{{ number_label }}</th>'
          . '<th class="itsiug-board-name">{{ name_label }}</th>'
          . '<th class="itsiug-board-institution">{{ institution_label }}</th>'
          . '<th class="itsiug-board-question">{{ question_label }}</th>'
          . '</tr></thead><tbody id="itsiug-board-rows">'
          . '{% for row in rows %}<tr data-question-id="{{ row.id }}">'
          . '<td class="itsiug-board-number">{{ row.number }}</td>'
          . '<td class="itsiug-board-name">{{ row.name }}</td>'
          . '<td class="itsiug-board-institution">{{ row.institution }}</td>'
          . '<td class="itsiug-board-question">{{ row.question }}</td>'
          . '</tr>{% endfor %}</tbody></table>',
        '#context' => [
          'rows' => $rows,
          'number_label' => $this->t('No.'),
          'name_label' => $this->t('Display name'),
          'institution_label' => $this->t('Institution'),
          'question_label' => $this->t('Question'),
        ],
      ],
      'empty' => [
        '#markup' => '<p id="itsiug-board-empty" class="itsiug-board-empty"'
          . ($rows ? ' hidden' : '') . '>'
          . $this->t('No questions yet. Scan the QR code on your table to ask one.')
          . '</p>',
      ],
      'status' => [
        '#markup' => '<p id="itsiug-board-status" class="itsiug-board-status" aria-live="polite"></p>',
      ],
    ];
  }

  /**
   * Return new questions for the board as JSON.
   */
  public function boardData(string $session_number, Request $request): JsonResponse {
    $session = $this->loadSession($session_number);
    $since = (int) $request->query->get('since', 0);

    $rows = $this->questionManager->getSessionQuestions($session, $since);

    $response = new JsonResponse([
      'rows' => $rows,
      'since' => $rows ? max(array_column($rows, 'id')) : $since,
    ]);

    // The board polls this endpoint, so it must never be cached.
    $response->setPrivate();
    $response->setMaxAge(0);
    $response->headers->set('Cache-Control', 'no-store, must-revalidate');

    return $response;
  }

  /**
   * Load a session by programme number or fail with a 404.
   */
  protected function loadSession(string $session_number) {
    $session = ctype_digit($session_number)
      ? $this->questionManager->findSessionByNumber((int) $session_number)
      : null;

    if (!$session) {
      throw new NotFoundHttpException();
    }

    return $session;
  }

}
