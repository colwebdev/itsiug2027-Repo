<?php

namespace Drupal\eca_vbo\Event;

use Drupal\Core\Action\ActionInterface;
use Drupal\eca\Plugin\DataType\DataTransferObject;
use Drupal\views\ViewExecutable;

/**
 * Base class for VBO execution events.
 *
 * @internal
 *   This class is not meant to be used as a public API. It is subject for name
 *   change or may be removed completely, also on minor version updates.
 */
abstract class VboExecutionEventBase extends VboEventBase {

  /**
   * Contains view data and optionally batch operation context.
   *
   * @var array
   */
  public array $actionContext;

  /**
   * The action plugin configuration.
   *
   * @var array
   */
  public array $actionConfiguration;

  /**
   * The action plugin instance.
   *
   * @var \Drupal\Core\Action\ActionInterface
   */
  protected ActionInterface $action;

  /**
   * The output of the processed result.
   *
   * @var mixed
   */
  public mixed $result;

  /**
   * Initialized event data.
   *
   * @var \Drupal\eca\Plugin\DataType\DataTransferObject|null
   */
  protected ?DataTransferObject $eventData = NULL;

  /**
   * Constructs a new VboExecutionEventBase object.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The executable view.
   * @param array &$action_context
   *   The context array passed from VBO to the action.
   * @param array &$action_configuration
   *   The action configuration.
   * @param \Drupal\Core\Action\ActionInterface $action
   *   The action plugin instance.
   * @param mixed $result
   *   (optional) The output of the processed result.
   */
  public function __construct(ViewExecutable $view, array &$action_context, array &$action_configuration, ActionInterface $action, mixed $result = NULL) {
    $this->view = $view;
    $this->actionContext = &$action_context;
    if (!isset($action_configuration['operation_name'])) {
      $action_configuration['operation_name'] = $action->getPluginDefinition()['operation_name'] ?? '';
    }
    $this->actionConfiguration = &$action_configuration;
    $this->operationName = &$action_configuration['operation_name'];
    $this->action = $action;
    $this->result = $result;
  }

  /**
   * Get the action plugin instance.
   *
   * @return \Drupal\Core\Action\ActionInterface
   *   The action plugin instance.
   */
  public function getAction(): ActionInterface {
    return $this->action;
  }

}
