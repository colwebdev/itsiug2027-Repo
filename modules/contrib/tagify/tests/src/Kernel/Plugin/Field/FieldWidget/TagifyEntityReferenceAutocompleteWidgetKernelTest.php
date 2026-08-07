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
use Drupal\tagify\Plugin\Field\FieldWidget\TagifyEntityReferenceAutocompleteWidget;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Kernel tests for the Tagify entity reference autocomplete widget.
 *
 * Covers the PHP-only paths (settings, element build, value massaging) the
 * browser tests cannot reach cheaply, so the libraries/JS refactor is safe to
 * land.
 *
 * @group tagify
 */
#[CoversClass(TagifyEntityReferenceAutocompleteWidget::class)]
#[RunTestsInSeparateProcesses]
class TagifyEntityReferenceAutocompleteWidgetKernelTest extends TagifyKernelTestBase {

  use TagifyTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    Vocabulary::create(['vid' => 'tags', 'name' => 'Tags'])->save();
    NodeType::create(['type' => 'article', 'name' => 'Article'])->save();

    // An entity_reference field with auto-create on, targeting the vocabulary.
    $this->createField('field_tags', 'node', 'article', 'entity_reference', [
      'target_type' => 'taxonomy_term',
    ], [
      'handler' => 'default:taxonomy_term',
      'handler_settings' => [
        'target_bundles' => ['tags' => 'tags'],
        'auto_create' => TRUE,
      ],
    ], 'tagify_entity_reference_autocomplete_widget', []);
  }

  /**
   * Builds the widget plugin for the field under test.
   *
   * @param array $settings
   *   Widget settings overrides.
   *
   * @return \Drupal\Core\Field\WidgetInterface
   *   The widget instance.
   */
  protected function getWidget(array $settings = []): WidgetInterface {
    $field_definition = Node::create(['type' => 'article'])
      ->get('field_tags')
      ->getFieldDefinition();

    return $this->container->get('plugin.manager.field.widget')->getInstance([
      'field_definition' => $field_definition,
      'form_mode' => 'default',
      'configuration' => [
        'type' => 'tagify_entity_reference_autocomplete_widget',
        'settings' => $settings,
        'third_party_settings' => [],
      ],
    ]);
  }

  /**
   * Returns the field item list for a fresh article node.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface
   *   The field item list.
   */
  protected function getItems(): FieldItemListInterface {
    return Node::create(['type' => 'article', 'title' => 'N'])->get('field_tags');
  }

  /**
   * The defaults match the documented widget configuration.
   */
  public function testDefaultSettings(): void {
    $defaults = TagifyEntityReferenceAutocompleteWidget::defaultSettings();

    $this->assertSame('CONTAINS', $defaults['match_operator']);
    $this->assertSame(10, $defaults['match_limit']);
    $this->assertSame(1, $defaults['suggestions_dropdown']);
    $this->assertSame('', $defaults['placeholder']);
    $this->assertSame(0, $defaults['show_entity_id']);
    $this->assertSame(0, $defaults['show_info_label']);
    $this->assertSame(1, $defaults['parent_selection']);
  }

  /**
   * The settings summary reflects the configured operator and flags.
   */
  public function testSettingsSummary(): void {
    $widget = $this->getWidget([
      'match_operator' => 'STARTS_WITH',
      'match_limit' => 0,
      'placeholder' => 'Type a tag',
      'show_entity_id' => 1,
      'parent_selection' => 0,
    ]);

    $summary = array_map('strval', $widget->settingsSummary());
    $joined = implode("\n", $summary);

    $this->assertStringContainsString('Starts with', $joined);
    $this->assertStringContainsString('unlimited', $joined);
    $this->assertStringContainsString('Type a tag', $joined);
    $this->assertStringContainsString('Include the entity ID within the tag', $joined);
    $this->assertStringContainsString('Parent selection not allowed', $joined);
  }

  /**
   * The form element is the tagify autocomplete element with the right wiring.
   */
  public function testFormElement(): void {
    $widget = $this->getWidget([
      'match_operator' => 'CONTAINS',
      'match_limit' => 10,
      'suggestions_dropdown' => 1,
      'parent_selection' => 1,
    ]);
    $items = $this->getItems();
    $element = [];
    $form = [];
    $element = $widget->formElement($items, 0, $element, $form, new FormState());

    $this->assertSame('entity_autocomplete_tagify', $element['#type']);
    $this->assertSame('taxonomy_term', $element['#target_type']);
    $this->assertSame('default:taxonomy_term', $element['#selection_handler']);
    $this->assertSame(1, $element['#cardinality']);
    $this->assertSame('CONTAINS', $element['#selection_settings']['match_operator']);
    // Taxonomy targets carry the parent-selection flag.
    $this->assertArrayHasKey('parent_selection', $element['#selection_settings']);
    // Single cardinality is "limited"; auto-create is on.
    $this->assertContains('field_tags', $element['#attributes']['class']);
    $this->assertContains('tagify--limited', $element['#attributes']['class']);
    $this->assertContains('tagify--autocreate', $element['#attributes']['class']);
    $this->assertSame('field_tags', $element['#identifier']);
  }

  /**
   * Non-string and non-JSON submissions massage to an empty value.
   */
  public function testMassageFormValuesRejectsBadInput(): void {
    $widget = $this->getWidget();
    $form = [];

    $this->assertSame([], $widget->massageFormValues(['array'], $form, new FormState()));
    $this->assertSame([], $widget->massageFormValues('not-json', $form, new FormState()));
  }

  /**
   * An explicit entity_id passes straight through as a target_id.
   */
  public function testMassageFormValuesEntityIdPassthrough(): void {
    $widget = $this->getWidget();
    $form = [];
    $json = json_encode([['value' => 'Whatever', 'entity_id' => 7]]);

    $result = $widget->massageFormValues($json, $form, new FormState());

    $this->assertSame([['target_id' => 7]], $result);
  }

  /**
   * A label matching an existing term resolves to that term's ID.
   */
  public function testMassageFormValuesResolvesExistingLabel(): void {
    $term = Term::create(['name' => 'Existing', 'vid' => 'tags']);
    $term->save();

    $widget = $this->getWidget();
    $form = [];
    $json = json_encode([['value' => 'Existing']]);

    $result = $widget->massageFormValues($json, $form, new FormState());

    $this->assertSame([['target_id' => $term->id()]], $result);
  }

  /**
   * An unknown label auto-creates a new (unsaved) entity.
   */
  public function testMassageFormValuesAutocreatesUnknownLabel(): void {
    $widget = $this->getWidget();
    $form = [];
    $json = json_encode([['value' => 'Brand new term']]);

    $result = $widget->massageFormValues($json, $form, new FormState());

    $this->assertCount(1, $result);
    $this->assertArrayHasKey('entity', $result[0]);
    $entity = $result[0]['entity'];
    $this->assertInstanceOf(Term::class, $entity);
    $this->assertTrue($entity->isNew());
    $this->assertSame('Brand new term', $entity->label());
  }

}
