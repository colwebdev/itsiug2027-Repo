<?php

declare(strict_types=1);

namespace Drupal\Tests\tagify\Kernel\Element;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Form\FormState;
use Drupal\Core\Site\Settings;
use Drupal\Tests\tagify\Kernel\TagifyKernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\tagify\Element\EntityAutocompleteTagify;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the server-side logic of the entity autocomplete tagify element.
 *
 * These assertions lock the PHP -> JS contract (the data-* attributes, the
 * default-value JSON payload and the hashed autocomplete URL) that the
 * libraries/JS refactor reshapes, so the refactor is safe to land.
 *
 * @group tagify
 */
#[CoversClass(EntityAutocompleteTagify::class)]
#[RunTestsInSeparateProcesses]
class EntityAutocompleteTagifyTest extends TagifyKernelTestBase {

  use UserCreationTrait;

  /**
   * The vocabulary used to build referenceable terms.
   *
   * @var \Drupal\taxonomy\Entity\Vocabulary
   */
  protected Vocabulary $vocabulary;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // An admin current user so 'view label' access passes; otherwise labels
    // collapse to '- Restricted access -'.
    $this->setUpCurrentUser([], [], TRUE);
    $this->vocabulary = Vocabulary::create([
      'vid' => 'tags',
      'name' => 'Tags',
    ]);
    $this->vocabulary->save();
  }

  /**
   * The element exposes the Tagify defaults consumed downstream.
   */
  public function testGetInfoDefaults(): void {
    $info = $this->container->get('plugin.manager.element_info')
      ->getInfo('entity_autocomplete_tagify');

    $this->assertSame(-1, $info['#cardinality']);
    $this->assertSame('default', $info['#selection_handler']);
    $this->assertSame(10, $info['#max_items']);
    $this->assertSame(1, $info['#suggestions_dropdown']);
    $this->assertSame('CONTAINS', $info['#match_operator']);
    $this->assertSame(1, $info['#parent_selection']);
    $this->assertSame(0, $info['#show_entity_id']);
    // The custom processor must run first so the data-* attributes exist
    // before core's textfield processing.
    $this->assertSame(
      [EntityAutocompleteTagify::class, 'processEntityAutocompleteTagify'],
      $info['#process'][0],
    );
  }

  /**
   * A missing #target_type is a developer error, not a silent no-op.
   */
  public function testProcessRequiresTargetType(): void {
    $element = ['#target_type' => ''];
    $form = [];
    $this->expectException(\InvalidArgumentException::class);
    EntityAutocompleteTagify::processEntityAutocompleteTagify(
      $element,
      new FormState(),
      $form,
    );
  }

  /**
   * Processing builds the data-* contract, library and hashed autocomplete URL.
   */
  public function testProcessBuildsAttributesAndStoresSelectionSettings(): void {
    $selection_settings = ['target_bundles' => ['tags' => 'tags']];
    $target_type = 'taxonomy_term';
    $selection_handler = 'default:taxonomy_term';

    $element = [
      '#target_type' => $target_type,
      '#selection_handler' => $selection_handler,
      '#selection_settings' => $selection_settings,
      '#autocreate' => TRUE,
      '#max_items' => 10,
      '#suggestions_dropdown' => 1,
      '#match_operator' => 'CONTAINS',
      '#placeholder' => 'Pick one',
      '#show_entity_id' => 0,
      '#identifier' => 'field_tags',
      '#cardinality' => -1,
      '#parent_selection' => 1,
      '#attributes' => [],
      '#attached' => [],
      '#autocomplete_query_parameters' => [],
    ];
    $form = [];
    EntityAutocompleteTagify::processEntityAutocompleteTagify(
      $element,
      new FormState(),
      $form,
    );

    // Library + marker classes.
    $this->assertContains('tagify/default', $element['#attached']['library']);
    $this->assertContains('tagify-widget', $element['#attributes']['class']);
    $this->assertContains('autocreate', $element['#attributes']['class']);

    // Data attributes that the JS reads.
    $this->assertSame(10, $element['#attributes']['data-max-items']);
    $this->assertSame(1, $element['#attributes']['data-suggestions-dropdown']);
    $this->assertSame(1, $element['#attributes']['data-match-operator']);
    $this->assertSame('Pick one', $element['#attributes']['data-placeholder']);
    $this->assertSame('field_tags', $element['#attributes']['data-identifier']);
    $this->assertSame(-1, $element['#attributes']['data-cardinality']);
    $this->assertSame(1, $element['#attributes']['data-parent-selection']);
    $this->assertContains('off', $element['#attributes']['autocomplete']);

    // The selection settings are stored under the recomputed HMAC key and the
    // autocomplete URL references that exact key.
    $data = serialize($selection_settings) . $target_type . $selection_handler;
    $key = Crypt::hmacBase64($data, Settings::getHashSalt());
    $store = $this->container->get('keyvalue')->get('entity_autocomplete');
    $this->assertTrue($store->has($key));
    $this->assertEquals($selection_settings, $store->get($key));
    $this->assertStringContainsString($key, $element['#attributes']['data-autocomplete-url']);

    // Translated UI strings handed to the JS.
    $messages = $element['#attached']['drupalSettings']['tagify']['information_message'];
    $this->assertArrayHasKey('limit_tag', $messages);
    $this->assertArrayHasKey('no_matching_suggestions', $messages);
  }

  /**
   * STARTS_WITH maps to the 0 flag the JS expects.
   */
  public function testProcessMapsStartsWithOperator(): void {
    $element = [
      '#target_type' => 'taxonomy_term',
      '#selection_handler' => 'default',
      '#selection_settings' => [],
      '#autocreate' => FALSE,
      '#max_items' => 0,
      '#suggestions_dropdown' => 0,
      '#match_operator' => 'STARTS_WITH',
      '#placeholder' => '',
      '#show_entity_id' => 0,
      '#identifier' => '',
      '#cardinality' => 1,
      '#parent_selection' => 0,
      '#attributes' => [],
      '#attached' => [],
      '#autocomplete_query_parameters' => [],
    ];
    $form = [];
    EntityAutocompleteTagify::processEntityAutocompleteTagify(
      $element,
      new FormState(),
      $form,
    );

    $this->assertSame(0, $element['#attributes']['data-match-operator']);
    // A 0 max_items must not emit a data-max-items cap.
    $this->assertArrayNotHasKey('data-max-items', $element['#attributes']);
    $this->assertNotContains('autocreate', $element['#attributes']['class'] ?? []);
  }

  /**
   * The value callback turns default entities into the Tagify JSON payload.
   */
  public function testValueCallbackFormatsDefaultEntities(): void {
    $term = Term::create(['name' => 'Alpha', 'vid' => 'tags']);
    $term->save();

    $element = [
      '#default_value' => [$term],
      '#info_label' => '',
      '#target_type' => 'taxonomy_term',
    ];
    $value = EntityAutocompleteTagify::valueCallback(
      $element,
      FALSE,
      new FormState(),
    );

    $decoded = json_decode($value, TRUE);
    $this->assertCount(1, $decoded);
    $this->assertSame((int) $term->id(), (int) $decoded[0]['entity_id']);
    $this->assertSame('Alpha', $decoded[0]['label']);
    $this->assertFalse($decoded[0]['editable']);
  }

  /**
   * URL-param input is sanitised: junk is dropped and the count is capped.
   */
  public function testValueCallbackSanitisesAndCapsUrlParams(): void {
    $terms = [];
    foreach (['one', 'two', 'three'] as $name) {
      $term = Term::create(['name' => $name, 'vid' => 'tags']);
      $term->save();
      $terms[$name] = (string) $term->id();
    }

    // Cardinality 2 caps the load to the first two valid IDs; non-numeric and
    // non-positive values are discarded outright. ('abc' -> intval 0 ->
    // dropped; a value like '42abc' would survive as 42 because intval runs
    // first.)
    $element = [
      '#target_type' => 'taxonomy_term',
      '#info_label' => '',
      '#cardinality' => 2,
    ];
    $input = [$terms['one'], 'abc', '0', '-5', $terms['two'], $terms['three']];
    $value = EntityAutocompleteTagify::valueCallback(
      $element,
      $input,
      new FormState(),
    );

    $decoded = json_decode($value, TRUE);
    $this->assertCount(2, $decoded);
    $ids = array_map(static fn(array $item): string => (string) $item['entity_id'], $decoded);
    $this->assertSame([$terms['one'], $terms['two']], $ids);
  }

  /**
   * An empty array of URL params yields no value.
   */
  public function testValueCallbackReturnsNullForEmptyInput(): void {
    $element = ['#target_type' => 'taxonomy_term', '#info_label' => ''];
    $value = EntityAutocompleteTagify::valueCallback(
      $element,
      [],
      new FormState(),
    );
    $this->assertNull($value);
  }

  /**
   * A new (unsaved) entity stores its label as the value, not an ID.
   */
  public function testGetTagifyDefaultValueUsesLabelForNewEntity(): void {
    $term = Term::create(['name' => 'Fresh', 'vid' => 'tags']);

    $value = EntityAutocompleteTagify::getTagifyDefaultValue([$term], '');
    $decoded = json_decode($value, TRUE);

    $this->assertSame('Fresh', $decoded[0]['value']);
    $this->assertSame('Fresh', $decoded[0]['label']);
  }

  /**
   * Hierarchical terms carry their immediate parent name.
   */
  public function testGetTagifyDefaultValueAddsParentName(): void {
    $parent = Term::create(['name' => 'Parent', 'vid' => 'tags']);
    $parent->save();
    $child = Term::create([
      'name' => 'Child',
      'vid' => 'tags',
      'parent' => [$parent->id()],
    ]);
    $child->save();

    $value = EntityAutocompleteTagify::getTagifyDefaultValue([$child], '');
    $decoded = json_decode($value, TRUE);

    $this->assertArrayHasKey('parent_name', $decoded[0]);
    $this->assertSame('Parent', $decoded[0]['parent_name']);
  }

  /**
   * Info-label tokens are replaced against the referenced entity.
   */
  public function testGetTagifyDefaultValueReplacesInfoLabelToken(): void {
    NodeType::create(['type' => 'article', 'name' => 'Article'])->save();
    $node = Node::create(['type' => 'article', 'title' => 'Hello world']);
    $node->save();

    $value = EntityAutocompleteTagify::getTagifyDefaultValue([$node], '[node:title]');
    $decoded = json_decode($value, TRUE);

    $this->assertSame('Hello world', $decoded[0]['info_label']);
  }

}
