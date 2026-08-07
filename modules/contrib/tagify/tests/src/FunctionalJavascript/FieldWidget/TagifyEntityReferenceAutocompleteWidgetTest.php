<?php

declare(strict_types=1);

namespace Drupal\Tests\tagify\FunctionalJavascript\FieldWidget;

use Drupal\Tests\TestFileCreationTrait;
use Drupal\Tests\tagify\FunctionalJavascript\TagifyJavascriptTestBase;
use Drupal\entity_test\Entity\EntityTestMulRevPub;
use Drupal\node\Entity\Node;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests tagify entity reference widget.
 *
 * @group tagify
 */
#[RunTestsInSeparateProcesses]
class TagifyEntityReferenceAutocompleteWidgetTest extends TagifyJavascriptTestBase {

  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'tagify',
    // Prevent tests from failing due to 'RuntimeException' with AJAX request.
    'js_testing_ajax_request_test',
  ];

  /**
   * Test a single value widget.
   */
  #[DataProvider('providerTestSingleValueWidget')]
  public function testSingleValueWidget($match_operator, $autocreate) {
    // Create a new entity reference field with tagify widget.
    $this->createField('tagify', 'node', 'test', 'entity_reference', [
      'target_type' => 'entity_test_mulrevpub',
    ], [
      'handler' => 'default:entity_test_mulrevpub',
      'handler_settings' => [
        'auto_create' => $autocreate,
      ],
    ], 'tagify_entity_reference_autocomplete_widget', [
      'match_operator' => $match_operator,
      'match_limit' => 10,
      'suggestions_dropdown' => 1,
      'show_entity_id' => 0,
    ]);

    // Add references to the new field.
    EntityTestMulRevPub::create(['name' => 'foo'])->save();
    EntityTestMulRevPub::create(['name' => 'bar'])->save();
    EntityTestMulRevPub::create(['name' => 'baz'])->save();

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('/node/add/test');
    $page->fillField('title[0][value]', 'Test node');
    $this->click('.tagify__input');

    // Write value to get suggestion.
    $page->find('css', '.tagify__input')->setValue('foo');
    $assert_session->waitForElement('css', '.tagify__dropdown__item');
    $assert_session->waitForElementVisible('css', '.tagify__dropdown__item--active');

    $page->find('css', '.tagify__dropdown__item--active')->click();
    $page->pressButton('Save');

    $node = $this->getNodeByTitle('Test node', TRUE);
    if (!$node) {
      return;
    }

    // Get Tag id from entity reference field.
    $tag_id = $node->get('tagify')->getString();
    // Check if the node is an object and there is Tag ID.
    if (is_object($node) && $tag_id) {
      $this->assertSame("1", $tag_id);
    }
  }

  /**
   * Data provider for testSingleValueWidget().
   *
   * @return array
   *   The data.
   */
  public static function providerTestSingleValueWidget() {
    return [
      ['CONTAINS', TRUE],
      ['STARTS_WITH', TRUE],
    ];
  }

  /**
   * Test multiple value widget.
   */
  #[DataProvider('providerTestMultipleValueWidget')]
  public function testMultipleValueWidget($match_operator, $autocreate, $cardinality) {
    // Create a new entity reference field with tagify widget.
    $this->createField('tagify', 'node', 'test', 'entity_reference', [
      'target_type' => 'entity_test_mulrevpub',
      'cardinality' => $cardinality,
    ], [
      'handler' => 'default:entity_test_mulrevpub',
      'handler_settings' => [
        'auto_create' => $autocreate,
      ],
    ], 'tagify_entity_reference_autocomplete_widget', [
      'match_operator' => $match_operator,
      'match_limit' => 10,
      'suggestions_dropdown' => 0,
      'show_entity_id' => 0,
    ]);

    // Add references to the new field.
    EntityTestMulRevPub::create(['name' => 'foo'])->save();
    EntityTestMulRevPub::create(['name' => 'bar'])->save();
    EntityTestMulRevPub::create(['name' => 'baz'])->save();
    EntityTestMulRevPub::create(['name' => 'waldo'])->save();
    EntityTestMulRevPub::create(['name' => 'fred'])->save();

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('/node/add/test');
    $page->fillField('title[0][value]', 'Test node');

    // Add first value.
    $this->click('.tagify__input');
    $page->find('css', '.tagify__input')->setValue('foo');
    $this->getSession()->wait(500);
    $assert_session->waitForElement('css', '.tagify__dropdown__item');
    $assert_session->waitForElementVisible('css', '.tagify__dropdown__item--active');
    $page->find('css', '.tagify__dropdown__item--active')->click();

    // Add second value.
    $this->click('.tagify__input');
    $page->find('css', '.tagify__input')->setValue('bar');
    $this->getSession()->wait(500);
    $assert_session->waitForElement('css', '.tagify__dropdown__item');
    $assert_session->waitForElementVisible('css', '.tagify__dropdown__item--active');
    $page->find('css', '.tagify__dropdown__item--active')->click();

    // Add third value.
    $this->click('.tagify__input');
    $page->find('css', '.tagify__input')->setValue('baz');
    $this->getSession()->wait(500);
    $assert_session->waitForElement('css', '.tagify__dropdown__item');
    $assert_session->waitForElementVisible('css', '.tagify__dropdown__item--active');
    $page->find('css', '.tagify__dropdown__item--active')->click();

    // Add fourth value.
    $this->click('.tagify__input');
    $page->find('css', '.tagify__input')->setValue('waldo');
    $this->getSession()->wait(500);
    $assert_session->waitForElement('css', '.tagify__dropdown__item');
    $assert_session->waitForElementVisible('css', '.tagify__dropdown__item--active');
    $page->find('css', '.tagify__dropdown__item--active')->click();
    $assert_session->waitForElementVisible('css', 'tagify__tag');

    $page->pressButton('Save');

    $node = $this->getNodeByTitle('Test node', TRUE);
    if (!$node) {
      return;
    }
    $this->assertEquals([
      ['target_id' => 1],
      ['target_id' => 2],
      ['target_id' => 3],
      ['target_id' => 4],
    ], $node->get('tagify')->getValue());
  }

  /**
   * Data provider for testMultipleValueWidget().
   *
   * @return array
   *   The data.
   */
  public static function providerTestMultipleValueWidget() {
    return [
      ['CONTAINS', TRUE, -1],
      ['CONTAINS', FALSE, -1],
    ];
  }

  /**
   * Test limited cardinality information.
   */
  #[DataProvider('providerTestLimitedCardinality')]
  public function testLimitedCardinality($match_operator, $autocreate, $cardinality) {
    // Create a new entity reference field with tagify widget.
    $this->createField('tagify', 'node', 'test', 'entity_reference', [
      'target_type' => 'entity_test_mulrevpub',
      'cardinality' => $cardinality,
    ], [
      'handler' => 'default:entity_test_mulrevpub',
      'handler_settings' => [
        'auto_create' => $autocreate,
      ],
    ], 'tagify_entity_reference_autocomplete_widget', [
      'match_operator' => $match_operator,
      'match_limit' => 10,
      'suggestions_dropdown' => 0,
      'show_entity_id' => 0,
    ]);

    // Add references to the new field.
    EntityTestMulRevPub::create(['name' => 'foo'])->save();
    EntityTestMulRevPub::create(['name' => 'bar'])->save();

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('/node/add/test');
    $page->fillField('title[0][value]', 'Test node');

    // Add first value.
    $this->click('.tagify__input');
    $page->find('css', '.tagify__input')->setValue('foo');
    $assert_session->waitForElement('css', '.tagify__dropdown__item');
    $assert_session->waitForElementVisible('css', '.tagify__dropdown__item--active');
    $page->find('css', '.tagify__dropdown__item--active')->click();
    $this->getSession()->wait(500);
    $assert_session->waitForElement('css', '.tagify__tag');

    // Add second value.
    $this->click('.tagify__input');
    $page->find('css', '.tagify__input')->setValue('bar');
    $assert_session->waitForElement('css', '.tagify__dropdown__item');
    $assert_session->waitForElementVisible('css', '.tagify__dropdown__footer');
    // Assert that the footer element contains the correct text.
    $this->assertSession()->elementTextContains('css', '.tagify__dropdown__footer', 'Tags are limited to: 1');
  }

  /**
   * Data provider for testLimitedCardinality().
   *
   * @return array
   *   The data.
   */
  public static function providerTestLimitedCardinality() {
    return [
      ['CONTAINS', TRUE, 1],
      ['CONTAINS', FALSE, 1],
    ];
  }

  /**
   * Test non matching tag information.
   */
  #[DataProvider('providerTestNonMatchingTag')]
  public function testNonMatchingTag($match_operator, $autocreate) {
    // Create a new entity reference field with tagify widget.
    $this->createField('tagify', 'node', 'test', 'entity_reference', [
      'target_type' => 'entity_test_mulrevpub',
      'cardinality' => -1,
    ], [
      'handler' => 'default:entity_test_mulrevpub',
      'handler_settings' => [
        'auto_create' => $autocreate,
      ],
    ], 'tagify_entity_reference_autocomplete_widget', [
      'match_operator' => $match_operator,
      'match_limit' => 10,
      'suggestions_dropdown' => 0,
      'show_entity_id' => 0,
    ]);

    // Add references to the new field.
    EntityTestMulRevPub::create(['name' => 'foo'])->save();
    EntityTestMulRevPub::create(['name' => 'bar'])->save();

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('/node/add/test');
    $page->fillField('title[0][value]', 'Test node');

    // Add value non existing value.
    $this->click('.tagify__input');
    $page->find('css', '.tagify__input')->setValue('baz');
    $assert_session->waitForElement('css', '.tagify__dropdown__item');
    $assert_session->waitForElementVisible('css', '.tagify--dropdown-item-no-match');
    // Assert that the footer element contains the correct text.
    $this->assertSession()->elementTextContains('css', '.tagify--dropdown-item-no-match', 'No matching suggestions found for: baz');
  }

  /**
   * Data provider for testNonMatchingTag().
   *
   * @return array
   *   The data.
   */
  public static function providerTestNonMatchingTag() {
    return [
      ['CONTAINS', FALSE],
    ];
  }

  /**
   * Existing references hydrate as pre-populated tags on the edit form.
   *
   * Exercises the PHP -> JS default-value path
   * (EntityAutocompleteTagify::getTagifyDefaultValue) end to end: the refactor
   * reshaped that payload, so a regression would drop pre-existing tags.
   */
  public function testDefaultValueRendersExistingTags(): void {
    $this->createField('tagify', 'node', 'test', 'entity_reference', [
      'target_type' => 'entity_test_mulrevpub',
      'cardinality' => -1,
    ], [
      'handler' => 'default:entity_test_mulrevpub',
      'handler_settings' => ['auto_create' => FALSE],
    ], 'tagify_entity_reference_autocomplete_widget', [
      'match_operator' => 'CONTAINS',
      'match_limit' => 10,
      'suggestions_dropdown' => 0,
      'show_entity_id' => 0,
    ]);

    $foo = EntityTestMulRevPub::create(['name' => 'foo']);
    $foo->save();
    $bar = EntityTestMulRevPub::create(['name' => 'bar']);
    $bar->save();

    // A node that already references both entities.
    $node = Node::create([
      'type' => 'test',
      'title' => 'Prefilled node',
      'tagify' => [
        ['target_id' => $foo->id()],
        ['target_id' => $bar->id()],
      ],
    ]);
    $node->save();

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $this->drupalGet('/node/' . $node->id() . '/edit');

    // Both referenced entities render as Tagify tags before any interaction.
    $assert_session->waitForElement('css', '.tagify__tag');
    $this->assertCount(2, $page->findAll('css', '.tagify__tag'), 'Both referenced entities render as tags.');

    // Saving without changes round-trips the hydrated value unchanged. This is
    // the strongest hydration signal: the tags map back to the right entities.
    $page->pressButton('Save');
    $node = $this->getNodeByTitle('Prefilled node', TRUE);
    if (!$node) {
      return;
    }
    $this->assertEquals(
      [['target_id' => $foo->id()], ['target_id' => $bar->id()]],
      $node->get('tagify')->getValue(),
    );
  }

  /**
   * Removing a tag syncs to the input and clears the reference on save.
   *
   * Protects the JS refactor's tag-removal -> input-sync path: after the
   * remove button is clicked the underlying input must hold only the remaining
   * reference (the JSON Drupal's massageFormValues() reads), and saving must
   * persist exactly that.
   */
  public function testRemoveTagClearsReference(): void {
    $this->createField('tagify', 'node', 'test', 'entity_reference', [
      'target_type' => 'entity_test_mulrevpub',
      'cardinality' => -1,
    ], [
      'handler' => 'default:entity_test_mulrevpub',
      'handler_settings' => ['auto_create' => FALSE],
    ], 'tagify_entity_reference_autocomplete_widget', [
      'match_operator' => 'CONTAINS',
      'match_limit' => 10,
      'suggestions_dropdown' => 0,
      'show_entity_id' => 0,
    ]);

    $foo = EntityTestMulRevPub::create(['name' => 'foo']);
    $foo->save();
    $bar = EntityTestMulRevPub::create(['name' => 'bar']);
    $bar->save();

    $node = Node::create([
      'type' => 'test',
      'title' => 'Removable node',
      'tagify' => [
        ['target_id' => $foo->id()],
        ['target_id' => $bar->id()],
      ],
    ]);
    $node->save();
    $nid = $node->id();

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $this->drupalGet('/node/' . $nid . '/edit');

    $assert_session->waitForElement('css', '.tagify__tag');
    $this->assertCount(2, $page->findAll('css', '.tagify__tag'));

    // Remove the first tag (foo) and wait until Tagify settles to one tag.
    $page->find('css', '.tagify__tag .tagify__tag__removeBtn')->click();
    $this->assertJsCondition('document.querySelectorAll(".tagify__tag").length === 1');

    // The underlying input (the value Drupal submits) now holds only the
    // remaining reference: bar's entity_id, never foo's.
    $value = $this->getSession()->evaluateScript(
      "document.querySelector('textarea.tagify-widget, input.tagify-widget').value",
    );
    $decoded = json_decode($value, TRUE);
    $this->assertIsArray($decoded);
    $this->assertCount(1, $decoded);
    $this->assertSame((string) $bar->id(), (string) $decoded[0]['entity_id']);

    // Save and wait for the POST to land (the edit form navigates away) before
    // reading the entity, otherwise the DB read can race the submit.
    $page->pressButton('Save');
    $this->assertJsCondition("!document.querySelector('input.tagify-widget')");

    $storage = \Drupal::entityTypeManager()->getStorage('node');
    $storage->resetCache([$nid]);
    $saved = $storage->load($nid);
    $this->assertEquals(
      [['target_id' => $bar->id()]],
      $saved->get('tagify')->getValue(),
    );
  }

}
