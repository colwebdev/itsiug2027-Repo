<?php

declare(strict_types=1);

namespace Drupal\Tests\tagify\Kernel\Element;

use Drupal\Core\Form\FormState;
use Drupal\Tests\tagify\Kernel\TagifyKernelTestBase;
use Drupal\tagify\Element\SelectTagify;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the server-side logic of the select_tagify element.
 *
 * Locks the data-* attribute contract and the selection-order option sorting
 * that the libraries/JS refactor depends on.
 *
 * @group tagify
 */
#[CoversClass(SelectTagify::class)]
#[RunTestsInSeparateProcesses]
class SelectTagifyTest extends TagifyKernelTestBase {

  /**
   * The element exposes the Tagify defaults consumed downstream.
   */
  public function testGetInfoDefaults(): void {
    $info = $this->container->get('plugin.manager.element_info')
      ->getInfo('select_tagify');

    $this->assertSame('select', $info['#mode']);
    $this->assertSame(20, $info['#match_limit']);
    $this->assertSame('CONTAINS', $info['#match_operator']);
    $this->assertSame(0, $info['#cardinality']);
    $this->assertSame(1, $info['#parent_selection']);
    $this->assertFalse($info['#multiple']);
    $this->assertSame(
      [SelectTagify::class, 'processSelectTagify'],
      $info['#process'][0],
    );
  }

  /**
   * Processing builds the data-* contract and attaches the library.
   */
  public function testProcessBuildsAttributes(): void {
    $element = [
      '#mode' => 'select',
      '#identifier' => 'field_choice',
      '#cardinality' => 3,
      '#match_operator' => 'CONTAINS',
      '#match_limit' => 5,
      '#placeholder' => 'Choose',
      '#show_entity_id' => 0,
      '#parent_selection' => 1,
      '#multiple' => FALSE,
      '#options' => ['a' => 'A', 'b' => 'B'],
      '#value' => [],
      '#attributes' => [],
      '#attached' => [],
    ];
    $form = [];
    SelectTagify::processSelectTagify($element, new FormState(), $form);

    $this->assertContains('tagify/default', $element['#attached']['library']);
    $this->assertSame('select', $element['#attributes']['data-mode']);
    $this->assertSame('field_choice', $element['#attributes']['data-identifier']);
    $this->assertSame(3, $element['#attributes']['data-cardinality']);
    $this->assertSame(1, $element['#attributes']['data-match-operator']);
    $this->assertSame(5, $element['#attributes']['data-match-limit']);
    $this->assertSame('Choose', $element['#attributes']['data-placeholder']);
    $this->assertSame(1, $element['#attributes']['data-parent-selection']);

    $messages = $element['#attached']['drupalSettings']['tagify_select']['information_message'];
    $this->assertArrayHasKey('limit_tag', $messages);
    $this->assertArrayHasKey('no_matching_suggestions', $messages);
  }

  /**
   * A multiple select re-orders its options by selection order.
   */
  public function testProcessSortsOptionsBySelectionOrder(): void {
    $element = [
      '#mode' => 'select',
      '#identifier' => 'field_choice',
      '#cardinality' => -1,
      '#match_operator' => 'CONTAINS',
      '#match_limit' => 0,
      '#placeholder' => '',
      '#show_entity_id' => 0,
      '#parent_selection' => 1,
      '#multiple' => TRUE,
      '#options' => ['a' => 'A', 'b' => 'B', 'c' => 'C'],
      // 'c' then 'a' were selected, in that order.
      '#value' => ['c', 'a'],
      '#attributes' => [],
      '#attached' => [],
    ];
    $form = [];
    SelectTagify::processSelectTagify($element, new FormState(), $form);

    // The real contract: selected options keep their selection order relative
    // to each other ('c' was chosen before 'a'). The exact slot of the
    // unselected 'b' is an artifact of array_search() returning FALSE.
    $keys = array_keys($element['#options']);
    $this->assertContains('a', $keys);
    $this->assertContains('c', $keys);
    $this->assertLessThan(
      array_search('a', $keys, TRUE),
      array_search('c', $keys, TRUE),
      "The earlier-selected option 'c' must sort before 'a'.",
    );
  }

}
