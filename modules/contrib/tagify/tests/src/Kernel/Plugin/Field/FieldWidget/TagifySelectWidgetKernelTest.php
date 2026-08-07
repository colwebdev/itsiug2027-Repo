<?php

declare(strict_types=1);

namespace Drupal\Tests\tagify\Kernel\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormState;
use Drupal\Tests\tagify\Kernel\TagifyKernelTestBase;
use Drupal\Tests\tagify\Traits\TagifyTestTrait;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\tagify\Plugin\Field\FieldWidget\TagifySelectWidget;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Kernel tests for the Tagify select widget.
 *
 * Covers the settings and form-element build for both an options (list_string)
 * field and an entity_reference field, so the libraries/JS refactor is safe to
 * land.
 *
 * @group tagify
 */
#[CoversClass(TagifySelectWidget::class)]
#[RunTestsInSeparateProcesses]
class TagifySelectWidgetKernelTest extends TagifyKernelTestBase {

  use TagifyTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'field',
    'user',
    'system',
    'taxonomy',
    'text',
    'filter',
    'options',
    'tagify',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    Vocabulary::create(['vid' => 'tags', 'name' => 'Tags'])->save();
    NodeType::create(['type' => 'article', 'name' => 'Article'])->save();

    // A single-cardinality list_string field (the simplest options field).
    $this->createField('field_choice', 'node', 'article', 'list_string', [
      'allowed_values' => ['a' => 'A', 'b' => 'B'],
    ], [], 'tagify_select_widget', []);

    // A multi-cardinality entity_reference field for the target_type branch.
    $this->createField('field_terms', 'node', 'article', 'entity_reference', [
      'target_type' => 'taxonomy_term',
      'cardinality' => -1,
    ], [
      'handler' => 'default:taxonomy_term',
      'handler_settings' => ['target_bundles' => ['tags' => 'tags']],
    ], 'tagify_select_widget', []);
  }

  /**
   * Builds the select widget plugin for a given field.
   *
   * @param string $field_name
   *   The field machine name.
   * @param array $settings
   *   Widget settings overrides.
   *
   * @return \Drupal\Core\Field\WidgetInterface
   *   The widget instance.
   */
  protected function getWidget(string $field_name, array $settings = []): WidgetInterface {
    $field_definition = Node::create(['type' => 'article'])
      ->get($field_name)
      ->getFieldDefinition();

    return $this->container->get('plugin.manager.field.widget')->getInstance([
      'field_definition' => $field_definition,
      'form_mode' => 'default',
      'configuration' => [
        'type' => 'tagify_select_widget',
        'settings' => $settings,
        'third_party_settings' => [],
      ],
    ]);
  }

  /**
   * Returns the field item list for a fresh article node.
   *
   * @param string $field_name
   *   The field machine name.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface
   *   The field item list.
   */
  protected function getItems(string $field_name): FieldItemListInterface {
    return Node::create(['type' => 'article', 'title' => 'N'])->get($field_name);
  }

  /**
   * The defaults match the documented widget configuration.
   */
  public function testDefaultSettings(): void {
    $defaults = TagifySelectWidget::defaultSettings();

    $this->assertSame('CONTAINS', $defaults['match_operator']);
    $this->assertSame(0, $defaults['match_limit']);
    $this->assertSame('', $defaults['placeholder']);
    $this->assertSame(0, $defaults['show_entity_id']);
    $this->assertSame(1, $defaults['parent_selection']);
  }

  /**
   * The settings summary reflects the configured operator and flags.
   */
  public function testSettingsSummary(): void {
    $widget = $this->getWidget('field_choice', [
      'match_operator' => 'CONTAINS',
      'match_limit' => 0,
      'placeholder' => '',
      'show_entity_id' => 0,
      'parent_selection' => 1,
    ]);

    $joined = implode("\n", array_map('strval', $widget->settingsSummary()));

    $this->assertStringContainsString('Contains', $joined);
    $this->assertStringContainsString('unlimited', $joined);
    $this->assertStringContainsString('Remove the entity ID from the tag', $joined);
    $this->assertStringContainsString('Parent selection allowed', $joined);
    $this->assertStringContainsString('No placeholder', $joined);
  }

  /**
   * An options field builds a select_tagify element with its option list.
   */
  public function testFormElementForOptionsField(): void {
    $widget = $this->getWidget('field_choice', [
      'match_operator' => 'CONTAINS',
      'match_limit' => 0,
      'parent_selection' => 1,
    ]);
    // Seed the keys core's form builder normally sets before formElement().
    $element = ['#required' => FALSE, '#field_parents' => []];
    $form = [];
    $element = $widget->formElement($this->getItems('field_choice'), 0, $element, $form, new FormState());

    $this->assertSame('select_tagify', $element['#type']);
    $this->assertSame('field_choice', $element['#identifier']);
    $this->assertSame('CONTAINS', $element['#match_operator']);
    $this->assertSame(0, $element['#match_limit']);
    // Single cardinality => "select" mode and not multiple.
    $this->assertSame('select', $element['#mode']);
    $this->assertFalse($element['#multiple']);
    // The configured allowed values are present as options.
    $this->assertArrayHasKey('a', $element['#options']);
    $this->assertArrayHasKey('b', $element['#options']);
    // A single select gets the reserved empty option.
    $this->assertArrayHasKey('_none', $element['#options']);
  }

  /**
   * An entity_reference field adds the drag-to-reorder description message.
   */
  public function testFormElementForEntityReferenceField(): void {
    Term::create(['name' => 'One', 'vid' => 'tags'])->save();
    Term::create(['name' => 'Two', 'vid' => 'tags'])->save();

    $widget = $this->getWidget('field_terms', [
      'match_operator' => 'CONTAINS',
      'parent_selection' => 1,
    ]);
    // Seed the keys core's form builder normally sets before formElement().
    $element = ['#required' => FALSE, '#field_parents' => []];
    $form = [];
    $element = $widget->formElement($this->getItems('field_terms'), 0, $element, $form, new FormState());

    $this->assertSame('select_tagify', $element['#type']);
    $this->assertSame(-1, $element['#cardinality']);
    // Unlimited cardinality => non-"select" mode (multi-tag UI).
    $this->assertSame('', $element['#mode']);
    $this->assertTrue((bool) $element['#parent_selection']);
    $this->assertArrayHasKey('parent_selection', $element['#selection_settings']);
    // The drag-to-reorder message is attached as the description.
    $this->assertArrayHasKey('#description', $element);
  }

}
