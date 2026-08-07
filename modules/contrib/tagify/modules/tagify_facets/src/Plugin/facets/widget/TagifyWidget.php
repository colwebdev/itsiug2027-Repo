<?php

declare(strict_types=1);

namespace Drupal\tagify_facets\Plugin\facets\widget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Widget\WidgetPluginBase;
use Drupal\tagify\Enum\MatchOperator;

/**
 * The Tagify widget.
 *
 * @todo Convert this annotation to a #[FacetsWidget] attribute once Facets
 *   ships one. As of Facets 3.0.x only FacetsUrlProcessor has been converted to
 *   an attribute; the widget plugin type is still annotation-only (the manager
 *   in Drupal\facets\Widget\WidgetPluginManager references
 *   Drupal\facets\Annotation\FacetsWidget). Track the upstream conversion at
 *   https://www.drupal.org/project/facets and migrate when an attribute exists.
 *
 * @FacetsWidget(
 *   id = "tagify",
 *   label = @Translation("Tagify"),
 *   description = @Translation("A configurable widget that shows a tagify component."),
 * )
 */
class TagifyWidget extends WidgetPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'match_operator' => MatchOperator::Contains->value,
      'max_items' => 10,
      'placeholder' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet): array {
    $form = parent::buildConfigurationForm($form, $form_state, $facet);
    $form['match_operator'] = [
      '#type' => 'radios',
      '#title' => $this->t('Autocomplete matching'),
      '#default_value' => $this->getConfiguration()['match_operator'],
      '#options' => MatchOperator::options(),
      '#description' => $this->t('Select the method used to collect autocomplete suggestions. Note that <em>Contains</em> can cause performance issues on sites with thousands of entities.'),
      '#states' => [
        'visible' => [
          ':input[name$="widget_config[autocomplete]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['max_items'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of results'),
      '#default_value' => $this->getConfiguration()['max_items'],
      '#min' => 0,
      '#description' => $this->t('The number of suggestions that will be listed. Use <em>0</em> to remove the limit.'),
    ];
    $form['placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder'),
      '#default_value' => $this->getConfiguration()['placeholder'],
      '#description' => $this->t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet): array {
    $build = parent::build($facet);
    $this->appendWidgetLibrary($build);
    return $build;
  }

  /**
   * Appends widget library and relevant information for it to build array.
   *
   * @param array $build
   *   Reference to build array.
   */
  protected function appendWidgetLibrary(array &$build): void {
    $build['#attributes']['class'][] = 'js-facets-tagify';
    $build['#attributes']['class'][] = 'js-facets-widget';
    $build['#attributes']['class'][] = 'hidden';
    $build['#attached']['library'][] = 'tagify_facets/drupal.tagify_facets.tagify-widget';
    $build['#attached']['drupalSettings']['tagify']['tagify_facets_widget']['match_operator'] = $this->getConfiguration()['match_operator'];
    $build['#attached']['drupalSettings']['tagify']['tagify_facets_widget']['placeholder'] = $this->getConfiguration()['placeholder'];
    $build['#attached']['drupalSettings']['tagify']['tagify_facets_widget']['max_items'] = $this->getConfiguration()['max_items'];
  }

}
