<?php

namespace Drupal\csv_import_user\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for user import CSV functionality.
 */
class UserImportController extends ControllerBase {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The Form Builder.
   */
  public function __construct(FormBuilderInterface $formBuilder) {
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('form_builder')
    );
  }

  /**
   * Displays the form for importing users.
   */
  public function importForm() {
    return $this->formBuilder->getForm('Drupal\csv_import_user\Form\UserImportCsvForm');
  }

}
