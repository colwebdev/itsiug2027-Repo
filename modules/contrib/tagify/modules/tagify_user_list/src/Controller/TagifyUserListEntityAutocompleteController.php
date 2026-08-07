<?php

declare(strict_types=1);

namespace Drupal\tagify_user_list\Controller;

use Drupal\tagify\Controller\TagifyEntityAutocompleteController;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a route controller for user entity autocomplete form elements.
 */
class TagifyUserListEntityAutocompleteController extends TagifyEntityAutocompleteController {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('tagify_user_list.autocomplete_matcher')
    );
  }

}
