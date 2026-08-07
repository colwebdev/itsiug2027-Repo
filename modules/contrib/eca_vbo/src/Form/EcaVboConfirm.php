<?php

namespace Drupal\eca_vbo\Form;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Action\ActionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\eca_vbo\Event\EcaVboEvents;
use Drupal\eca_vbo\Event\VboConfirmFormBuildEvent;
use Drupal\eca_vbo\Event\VboConfirmFormSubmitEvent;
use Drupal\eca_vbo\Event\VboConfirmFormValidateEvent;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Drupal\views_bulk_operations\Form\ConfirmAction;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Confirm form of a ECA VBO action.
 */
class EcaVboConfirm extends ConfirmAction {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected EventDispatcherInterface $eventDispatcher;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->setEventDispatcher($container->get('event_dispatcher'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'eca_vbo_confirm_action';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $view_id = NULL, $display_id = NULL): array {
    $parentForm = parent::buildForm($form, $form_state, $view_id, $display_id);
    if (!is_array($parentForm)) {
      return $form;
    }
    $form = $parentForm;
    $form_data = $this->getFormData($view_id, $display_id);
    $action = $this->getConfiguredAction($form_data);
    $config = $action instanceof ConfigurableInterface ? $action->getConfiguration() : ($form_data['configuration'] ?? []);
    $view = $this->getView($form_data);
    $event = new VboConfirmFormBuildEvent($form, $form_state, $view, $form_data, $config, $action);
    $this->eventDispatcher->dispatch($event, EcaVboEvents::CONFIRM_FORM_BUILD);
    if ($action instanceof ConfigurableInterface) {
      $action->setConfiguration($config);
      $form_data['configuration'] = $action->getConfiguration();
    }
    else {
      $form_data['configuration'] = $config;
    }
    $form_state->set('views_bulk_operations', $form_data);

    if (!empty($form_data['preconfiguration']['skip_confirm'])) {
      $this->submitForm($form, $form_state);
      $messenger = $this->messenger();
      $messages = $messenger->all();
      $messenger->deleteAll();
      $redirect_url = $form_state->getRedirect();
      if ($redirect_url instanceof Url) {
        $response = new RedirectResponse($redirect_url->setAbsolute(FALSE)->toString());
        $this->eventDispatcher->addListener(KernelEvents::RESPONSE, function ($event) use ($response, $messages, $messenger) {
          foreach ($messages as $message_type => $message_list) {
            foreach ($message_list as $message) {
              $messenger->addMessage($message, $message_type);
            }
          }
          $event->setResponse($response);
        });
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $form_data = $form_state->get('views_bulk_operations');
    $action = $this->getConfiguredAction($form_data);
    $config = $action instanceof ConfigurableInterface ? $action->getConfiguration() : [];
    $event = new VboConfirmFormValidateEvent($form, $form_state, $this->getView($form_data), $form_data, $config, $action);
    $this->eventDispatcher->dispatch($event, EcaVboEvents::CONFIRM_FORM_VALIDATE);
    if ($action instanceof ConfigurableInterface) {
      $action->setConfiguration($config);
      $form_data['configuration'] = $action->getConfiguration();
    }
    else {
      $form_data['configuration'] = $config;
    }
    $form_state->set('views_bulk_operations', $form_data);
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $form_data = $form_state->get('views_bulk_operations');
    $action = $this->getConfiguredAction($form_data);
    $config = $action instanceof ConfigurableInterface ? $action->getConfiguration() : [];
    $event = new VboConfirmFormSubmitEvent($form, $form_state, $this->getView($form_data), $form_data, $config, $action);
    $this->eventDispatcher->dispatch($event, EcaVboEvents::CONFIRM_FORM_SUBMIT);
    if ($action instanceof ConfigurableInterface) {
      $action->setConfiguration($config);
      $form_data['configuration'] = $action->getConfiguration();
    }
    else {
      $form_data['configuration'] = $config;
    }
    $form_state->set('views_bulk_operations', $form_data);
    parent::submitForm($form, $form_state);
  }

  /**
   * Set the event dispatcher.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function setEventDispatcher(EventDispatcherInterface $event_dispatcher): void {
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * Get the configured action.
   *
   * @param array $form_data
   *   The form data.
   *
   * @return \Drupal\Core\Action\ActionInterface|null
   *   The configured action.
   */
  protected function getConfiguredAction(array $form_data): ?ActionInterface {
    $action = NULL;
    try {
      /** @var \Drupal\Core\Action\ActionInterface|null $action */
      $action = $this->actionManager->createInstance($form_data['action_id'], ($form_data['configuration'] ?? []));
    }
    catch (PluginException) {
      // Can be ignored.
    }
    return $action;
  }

  /**
   * Get the view.
   *
   * @param array $form_data
   *   The form data.
   *
   * @return \Drupal\views\ViewExecutable|null
   *   The view.
   */
  protected function getView(array $form_data): ?ViewExecutable {
    if ($view = Views::getView($form_data['view_id'])) {
      $view->setDisplay($form_data['display_id']);
    }
    return $view;
  }

}
