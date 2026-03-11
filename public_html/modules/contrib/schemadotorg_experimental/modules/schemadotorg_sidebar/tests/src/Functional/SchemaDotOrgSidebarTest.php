<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg_sidebar\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org sidebar form.
 *
 * @covers schemadotorg_sidebar_schemadotorg_mapping_insert()
 * @covers schemadotorg_sidebar_field_widget_inline_entity_form_simple_form_alter()
 * @covers schemadotorg_sidebar_node_view_alter()
 * @group schemadotorg
 */
class SchemaDotOrgSidebarTest extends SchemaDotOrgBrowserTestBase {

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
  protected static $modules = ['schemadotorg_sidebar_test'];

  /**
   * Test Schema.org sidebar.
   */
  public function testSidebar(): void {
    $assert = $this->assertSession();

    // Create a Place.
    $this->createSchemaEntity('node', 'Place');

    // Check that the field storage is created.
    $this->assertNotNull(FieldStorageConfig::loadByName('node', 'field_sidebar_test'));

    // Check that the field is created.
    $this->assertNotNull(FieldConfig::loadByName('node', 'place', 'field_sidebar_test'));

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository */
    $entity_display_repository = \Drupal::service('entity_display.repository');

    // Create that the form display and component are created.
    $form_display = $entity_display_repository->getFormDisplay('node', 'place');
    $this->assertNotNull($form_display);
    $form_component = $form_display->getComponent('field_sidebar_test');
    $this->assertEquals('inline_entity_form_simple', $form_component['type']);
    $form_group = $form_display->getThirdPartySetting('field_group', 'group_sidebar_test');
    $this->assertEquals('Sidebar test', $form_group['label']);
    $this->assertEquals('details', $form_group['format_type']);
    $expected_settings = [
      'open' => TRUE,
      'description' => 'This is a test of paragraph type being used as a sidebar.',
    ];
    $this->assertEquals($expected_settings, $form_group['format_settings']);
    $this->assertEquals(['field_sidebar_test'], $form_group['children']);

    // Create that the view display and component are created.
    $view_display = $entity_display_repository->getViewDisplay('node', 'place');
    $this->assertNotNull($view_display);
    $view_component = $view_display->getComponent('field_sidebar_test');
    $this->assertEquals('entity_reference_revisions_entity_view', $view_component['type']);
    $this->assertEquals('hidden', $view_component['label']);
    $view_group = $view_display->getThirdPartySetting('field_group', 'group_sidebar_test');
    $this->assertEquals('Sidebar test', $view_group['label']);
    $this->assertEquals('details', $view_group['format_type']);
    $expected_settings = [
      'open' => TRUE,
    ];
    $this->assertEquals($expected_settings, $view_group['format_settings']);

    $this->assertEquals(['field_sidebar_test'], $view_group['children']);

    $this->drupalLogin($this->rootUser);
    $this->drupalGet('node/add/place');
    // Check that 'Editorial sidebar' exists.
    $this->assertNotEmpty($this->cssSelect('details#edit-group-sidebar-test'));
    // Check that the nested field does not exist.
    // @see schemadotorg_sidebar_field_widget_single_element_inline_entity_form_simple_form_alter()
    $this->assertEmpty($this->cssSelect('details#edit-group-sidebar-test fieldset'));

    // Create a place node with sidebar test.
    $node = $this->drupalCreateNode([
      'type' => 'place',
      'field_sidebar_test' => Paragraph::create([
        'type' => 'sidebar_test',
        'field_sidebar_text' => ['value' => 'This is a note'],
      ]),
    ]);
    $nid = $node->id();

    // Check displaying sidebar text..
    $this->drupalGet("/node/$nid");
    $assert->responseContains('Sidebar test');
    $assert->responseContains('This is a note');

    // Remove the sidebar text.
    /** @var \Drupal\paragraphs\ParagraphInterface $sidebar_paragraph */
    $sidebar_paragraph = $node->get('field_sidebar_test')->entity;
    $sidebar_paragraph->get('field_sidebar_text')->value = '';
    $sidebar_paragraph->save();

    // Check that nothing is displayed when there is no editorial information.
    // @see schemadotorg_sidebar_node_view_alter()
    $this->drupalGet("/node/$nid");
    $assert->responseNotContains('Sidebar test');
    $assert->responseNotContains('This is a note');
  }

}
