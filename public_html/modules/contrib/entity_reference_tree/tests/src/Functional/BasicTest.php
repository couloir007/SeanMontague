<?php

namespace Drupal\Tests\entity_reference_tree\Functional;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Entity Reference Tree functionality.
 *
 * @group entity_reference_tree
 */
class BasicTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'field', 'entity_reference_tree'];

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
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests the Entity Reference Tree functionality.
   */
  public function testEntityReferenceTree() {
    $assert = $this->assertSession();
    // Check that the node was created and references the correct nodes.
    $this->drupalGet('node/' . $this->testNode->id() . '/edit');
    $assert->statusCodeEquals(200);
    $assert->linkExists('Node tree');
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
