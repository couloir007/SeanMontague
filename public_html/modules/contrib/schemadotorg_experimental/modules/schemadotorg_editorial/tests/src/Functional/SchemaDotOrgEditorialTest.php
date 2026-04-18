<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg_editorial\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org editorial sidebar.
 *
 * @covers schemadotorg_sidebar_schemadotorg_mapping_insert()
 * @covers schemadotorg_sidebar_field_widget_inline_entity_form_simple_form_alter()
 * @covers schemadotorg_sidebar_node_view_alter()
 * @covers schemadotorg_editorial_node_prepare_form()
 * @group schemadotorg
 */
class SchemaDotOrgEditorialTest extends SchemaDotOrgBrowserTestBase {

  // phpcs:disable DrupalPractice.Objects.StrictSchemaDisabled.StrictConfigSchema
  /**
   * Disabled config schema checking temporarily until inline entity form fixes missing schema.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;
  // phpcs:enable DrupalPractice.Objects.StrictSchemaDisabled.StrictConfigSchema

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['schemadotorg_editorial'];

  /**
   * Test Schema.org editorial sidebar.
   */
  public function testEditorialSidebar(): void {
    $assert = $this->assertSession();

    // Create a Place.
    $this->createSchemaEntity('node', 'Place');

    // Check that the field storage is created.
    $this->assertNotNull(FieldStorageConfig::loadByName('node', 'field_editorial'));

    // Check that the field is created.
    $this->assertNotNull(FieldConfig::loadByName('node', 'place', 'field_editorial'));

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository */
    $entity_display_repository = \Drupal::service('entity_display.repository');

    // Create that the form display and component are created.
    $form_display = $entity_display_repository->getFormDisplay('node', 'place');
    $this->assertNotNull($form_display);
    $form_component = $form_display->getComponent('field_editorial');
    $this->assertEquals('inline_entity_form_simple', $form_component['type']);
    $form_group = $form_display->getThirdPartySetting('field_group', 'group_editorial');
    $this->assertEquals('Editorial information', $form_group['label']);
    $this->assertEquals('details_sidebar', $form_group['format_type']);
    $this->assertEquals(['field_editorial'], $form_group['children']);

    // Create that the view display and component are created.
    $view_display = $entity_display_repository->getViewDisplay('node', 'place');
    $this->assertNotNull($view_display);
    $view_component = $view_display->getComponent('field_editorial');
    $this->assertEquals('entity_reference_revisions_entity_view', $view_component['type']);
    $this->assertEquals('hidden', $view_component['label']);
    $view_group = $view_display->getThirdPartySetting('field_group', 'group_editorial');
    $this->assertEquals('Editorial information', $view_group['label']);
    $this->assertEquals('details', $view_group['format_type']);
    $this->assertEquals(['field_editorial'], $view_group['children']);

    $this->drupalLogin($this->rootUser);
    $this->drupalGet('node/add/place');
    // Check that 'Editorial sidebar' exists.
    $this->assertNotEmpty($this->cssSelect('details#edit-group-editorial'));
    // Check that the nested field does not exist.
    // @see schemadotorg_sidebar_field_widget_single_element_inline_entity_form_simple_form_alter()
    $this->assertEmpty($this->cssSelect('details#edit-group-editorial fieldset'));
    // Check that last updated data element exists.
    $assert->fieldExists('field_editorial[0][inline_entity_form][field_editorial_last_updated][0][value][date]');

    // Create a place node with editorial information.
    $node = $this->drupalCreateNode([
      'type' => 'place',
      'field_editorial' => Paragraph::create([
        'type' => 'editorial',
        'field_editorial_message' => ['value' => 'This is a message.'],
        'field_editorial_notes' => ['value' => 'This is a note'],
      ]),
    ]);
    $nid = $node->id();

    // Check displaying editorial note but not the message.
    $this->drupalGet("/node/$nid");
    $assert->responseContains('Editorial information');
    $assert->responseContains('This is a note');
    $assert->responseNotContains('This is a message.');

    // Check displaying the editorial message as a warning.
    // @see schemadotorg_editorial_node_prepare_form()
    $this->drupalGet("/node/$nid/edit");
    $assert->pageTextMatches('/Warning message\s+This is a message./');

    // Remove the editorial note.
    /** @var \Drupal\paragraphs\ParagraphInterface $editorial_paragraph */
    // @phpstan-ignore-next-line property.notFound
    $editorial_paragraph = $node->field_editorial->entity;
    // @phpstan-ignore-next-line property.notFound
    $editorial_paragraph->field_editorial_notes->value = '';
    $editorial_paragraph->save();

    // Check that nothing is displayed when there is no editorial information.
    // @see schemadotorg_sidebar_node_view_alter()
    $this->drupalGet("/node/$nid");
    $assert->responseNotContains('Editorial information');
    $assert->responseNotContains('This is a note');
    $assert->responseNotContains('This is a message.');
  }

}
