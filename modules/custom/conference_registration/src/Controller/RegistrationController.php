<?php

namespace Drupal\conference_registration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\RedirectResponse;

class RegistrationController extends ControllerBase {

  public function register($token) {

    $nids = \Drupal::entityQuery('node')
      ->accessCheck(TRUE)
      ->condition('type', 'institution')
      ->condition('field_registration_token', $token)
      ->range(0, 1)
      ->execute();

    if (empty($nids)) {
      return [
        '#markup' => '<h2>Invalid Registration Link</h2>',
      ];
    }

    $nid = reset($nids);
    $institution = Node::load($nid);

    // Build query parameters for Webform
    $params = [
      'institution' => $institution->label(),
      'acronym' => $institution->get('field_institution_acronym')->value ?? '',
      'token' => $token,
    ];

    $url = '/webform/event_registration?' . http_build_query($params);

    return new RedirectResponse($url);
  }
}