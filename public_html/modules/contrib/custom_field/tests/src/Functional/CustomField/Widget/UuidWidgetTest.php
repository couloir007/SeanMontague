<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Functional\CustomField\Widget;

use Drupal\Component\Uuid\Uuid;
use Drupal\Tests\custom_field\Functional\CustomField\CustomFieldFunctionalTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Functional tests for the uuid widget.
 *
 * The uuid widget is not editable: it renders as #type value and either
 * keeps an existing UUID or generates one on first create. UuidType sets
 * never_check_empty: TRUE so the generated value is always stored.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class UuidWidgetTest extends CustomFieldFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createCustomField(
      'field_test',
      [
        'item_uuid' => [
          'name' => 'item_uuid',
          'type' => 'uuid',
        ],
      ],
      [
        'item_uuid' => [
          'label' => 'Item UUID',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
        ],
      ],
    );

    $this->setFormDisplay('field_test', [
      'item_uuid' => [
        'type' => 'uuid',
        'weight' => 0,
        'label' => 'Item UUID',
      ],
    ]);
  }

  /**
   * Tests the subfield is not rendered as a visible form control.
   */
  public function testFieldNotVisibleOnForm(): void {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    $assert->fieldNotExists('field_test[0][item_uuid]');
    $assert->elementNotExists(
      'css',
      'input[name="field_test[0][item_uuid]"]'
    );
  }

  /**
   * Tests that saving a new node generates a valid UUID.
   */
  public function testUuidGeneratedOnCreate(): void {
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'UUID generate node',
    ], 'Save');

    $node = $this->loadNodeByTitle('UUID generate node');
    $uuid = $node->get('field_test')->item_uuid;
    $this->assertNotEmpty($uuid);
    $this->assertTrue(
      Uuid::isValid($uuid),
      sprintf('Expected a valid UUID, got: %s', $uuid)
    );
  }

  /**
   * Tests the generated UUID is stable across subsequent edit/saves.
   */
  public function testUuidStableAcrossEdits(): void {
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'UUID stable node',
    ], 'Save');

    $node = $this->loadNodeByTitle('UUID stable node');
    $original = $node->get('field_test')->item_uuid;
    $this->assertTrue(Uuid::isValid($original));

    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm([
      'title[0][value]' => 'UUID stable node updated',
    ], 'Save');

    $node = $this->reloadNode($node->id());
    $this->assertEquals($original, $node->get('field_test')->item_uuid);
    $this->assertEquals('UUID stable node updated', $node->label());
  }

  /**
   * Tests two separately created nodes get different UUIDs.
   */
  public function testUuidsAreUniquePerItem(): void {
    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'UUID unique node A',
    ], 'Save');
    $uuid_a = $this->loadNodeByTitle('UUID unique node A')
      ->get('field_test')->item_uuid;

    $this->drupalGet('node/add/page');
    $this->submitForm([
      'title[0][value]' => 'UUID unique node B',
    ], 'Save');
    $uuid_b = $this->loadNodeByTitle('UUID unique node B')
      ->get('field_test')->item_uuid;

    $this->assertNotEquals($uuid_a, $uuid_b);
    $this->assertTrue(Uuid::isValid($uuid_a));
    $this->assertTrue(Uuid::isValid($uuid_b));
  }

}
