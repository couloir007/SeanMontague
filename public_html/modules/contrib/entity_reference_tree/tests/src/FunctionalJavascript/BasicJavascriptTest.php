<?php

namespace Drupal\Tests\entity_reference_tree\FunctionalJavascript;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the Entity Reference Tree functionality with JavaScript.
 *
 * @group entity_reference_tree
 */
class BasicJavascriptTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'field', 'field_ui', 'entity_reference_tree'];

  /**
   * The default theme used for testing.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with permission to administer nodes and content types.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A node for testing.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $testNode;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a content type with an entity reference field.
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Page']);
    $field_name = 'field_referenced_entities';

    $this->createEntityReferenceField('node', 'page', $field_name, 'Referenced Entities', 'node');

    // Create nodes to be referenced.
    $node1 = $this->drupalCreateNode(['type' => 'page', 'title' => 'Node 1']);
    $node2 = $this->drupalCreateNode(['type' => 'page', 'title' => 'Node 2']);
    $node3 = $this->drupalCreateNode(['type' => 'page', 'title' => 'Node 3']);

    // Create a node that references other nodes.
    $this->testNode = $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'Node with References',
      $field_name => [
        ['target_id' => $node1->id()],
        ['target_id' => $node2->id()],
        ['target_id' => $node3->id()],
      ],
    ]);

    // Create and log in an administrative user.
    $this->adminUser = $this->drupalCreateUser([
      'administer nodes',
      'administer content types',
      'edit any page content',
      'administer node fields',
      'administer node display',
      'administer node form display',
    ]);
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/structure/types/manage/page/form-display');
  }

  /**
   * Tests the Entity Reference Tree functionality with JavaScript.
   */
  public function testEntityReferenceTreeJavascript() {
    $assert = $this->assertSession();
    // Check that the node was created and references the correct nodes.
    $this->drupalGet('node/' . $this->testNode->id() . '/edit');

    $button = $assert->waitForLink('Node tree');
    // Check the entity reference tree button exists.
    $this->assertNotEmpty($button);

    // Simulate a click to expand the tree.
    $button->click();
    $node_1 = $assert->waitForLink('Node 1');
    $node_2 = $assert->waitForLink('Node 2');
    $node_3 = $assert->waitForLink('Node 3');
    $this->assertNotEmpty($node_1);
    $this->assertNotEmpty($node_2);
    $this->assertNotEmpty($node_3);
    $assert->pageTextContains('Selected (3 of unlimited): Node 1 (1), Node 2 (2), Node 3 (3)');
  }

  /**
   * Creates an entity reference field.
   *
   * @param string $entity_type
   *   The entity type to which the field will be added.
   * @param string $bundle
   *   The bundle to which the field will be added.
   * @param string $field_name
   *   The machine name of the field.
   * @param string $label
   *   The human-readable label of the field.
   * @param string $target_type
   *   The target entity type for the entity reference.
   */
  protected function createEntityReferenceField($entity_type, $bundle, $field_name, $label, $target_type) {
    // Add a new field storage definition.
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'type' => 'entity_reference',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => [
        'target_type' => $target_type,
      ],
    ])->save();

    // Add a new field instance to the specified bundle.
    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'label' => $label,
      'settings' => [
        'handler' => 'default',
      ],
    ])->save();

    // Update the entity form display settings to show the field.
    $form_display = \Drupal::service('entity_display.repository')
      ->getFormDisplay($entity_type, $bundle, 'default');
    $form_display->setComponent($field_name, [
      'type' => 'entity_reference_tree',
      'weight' => 5,
    ])->save();
  }

}
