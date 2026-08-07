<?php

declare(strict_types=1);

namespace Drupal\smart_menu_links;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a smart menu links entity type.
 */
interface SmartMenuLinkInterface extends ConfigEntityInterface {

  /**
   *
   */
  public function getPluginDefinition();

}
