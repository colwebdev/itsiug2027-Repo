<?php

namespace Drupal\content_moderation_link\Controller;

use Drupal\content_moderation\StateTransitionValidationInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Returns responses for Content Moderation Link routes.
 */
class ContentModerationLinkController extends ControllerBase {
  use MessengerTrait;

  /**
   * The state transition validation service.
   *
   * @var \Drupal\content_moderation\StateTransitionValidationInterface
   */
  protected $validator;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\content_moderation\StateTransitionValidationInterface $validator
   *   State transition validator.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(StateTransitionValidationInterface $validator, EntityTypeManagerInterface $entity_type_manager) {
    $this->validator = $validator;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('content_moderation.state_transition_validation'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Attempt to moderate the specified content, and redirect.
   */
  public function moderate($state, $type, $id) {
    // ex. /content-moderation-link/process/published/node/{id}
    // ex. /content-moderation-link/process/in_review/node/108
    // ex. /content-moderation-link/process/draft/node/108
    // ex. /content-moderation-link/process/in_review/node/108,109
    // ex. /content-moderation-link/process/in_review/node/88,107.
    // Require login.
    $account = $this->currentUser();
    if ($account->isAnonymous()) {
      return $this->redirect('user.login', $this->getDestinationArray());
    }

    $config = $this->config('content_moderation_link.settings');
    $skip_errors = $config->get('skip_errors');
    $destination = $config->get('destination') ?: '<front>';
    $valid_types = $config->get('entity_types') ?? [];
    if ($valid_types && !in_array($type, $valid_types, TRUE)) {
      // Log error and escape.
      $this->messenger()->addError('Invalid entity type specified.');
      return $this->redirect($destination);
    }

    // Look for a matching state ending with the specified state.
    $match_found = FALSE;
    $valid_states = $config->get('moderation_states') ?? [];
    // To maintain legacy functionality, look for an exact or end match.
    if (in_array($state, $valid_states) || $this->findAmongWorkflows($valid_states, $state)) {
      $match_found = TRUE;
    }
    if ($valid_states && !$match_found) {
      // Log error and escape.
      $this->messenger()->addError('Invalid moderation state specified.');
      return $this->redirect($destination);
    }
    $workflow = NULL;
    if (str_contains($state, '-')) {
      // If the state contains the workflow identifier, parse it out.
      // $state = substr($state, strrpos($state, '-') + 1);
      [$worflow, $state] = explode('-', $state, 2);
    }

    $storage = $this->entityTypeManager()->getStorage($type);

    if (!$storage) {
      // Log error and escape.
      $this->messenger()->addError('Unable to use the specified entity type.');
      return $this->redirect($destination);
    }

    $processed = [];

    // Parse multiple values.
    $parsed_ids = explode(',', $id);
    if (!$config->get('allow_multiple')) {
      $parsed_ids = array_slice($parsed_ids, 0, 1);
    }

    // Step through ids.
    // @todo Prevalidate IDs and process none if one is invalid and $skip_errors
    // is false.
    foreach ($parsed_ids as $parsed_id) {
      $entity = $storage->load($parsed_id);
      if (!$entity) {
        // Throw error.
        $message = $this->t('ID @id is invalid for a @type entity.', [
          '@id' => $parsed_id,
          '@type' => $type,
        ]);
        if ($skip_errors) {
          $this->messenger()->addWarning($message);
          continue;
        }
        else {
          $this->postMessages($processed, $type, $state);
          $this->messenger()->addError($message);
          return $this->redirect($destination);
        }
      }

      // Access check.
      $is_valid = FALSE;

      $valid_transitions = $this->validator->getValidTransitions($entity, $account);
      foreach ($valid_transitions as $transition) {
        if ($transition->to()->id() == $state) {
          $is_valid = TRUE;
          break;
        }
      }
      if (!$is_valid) {
        // Throw warning.
        $message = $this->t('Attempt to moderate @type entity @id without valid transition.', [
          '@id' => $parsed_id,
          '@type' => $type,
        ]);
        $this->messenger()->addWarning($message);
        continue;
      }
      // Non-specific state used, need to explicitly validate.
      if ($valid_states && !$workflow) {
        // Find the appropriate workflow for the current entity.
        $bundle = $entity->bundle();
        $query = $this->entityTypeManager->getStorage('workflow')->getQuery();
        $query->condition('type_settings.entity_types.' . $type . '.*', $bundle);
        $workflows = $query->execute();
        $workflow = array_shift($workflows);
        // Make sure the entity-specific workflow state is allowlisted.
        $full_state = $workflow . '-' . $state;
        if (!in_array($full_state, $valid_states)) {
          // Throw warning.
          $message = $this->t('Attempt to moderate @type entity @id with mismatched workflow', [
            '@id' => $parsed_id,
            '@type' => $type,
          ]);
          $this->messenger()->addWarning($message);
          continue;
        }
      }

      // Change state.
      $entity->set('moderation_state', $state);

      // Allow other modules to alter the entity.
      \Drupal::moduleHandler()->invokeAll('content_moderation_link_alter_entity', [&$entity]);

      // Allow other modules to alter the account.
      \Drupal::moduleHandler()->invokeAll('content_moderation_link_alter_account', [&$account]);

      // Set revision log, if configured.
      if ($entity instanceof RevisionLogInterface) {
        // @todo populate from config.
        $entity->setRevisionLogMessage('Changed moderation state to @state via Content Moderation Link.', ['@state' => $state]);
        $entity->setRevisionUserId($account->id());
      }

      // Save.
      $entity->save();

      // Log processed data.
      $processed[] = ['id' => $parsed_id, 'title' => $entity->label()];

      // Continue ids.
    }

    $this->postMessages($processed, $type, $state);

    // Can only redirect to entity if a single one processed.
    if (count($processed) === 1) {
      $url = $entity->toUrl();
      if ($url->access()) {
        return new RedirectResponse($url->toString(), 302);
      }
    }

    // Default to redirect to the configured destination.
    return $this->redirect($destination);
  }

  /**
   * {@inheritdoc}
   */
  public function postMessages($processed, $type, $state) {
    if ($processed) {
      $this->messenger()->addStatus('The following content was successfully processed:');
      foreach ($processed as $data) {
        $message = $this->t('Moderated @type @id to @state: @title', [
          '@id' => $data['id'],
          '@type' => $type,
          '@state' => $state,
          '@title' => $data['title'],
        ]);
        $this->messenger()->addStatus($message);
      }
    }
  }

  /**
   * Look for an array value ending with the state in the URL.
   *
   * @param array $valid_states
   *   The configured set of allowed workflow states.
   * @param string $state
   *   The state to look for.
   *
   * @return void
   *   A matching array key.
   */
  protected function findAmongWorkflows(array $valid_states, string $state) {
    foreach ($valid_states as $valid_state) {
      if (str_ends_with($valid_state, '-' . $state)) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
