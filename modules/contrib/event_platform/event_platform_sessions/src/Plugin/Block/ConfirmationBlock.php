<?php

namespace Drupal\event_platform_sessions\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirmation block.
 *
 * @Block(
 *   id = "event_platform_sessions_confirmation",
 *   admin_label = @Translation("Session Confirmation"),
 *   category = @Translation("Event Platform")
 * )
 */
class ConfirmationBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new CopyrightBlock object.
   *
   * @param array $configuration
   *   The block configuration.
   * @param string $plugin_id
   *   The block plugin id.
   * @param mixed $plugin_definition
   *   The definition for the block.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $configFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $node = \Drupal::routeMatch()->getParameter('node');
    if (!$node instanceof NodeInterface) {
      return $build;
    }
    // Make sure we're dealing with an accepted session.
    $nid = $node->id();
    $bundle = $node->bundle();
    $state = $node->moderation_state->value;
    if ($bundle !== 'session' || $state !== 'accepted') {
      return $build;
    }

    // Make sure the currently logged in user is the session author.
    $current_user_id = \Drupal::currentUser()->id();
    $current_user_roles = \Drupal::currentUser()->getAccount()->getRoles(TRUE);

    if (!in_array('speaker', $current_user_roles) || $current_user_id !== $node->getOwner()->id()) {
      return $build;
    }

    $config = $this->getConfiguration();
    $message = $config['message'] ?? $this->t('Please confirm whether or not you will be able to deliver this session as proposed.');

    // Generate the moderation links.
    // @todo Is an extra step to validate permissions needed here?
    $available_states = [
      'declined' => $this->t('Decline'),
      'published' => $this->t('Confirm'),
    ];
    $links = [];
    foreach ($available_states as $state_id => $state_label) {
      $link = Link::createFromRoute(
        $state_label,
        'content_moderation_link.moderate',
        ['state' => $state_id, 'type' => 'node', 'id' => $nid],
        ['attributes' => ['class' => 'button']],
      );
      $links[$state_id] = $link->toRenderable();
    }

    $cache_tag = 'node:' . $nid;
    $block = [
      '#theme' => 'event_platform_confirmation_block',
      '#attributes' => [
        'class' => ['confirmation'],
        'id' => 'confirmation-block',
      ],
      '#message'  => $message,
      '#links'  => $links,
      '#cache' => [
        'tags' => [$cache_tag],
      ],
    ];
    $build['event_platform_details_confirmation'] = $block;
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'message' => $this->t('Please confirm whether or not you will be able to deliver this session as proposed.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#description' => $this->t('Please provide the message that should appear above the confirm/decline links.'),
      '#default_value' => $this->configuration['message'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->configuration['message'] = $values['message'];
  }

}
