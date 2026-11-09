<?php

namespace Drupal\itsiug_registration\EventSubscriber;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Expires inactive institution registration sessions.
 */
class RegistrationSessionSubscriber implements EventSubscriberInterface {

  /**
   * Registration inactivity timeout in seconds.
   */
  private const INACTIVITY_TIMEOUT = 900;

  /**
   * Routes requiring active institution registration context.
   */
  private const REGISTRATION_ROUTES = [
    'itsiug_registration.delegate',
    'itsiug_registration.exhibitor',
    'itsiug_registration.delegate_confirmation',
    'itsiug_registration.exhibitor_confirmation',
  ];

  /**
   * Constructs the subscriber.
   */
  public function __construct(
    private readonly TimeInterface $time,
    private readonly MessengerInterface $messenger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::REQUEST => ['onKernelRequest', 20],
    ];
  }

  /**
   * Refresh or expire registration context on workflow requests.
   */
  public function onKernelRequest(RequestEvent $event): void {
    if (!$event->isMainRequest()) {
      return;
    }

    $request = $event->getRequest();
    $route = $request->attributes->get('_route');
    if (!in_array($route, self::REGISTRATION_ROUTES, TRUE)) {
      return;
    }

    $session = $request->getSession();
    $registration = $session->get('itsiug_registration');
    if (empty($registration['institution_nid'])) {
      return;
    }

    $last_activity = (int) ($registration['last_activity'] ?? $registration['authenticated_at'] ?? 0);
    $now = $this->time->getRequestTime();

    if ($last_activity === 0 || ($now - $last_activity) >= self::INACTIVITY_TIMEOUT) {
      $session->remove('itsiug_registration');
      $this->messenger->addError(t('Your registration session expired due to inactivity. Please enter your Institution Code and Registration PIN again.'));

      $flow = ($registration['registration_flow'] ?? 'delegate') === 'exhibitor'
        ? 'exhibitor'
        : 'delegate';
      $url = Url::fromRoute('itsiug_registration.access', [], [
        'query' => ['flow' => $flow],
      ])->toString();
      $event->setResponse(new RedirectResponse($url));
      return;
    }

    $registration['last_activity'] = $now;
    $session->set('itsiug_registration', $registration);
  }

}
