<?php

declare(strict_types=1);

namespace Drupal\smart_menu_links\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\smart_menu_links\SmartMenuLinkInterface;

/**
 * Defines the smart menu links entity type.
 *
 * @ConfigEntityType(
 *   id = "smart_menu_link",
 *   label = @Translation("Smart menu link"),
 *   label_collection = @Translation("Smart menu links"),
 *   label_singular = @Translation("smart menu link"),
 *   label_plural = @Translation("smart menu links"),
 *   label_count = @PluralTranslation(
 *     singular = "@count smart menu link",
 *     plural = "@count smart menu links",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\smart_menu_links\SmartMenuLinkListBuilder",
 *     "form" = {
 *       "add" = "Drupal\smart_menu_links\Form\SmartMenuLinkForm",
 *       "edit" = "Drupal\smart_menu_links\Form\SmartMenuLinkForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *   },
 *   config_prefix = "smart_menu_link",
 *   admin_permission = "administer smart_menu_link",
 *   links = {
 *     "collection" = "/admin/structure/smart-menu-link",
 *     "add-form" = "/admin/structure/smart-menu-link/add",
 *     "edit-form" = "/admin/structure/smart-menu-link/{smart_menu_link}",
 *     "delete-form" = "/admin/structure/smart-menu-link/{smart_menu_link}/delete",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "link_text",
 *     "link_suffix",
 *     "menu_name",
 *     "parent",
 *     "weight",
 *     "expanded",
 *     "enabled",
 *     "target_entity_type",
 *     "target_bundle",
 *     "moderation_states",
 *     "source_path_segment",
 *     "source_id_or_name",
 *     "fallback",
 *     "validate_segment",
 *     "validate_segment_id",
 *     "validate_segment_value",
 *   },
 * )
 */
final class SmartMenuLink extends ConfigEntityBase implements SmartMenuLinkInterface {

  /**
   * The ID.
   */
  protected string $id;

  /**
   * The label.
   */
  protected string $label;

  /**
   * The link text.
   */
  protected string $link_text;

  /**
   * The link suffix.
   */
  protected string $link_suffix;

  /**
   * The parent menu.
   */
  protected string $menu_name;

  /**
   * The parent menu item.
   */
  protected string $parent;

  /**
   * The menu weight.
   */
  protected int $weight = 0;

  /**
   * Whether or not the menu item should be expanded.
   */
  protected ?bool $expanded;

  /**
   * Whether or not the menu item is enabled.
   */
  protected ?bool $enabled = TRUE;

  /**
   * The target entity type.
   */
  protected ?string $target_entity_type = '';

  /**
   * The target bundles.
   */
  protected ?string $target_bundle = '';

  /**
   * The moderation states.
   */
  protected ?array $moderation_states = [];

  /**
   * The source path segment.
   */
  protected ?int $source_path_segment = 1;

  /**
   * Whether the source is an ID or a name.
   */
  protected ?string $source_id_or_name = 'id';

  /**
   * A fallback value.
   */
  protected ?string $fallback = '';

  /**
   * Which segment to validate.
   */
  protected ?int $validate_segment_id;

  /**
   * The value to validate.
   */
  protected ?string $validate_segment_value = '';

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return (bool) $this->enabled;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition() {
    $plugin_definition = [];
    $plugin_definition['title'] = $this->getTitle();
    $plugin_definition['link_suffix'] = $this->link_suffix;
    $plugin_definition['menu_name'] = $this->menu_name;
    $plugin_definition['parent'] = $this->parent;
    $plugin_definition['enabled'] = $this->isEnabled() ? 1 : 0;
    $plugin_definition['expanded'] = ($this->expanded) ? 1 : 0;
    $plugin_definition['weight'] = $this->weight;
    $plugin_definition['target_entity_type'] = $this->target_entity_type;
    $plugin_definition['target_bundle'] = $this->target_bundle;
    $plugin_definition['moderation_states'] = $this->moderation_states;
    $plugin_definition['source_path_segment'] = $this->source_path_segment;
    $plugin_definition['fallback'] = $this->fallback;
    $plugin_definition['validate_segment_id'] = $this->validate_segment_id;
    $plugin_definition['validate_segment_value'] = $this->validate_segment_value;
    $plugin_definition['metadata']['entity_id'] = $this->id;
    $plugin_definition['class'] = 'Drupal\smart_menu_links\Plugin\Menu\SmartMenuLink';
    $plugin_definition['form_class'] = 'Drupal\smart_menu_links\Form\SmartMenuLinkForm';

    return $plugin_definition;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->link_text;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return 'smart_menu_links:' . $this->id();
  }

}
