<?php

declare(strict_types=1);

namespace Drupal\tagify\Hook;

use Drupal\Core\Asset\LibrariesDirectoryFileFinder;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for the Tagify module.
 */
final class TagifyHooks {

  public function __construct(
    private readonly LibrariesDirectoryFileFinder $librariesDirectoryFileFinder,
    private readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Implements hook_library_info_alter().
   */
  #[Hook('library_info_alter')]
  public function libraryInfoAlter(array &$libraries, string $module): void {
    if ($module !== 'tagify') {
      return;
    }

    // In case that the libraries are included locally, use those instead of
    // the CDN.
    // @see https://www.drupal.org/node/3099614
    $current_libraries = [
      'tagify' => [
        'js' => 'tagify/dist/tagify.js',
        'css' => 'tagify/dist/tagify.css',
      ],
      'tagify_polyfils' => [
        'js' => 'tagify/dist/tagify.polyfills.min.js',
      ],
    ];
    foreach ($current_libraries as $current_library_id => $current_library_type) {
      if (!isset($libraries[$current_library_id])) {
        continue;
      }
      foreach ($current_library_type as $library_type_id => $current_library_file) {
        $path = $this->librariesDirectoryFileFinder->find($current_library_file);
        if (!$path) {
          continue;
        }
        if ($library_type_id === 'css') {
          $libraries[$current_library_id][$library_type_id]['component'] = [
            '/' . $path => [],
          ];
        }
        else {
          $libraries[$current_library_id][$library_type_id] = [
            '/' . $path => ['minified' => TRUE],
          ];
        }
      }
    }
  }

  /**
   * Implements hook_options_list_alter().
   *
   * Strip dashes prefixed to taxonomy labels.
   */
  #[Hook('options_list_alter')]
  public function optionsListAlter(array &$options, array $context): void {
    if (!isset($context['fieldDefinition']) || !$context['fieldDefinition'] instanceof FieldDefinitionInterface) {
      return;
    }
    $target_type = $context['fieldDefinition']->getFieldStorageDefinition()->getSetting('target_type');
    if (empty($context['widget']) || !$context['widget'] instanceof WidgetInterface) {
      return;
    }
    $widget_id = $context['widget']->getPluginId();
    $provider = $context['widget']->getPluginDefinition()['provider'];

    if ($provider === 'tagify' && $target_type === 'taxonomy_term' && $widget_id !== 'tagify_select_widget') {
      $this->processOptions($options);
    }
  }

  /**
   * Implements hook_field_info_alter().
   *
   * Change the default widget for fields of type 'entity_reference'.
   */
  #[Hook('field_info_alter')]
  public function fieldInfoAlter(array &$info): void {
    $suggested = $this->configFactory->get('tagify.settings')->get('set_default_widget');
    if (isset($info['entity_reference']) && $suggested) {
      $info['entity_reference']['default_widget'] = 'tagify_entity_reference_autocomplete_widget';
    }
  }

  /**
   * Strips dashes prefixed to taxonomy labels recursively.
   *
   * @param array $options
   *   The options list to process, passed by reference.
   */
  private function processOptions(array &$options): void {
    foreach ($options as $key => $value) {
      if (is_array($value)) {
        $this->processOptions($options[$key]);
      }
      else {
        $options[$key] = ltrim($value, '-');
      }
    }
  }

}
