<?php

namespace Drupal\eca_vbo\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\views\ViewExecutable;

/**
 * Base class for VBO events.
 *
 * @internal
 *   This class is not meant to be used as a public API. It is subject for name
 *   change or may be removed completely, also on minor version updates.
 */
abstract class VboEventBase extends Event {

  /**
   * The executable view.
   *
   * @var \Drupal\views\ViewExecutable
   */
  protected ViewExecutable $view;

  /**
   * The operation name, or NULL if not defined.
   *
   * @var string|null
   */
  protected ?string $operationName = NULL;

  /**
   * Get the view.
   *
   * @return \Drupal\views\ViewExecutable
   *   The view.
   */
  public function getView(): ViewExecutable {
    return $this->view;
  }

  /**
   * Get the operation name if available.
   *
   * @return string|null
   *   The operation name if available, NULL otherwise.
   */
  public function getOperationName(): ?string {
    return $this->operationName;
  }

}
