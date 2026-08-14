<?php

declare(strict_types=1);

namespace Drupal\itsiug_questions\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;

/**
 * Resolves delegates and sessions, and records submitted questions.
 */
class QuestionManager {

  use StringTranslationTrait;

  /**
   * Maximum questions one delegate may submit for a single session.
   */
  public const MAX_PER_DELEGATE_PER_SESSION = 5;

  /**
   * Maximum length of a submitted question.
   */
  public const MAX_QUESTION_LENGTH = 500;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LockBackendInterface $lock,
  ) {}

  /**
   * Normalise a scanned or typed badge value into a bare QR ID.
   *
   * Badges encode the scanner URL, so accept both that and a plain ID.
   */
  public function normaliseQrCode(string $value): string {
    $value = trim($value);

    if ($value === '') {
      return '';
    }

    $parsed = parse_url($value);

    if (!empty($parsed['query'])) {
      parse_str($parsed['query'], $query);
      $value = trim((string) ($query['qr'] ?? $value));
    }

    // Badges generated before the institution code update used the long prefix.
    return str_replace('ITSIUG2027', 'ITSIUG', $value);
  }

  /**
   * Load the delegate node for a badge QR ID.
   */
  public function findDelegateByQrCode(string $qr_code): ?NodeInterface {
    $registration = $this->findRegistrationByQrCode($qr_code);

    if (!$registration) {
      return null;
    }

    $delegate = $registration->get('field_delegate')->entity;

    return $delegate instanceof NodeInterface ? $delegate : null;
  }

  /**
   * Load the conference registration node for a badge QR ID.
   */
  public function findRegistrationByQrCode(string $qr_code): ?NodeInterface {
    $qr_code = $this->normaliseQrCode($qr_code);

    if ($qr_code === '') {
      return null;
    }

    $ids = $this->entityTypeManager->getStorage('node')->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'conference_registration')
      ->condition('field_qr_code', $qr_code)
      ->range(0, 1)
      ->execute();

    if (!$ids) {
      return null;
    }

    $registration = $this->entityTypeManager->getStorage('node')
      ->load(reset($ids));

    return $registration instanceof NodeInterface ? $registration : null;
  }

  /**
   * Resolve the institution name shown next to a delegate.
   */
  public function getInstitutionName(string $qr_code): string {
    $registration = $this->findRegistrationByQrCode($qr_code);

    if (!$registration) {
      return '';
    }

    if (!$registration->get('field_institution1')->isEmpty()) {
      $institution = $registration->get('field_institution1')->entity;

      if ($institution instanceof NodeInterface) {
        return (string) $institution->label();
      }
    }

    $delegate = $registration->get('field_delegate')->entity;

    if ($delegate instanceof NodeInterface
      && $delegate->hasField('field_institution')
      && !$delegate->get('field_institution')->isEmpty()) {
      $institution = $delegate->get('field_institution')->entity;

      if ($institution instanceof NodeInterface) {
        return (string) $institution->label();
      }
    }

    return '';
  }

  /**
   * Load a published session by its programme number.
   */
  public function findSessionByNumber(int $session_number): ?NodeInterface {
    $ids = $this->entityTypeManager->getStorage('node')->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'session')
      ->condition('field_session_number', $session_number)
      ->range(0, 1)
      ->execute();

    if (!$ids) {
      return null;
    }

    $session = $this->entityTypeManager->getStorage('node')
      ->load(reset($ids));

    return $session instanceof NodeInterface ? $session : null;
  }

  /**
   * Count the questions a delegate has already submitted for a session.
   */
  public function countDelegateQuestions(NodeInterface $session, NodeInterface $delegate): int {
    return (int) $this->entityTypeManager->getStorage('node')->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'question')
      ->condition('field_question_session', $session->id())
      ->condition('field_question_delegate', $delegate->id())
      ->count()
      ->execute();
  }

  /**
   * Record a question and allocate its number within the session.
   *
   * The lock prevents two simultaneous submissions claiming the same number.
   */
  public function createQuestion(
    NodeInterface $session,
    NodeInterface $delegate,
    string $question_text,
  ): NodeInterface {
    $lock_name = 'itsiug_questions:session:' . $session->id();
    $acquired = $this->lock->acquire($lock_name, 10);

    if (!$acquired) {
      $this->lock->wait($lock_name, 10);
      $acquired = $this->lock->acquire($lock_name, 10);
    }

    try {
      $number = $this->getNextQuestionNumber($session);

      $node = $this->entityTypeManager->getStorage('node')->create([
        'type' => 'question',
        'title' => $this->buildTitle($session, $number),
        'status' => 1,
        'uid' => 0,
        'field_question_session' => ['target_id' => $session->id()],
        'field_question_delegate' => ['target_id' => $delegate->id()],
        'field_question_number' => $number,
        'field_question_text' => mb_substr(trim($question_text), 0, self::MAX_QUESTION_LENGTH),
        'field_question_state' => 'new',
      ]);

      $node->save();
    }
    finally {
      if ($acquired) {
        $this->lock->release($lock_name);
      }
    }

    return $node;
  }

  /**
   * Determine the next sequence number within a session.
   */
  protected function getNextQuestionNumber(NodeInterface $session): int {
    $ids = $this->entityTypeManager->getStorage('node')->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'question')
      ->condition('field_question_session', $session->id())
      ->sort('field_question_number', 'DESC')
      ->range(0, 1)
      ->execute();

    if (!$ids) {
      return 1;
    }

    $last = $this->entityTypeManager->getStorage('node')->load(reset($ids));

    if (!$last instanceof NodeInterface || $last->get('field_question_number')->isEmpty()) {
      return 1;
    }

    return ((int) $last->get('field_question_number')->value) + 1;
  }

  /**
   * Build the administrative title for a question node.
   */
  protected function buildTitle(NodeInterface $session, int $number): string {
    $session_number = $session->get('field_session_number')->isEmpty()
      ? ''
      : (string) $session->get('field_session_number')->value;

    $label = $session_number !== ''
      ? 'Session ' . $session_number
      : (string) $session->label();

    return mb_substr($label . ' – Q' . $number, 0, 255);
  }

  /**
   * Build the display name shown on the presenter board.
   */
  public function getDelegateDisplayName(NodeInterface $delegate): string {
    $first = $delegate->hasField('field_first_name') && !$delegate->get('field_first_name')->isEmpty()
      ? trim((string) $delegate->get('field_first_name')->value)
      : '';

    $last = $delegate->hasField('field_last_name') && !$delegate->get('field_last_name')->isEmpty()
      ? trim((string) $delegate->get('field_last_name')->value)
      : '';

    $name = trim($first . ' ' . $last);

    return $name !== '' ? $name : (string) $delegate->label();
  }

  /**
   * Build the institution name for a delegate node.
   */
  public function getDelegateInstitutionName(NodeInterface $delegate): string {
    if (!$delegate->hasField('field_institution') || $delegate->get('field_institution')->isEmpty()) {
      return '';
    }

    $institution = $delegate->get('field_institution')->entity;

    return $institution instanceof NodeInterface ? (string) $institution->label() : '';
  }

  /**
   * Build the short institution code for a delegate, e.g. "ITSIUG".
   *
   * Falls back to the full institution name when no code is set.
   */
  public function getDelegateInstitutionCode(NodeInterface $delegate): string {
    if (!$delegate->hasField('field_institution') || $delegate->get('field_institution')->isEmpty()) {
      return '';
    }

    $institution = $delegate->get('field_institution')->entity;

    if (!$institution instanceof NodeInterface) {
      return '';
    }

    if ($institution->hasField('field_institution_code')
      && !$institution->get('field_institution_code')->isEmpty()) {
      return trim((string) $institution->get('field_institution_code')->value);
    }

    return (string) $institution->label();
  }

  /**
   * Build the session heading, e.g. "Session 24 – HR/Payroll System".
   */
  public function getSessionHeading(NodeInterface $session): string {    $parts = [];

    if (!$session->get('field_session_number')->isEmpty()) {
      $parts[] = 'Session ' . (string) $session->get('field_session_number')->value;
    }

    if ($session->hasField('field_track') && !$session->get('field_track')->isEmpty()) {
      $track = $session->get('field_track')->entity;

      if ($track) {
        $parts[] = (string) $track->label();
      }
    }

    if (!$parts) {
      return (string) $session->label();
    }

    return implode(' – ', $parts);
  }

  /**
   * Load the visible questions for a session, oldest first.
   *
   * @param int $since
   *   Only return questions with a node ID greater than this, for polling.
   */
  public function getSessionQuestions(NodeInterface $session, int $since = 0): array {
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'question')
      ->condition('field_question_session', $session->id())
      ->condition('field_question_state', 'hidden', '<>')
      ->sort('field_question_number', 'ASC');

    if ($since > 0) {
      $query->condition('nid', $since, '>');
    }

    $ids = $query->execute();

    if (!$ids) {
      return [];
    }

    $rows = [];

    foreach ($this->entityTypeManager->getStorage('node')->loadMultiple($ids) as $question) {
      $delegate = $question->get('field_question_delegate')->entity;

      $rows[] = [
        'id' => (int) $question->id(),
        'number' => (int) $question->get('field_question_number')->value,
        'name' => $delegate instanceof NodeInterface
          ? $this->getDelegateDisplayName($delegate)
          : '',
        'institution' => $delegate instanceof NodeInterface
          ? $this->getDelegateInstitutionCode($delegate)
          : '',
        'question' => (string) $question->get('field_question_text')->value,
        'state' => $question->get('field_question_state')->isEmpty()
          ? 'new'
          : (string) $question->get('field_question_state')->value,
      ];
    }

    return $rows;
  }

  /**
   * Load every question node for a session, including hidden ones.
   *
   * @return \Drupal\node\NodeInterface[]
   */
  public function loadSessionQuestions(NodeInterface $session): array {
    $ids = $this->entityTypeManager->getStorage('node')->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'question')
      ->condition('field_question_session', $session->id())
      ->sort('field_question_number', 'ASC')
      ->execute();

    return $ids
      ? $this->entityTypeManager->getStorage('node')->loadMultiple($ids)
      : [];
  }

  /**
   * List sessions that have questions, with their counts.
   *
   * @return array[]
   *   Each item has 'session' and 'count' keys, ordered by session number.
   */
  public function getSessionsWithQuestions(): array {
    $ids = $this->entityTypeManager->getStorage('node')->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'question')
      ->execute();

    if (!$ids) {
      return [];
    }

    $counts = [];

    foreach ($this->entityTypeManager->getStorage('node')->loadMultiple($ids) as $question) {
      $session_id = (int) $question->get('field_question_session')->target_id;

      if ($session_id) {
        $counts[$session_id] = ($counts[$session_id] ?? 0) + 1;
      }
    }

    $sessions = $this->entityTypeManager->getStorage('node')
      ->loadMultiple(array_keys($counts));

    $result = [];

    foreach ($sessions as $session) {
      $result[] = [
        'session' => $session,
        'count' => $counts[(int) $session->id()],
        'number' => $session->get('field_session_number')->isEmpty()
          ? 0
          : (int) $session->get('field_session_number')->value,
      ];
    }

    usort($result, static fn (array $a, array $b): int => $a['number'] <=> $b['number']);

    return $result;
  }

}
