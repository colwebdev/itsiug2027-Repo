<?php

declare(strict_types=1);

namespace Drupal\Tests\tagify_facets\FunctionalJavascript\FacetsWidget;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\entity_test\Entity\EntityTestMulRevPub;
use Drupal\facets\Entity\Facet;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests tagify facets widget.
 *
 * @group tagify
 */
#[RunTestsInSeparateProcesses]
class TagifyWidgetTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'olivero';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'tagify_facets_test',
    // Prevent tests from failing due to 'RuntimeException' with AJAX request.
    'js_testing_ajax_request_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create reference items.
    $tag_1 = EntityTestMulRevPub::create(['name' => 'Tag 1']);
    $tag_1->save();
    $tag_2 = EntityTestMulRevPub::create(['name' => 'Tag 2']);
    $tag_2->save();
    $tag_3 = EntityTestMulRevPub::create(['name' => 'Tag 3']);
    $tag_3->save();
    // Create Nodes with references.
    EntityTestMulRevPub::create([
      'name' => 'Test Node 1',
      'field_tagify' => [$tag_1, $tag_2],
    ])->save();
    // Create Node 2 with field_tagify (entity reference field).
    EntityTestMulRevPub::create([
      'name' => 'Test Node 2',
      'field_tagify' => [$tag_1, $tag_3],
    ])->save();

    $account = $this->createUser(['view test entity']);
    $this->drupalLogin($account);

    search_api_cron();

    $this->drupalPlaceBlock('facet_block:tags');
  }

  /**
   * Tests tagify facets widget functionality.
   */
  public function testTagifyFacets(): void {
    $facet = Facet::load('tags');
    $facet->setWidget('tagify', [
      'show_numbers' => TRUE,
    ]);
    $facet->save();

    $this->drupalGet('/test-entity-view');

    $page = $this->getSession()->getPage();

    $assert_session = $this->assertSession();

    $page->find('css', '.tagify__input')->setValue('Tag');
    $assert_session->waitForElement('css', '.tagify__dropdown__item');
    $assert_session->waitForElementVisible('css', '.tagify__dropdown__item--active');

    // Output the new HTML.
    $this->htmlOutput($page->getHtml());

    $page->find('css', '.tagify__dropdown__item--active')->doubleClick();

    $current_url = $this->getSession()->getCurrentUrl();
    $this->assertStringContainsString('f%5B0%5D=tag%3A1', $current_url);

    // Test without show_numbers.
    $facet->setWidget('tagify', [
      'show_numbers' => FALSE,
    ]);
    $facet->save();
    $this->drupalGet('/test-entity-view');
    // @todo Improve assertions... refactor method??
  }

}
