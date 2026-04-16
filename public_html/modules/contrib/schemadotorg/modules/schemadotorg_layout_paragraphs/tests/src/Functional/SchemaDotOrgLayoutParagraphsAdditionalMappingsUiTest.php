<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg_paragraphs\Functional;

use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org layout paragraphs with additional mappings UI.
 *
 * @covers schemadotorg_layout_paragraphs_form_schemadotorg_mapping_form_alter()
 * @group schemadotorg
 */
class SchemaDotOrgLayoutParagraphsAdditionalMappingsUiTest extends SchemaDotOrgBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'schemadotorg_additional_mappings',
    'schemadotorg_layout_paragraphs',
    'schemadotorg_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Check that access is allowed to 'Add Schema.org type' page.
    $account = $this->drupalCreateUser([
      'administer content types',
      'administer node fields',
      'administer schemadotorg',
    ]);
    $this->drupalLogin($account);
  }

  /**
   * Test Schema.org layout paragraphs additional mappings UI.
   */
  public function testLayoutParagraphsAdditionalMappingsUi(): void {
    $assert = $this->assertSession();

    $this->config('schemadotorg_layout_paragraphs.settings')
      ->set('default_types', ['Organization'])
      ->save();

    $this->config('schemadotorg_additional_mappings.settings')
      ->set('default_properties.WebPage', [
        'dateCreated',
        'dateModified',
        'inLanguage',
        'name',
        'primaryImageOfPage',
        'relatedLink',
        'significantLink',
        'mainEntity',
      ])
      ->save();

    $this->drupalGet('admin/structure/types/schemadotorg', ['query' => ['type' => 'Organization']]);

    // Check that Organization has layout enabled by default.
    $assert->responseContains('Schema.org layout');
    $assert->checkboxChecked('mapping[properties][mainEntity][field][name]');

    // Check that WebPage additional mappings does not display mainEntity.
    $assert->responseContains('Schema.org layout');
    $assert->fieldExists('mapping[additional_mappings][WebPage][schema_properties][primaryImageOfPage]');
    $assert->fieldNotExists('mapping[additional_mappings][WebPage][schema_properties][mainEntity]');

    // Create the Organization Schema.org mapping.
    $this->submitForm([], 'Save');

    // Get the Organization Schema.org mapping.
    $entity_type_manager = $this->container->get('entity_type.manager');
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping */
    $mapping = $entity_type_manager
      ->getStorage('schemadotorg_mapping')
      ->load('node.organization');

    // Check that the mapping has the mainEntity property associated
    // with the WebPage additional mapping.
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $this->assertFalse($mapping->hasSchemaPropertyMapping('mainEntity'));
    $this->assertTrue($mapping->hasSchemaPropertyMapping('mainEntity', TRUE));
  }

}
