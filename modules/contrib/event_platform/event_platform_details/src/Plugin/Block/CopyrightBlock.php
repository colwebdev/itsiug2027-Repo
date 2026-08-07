<?php

namespace Drupal\event_platform_details\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\config_pages\Entity\ConfigPages;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a copyright block.
 *
 * @Block(
 *   id = "event_platform_details_copyright",
 *   admin_label = @Translation("Copyright"),
 *   category = @Translation("Event Platform")
 * )
 */
class CopyrightBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
    $configPage = ConfigPages::config('event_details');
    if ($configPage) {
      $site_name = $configPage->get('field_event_name')->value ?? NULL;
      $cache_tag = 'config_pages:' . $configPage->id();
    }
    else {
      $site_name = NULL;
      $cache_tag = 'config_pages_list';
    }
    if (!$site_name) {
      $site_name = $this->configFactory->get('system.site')->get('name');
    }

    $block = [
      '#theme' => 'event_platform_copyright_block',
      '#attributes' => [
        'class' => ['copyright'],
        'id' => 'copyright-block',
      ],
      '#org_name'  => $site_name,
      '#year'  => date('Y'),
      '#cache' => [
        'tags' => [$cache_tag],
      ],
    ];
    $build['event_platform_details_copyright'] = $block;
    return $build;
  }

}
