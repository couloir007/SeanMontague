<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg\Functional;

use Drupal\node\Entity\NodeType;

/**
 * Tests the functionality of the Schema.org autocomplete element.
 *
 * @see \Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgAutocompleteControllerKernelTest
 * @covers \Drupal\schemadotorg\Element\SchemaDotOrgAutocomplete
 * @group schemadotorg
 */
class SchemaDotOrgAutocompleteElementTest extends SchemaDotOrgBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'schemadotorg_autocomplete_element_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ])->save();
  }

  /**
   * Test Schema.org autocomplete form.
   */
  public function testSchemaDotOrgSettingsElement(): void {
    $assert = $this->assertSession();

    // Check autocomplete submitted values.
    $this->drupalGet('schemadotorg-autocomplete-element-test');
    $this->submitForm([], 'Submit');
    $assert->responseContains('schemadotorg_autocomplete_type: Person');
    $assert->responseContains('Person: Person');
    $assert->responseContains('Organization: Organization');
    $assert->responseContains('schemadotorg_autocomplete_novalidate: Dog');
    $assert->responseContains('schemadotorg_autocomplete_thing: Thing');
    $assert->responseContains('schemadotorg_autocomplete_property: name');
    $assert->responseContains('additionalName: additionalName');
    $assert->responseContains('schemadotorg_autocomplete_bundles:');
    $assert->responseContains('article: article');

    // Check autocomplete Schema.org type validation.
    $this->drupalGet('schemadotorg-autocomplete-element-test');
    $edit = [
      'schemadotorg_autocomplete_type' => 'Cat',
      'schemadotorg_autocomplete_property' => 'paws',
      'schemadotorg_autocomplete_bundles' => 'Person, not_a_bundle',
    ];
    $this->submitForm($edit, 'Submit');
    $assert->responseContains('The Schema.org type <em class="placeholder">Cat</em> is not valid.');
    $assert->responseContains('The Schema.org property <em class="placeholder">paws</em> is not valid.');
    $assert->responseContains('The Schema.org type <em class="placeholder">not_a_bundle</em> is not valid.');

    // Check autocomplete Schema.org Thing validation.
    $this->drupalGet('schemadotorg-autocomplete-element-test');
    $edit = [
      'schemadotorg_autocomplete_thing' => 'Enumeration',
    ];
    $this->submitForm($edit, 'Submit');
    $assert->responseContains('The Schema.org type <em class="placeholder">Enumeration</em> is not a valid <em class="placeholder">Thing</em>.');
  }

}
