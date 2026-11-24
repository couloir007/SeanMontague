<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\FunctionalJavascript\CustomField\Widget;

use Drupal\Component\Uuid\Uuid;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\custom_field\Traits\CustomFieldTestTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests uuid subfield behavior across AJAX rebuilds and on save.
 *
 * UuidWidgetTest (Functional) covers the saved-entity path: a UUID is generated
 * on create and is stable across later edit/save cycles. These tests cover the
 * pre-save path — AJAX rebuilds, and what actually reaches storage — which
 * behaves differently and was not otherwise exercised.
 *
 * testDeltaWithOnlyUuidIsDroppedOnSave and
 * testUuidIsBackfilledOntoDeltaWithoutOne characterize current behavior rather
 * than asserting it is desirable; see their docblocks.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 *
 * @see \Drupal\custom_field\Plugin\CustomField\FieldWidget\UuidWidget::widget()
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class UuidWidgetAjaxTest extends WebDriverTestBase {

  use CustomFieldTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'field_ui',
    'node',
    'custom_field',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Page']);
    $body = FieldConfig::loadByName('node', 'page', 'body');
    $body?->delete();

    $admin = $this->drupalCreateUser([
      'administer content types',
      'administer node fields',
      'administer node form display',
      'access content',
      'create page content',
      'edit any page content',
    ]);
    $this->drupalLogin($admin);

    $this->createCustomField(
      'field_test',
      [
        'item_uuid' => [
          'name' => 'item_uuid',
          'type' => 'uuid',
        ],
        'title' => [
          'name' => 'title',
          'type' => 'string',
          'length' => 255,
        ],
      ],
      [
        'item_uuid' => [
          'label' => 'Item UUID',
          'check_empty' => FALSE,
          'required' => FALSE,
          'description' => '',
          'description_display' => 'after',
        ],
        'title' => [
          'label' => 'Title',
          // Deliberate: an item is only considered non-empty when this is
          // filled. See CustomItem::isEmpty().
          'check_empty' => TRUE,
          'required' => FALSE,
          'description' => '',
          'description_display' => 'after',
        ],
      ],
    );

    $form_display = EntityFormDisplay::load('node.page.default')
      ?: EntityFormDisplay::create([
        'targetEntityType' => 'node',
        'bundle' => 'page',
        'mode' => 'default',
        'status' => TRUE,
      ]);
    $form_display->setComponent('field_test', [
      'type' => 'custom_stacked',
      'weight' => 10,
      'region' => 'content',
      'settings' => [
        'wrapper' => 'details',
        'open' => TRUE,
        'fields' => [
          'item_uuid' => ['type' => 'uuid', 'weight' => 0, 'label' => 'Item UUID'],
          'title' => ['type' => 'text', 'weight' => 1, 'label' => 'Title'],
        ],
      ],
    ])->save();
  }

  /**
   * Returns the UUIDs currently rendered for field_test, keyed by delta.
   *
   * Reads the hidden inputs directly, which is the value that will actually be
   * submitted — not the widget's freshly generated #default_value, which is
   * recomputed on every build and discarded whenever input is present.
   *
   * @return array<int, string>
   *   UUID strings keyed by delta.
   */
  protected function renderedUuids(): array {
    $found = [];
    $inputs = $this->getSession()->getPage()->findAll(
      'css',
      'input[type="hidden"][name^="field_test["]'
    );
    foreach ($inputs as $input) {
      $name = (string) $input->getAttribute('name');
      if (preg_match('/^field_test\[(\d+)\]\[item_uuid\]$/', $name, $matches) === 1) {
        $found[(int) $matches[1]] = (string) $input->getValue();
      }
    }
    ksort($found);

    return $found;
  }

  /**
   * Presses field_test's "Add another item" button and waits for the rebuild.
   */
  protected function addAnotherItem(): void {
    $this->getSession()->getPage()->pressButton('field_test_add_more');
    $this->assertSession()->assertWaitOnAjaxRequest();
  }

  /**
   * Tests that an unsaved delta keeps its UUID across AJAX rebuilds.
   *
   * The widget() method runs on every build, and for a delta that is not in
   * storage yet there is nothing in $items to read a UUID back from. The value
   * therefore has to come back with the submission, which is why the widget
   * renders a hidden input rather than a '#type' => 'value' — core excludes
   * 'value' elements from input processing, so one would send nothing to the
   * browser and every rebuild would mint a different UUID.
   */
  public function testUnsavedDeltaUuidSurvivesAjaxRebuild(): void {
    $this->drupalGet('/node/add/page');
    $this->assertNotEmpty($this->assertSession()->waitForElement('css', 'input[name="field_test[0][item_uuid]"]'));

    $before = $this->renderedUuids();
    $this->assertCount(1, $before, 'A single delta is rendered on a fresh node form.');
    $this->assertTrue(Uuid::isValid($before[0]), 'The initial delta carries a valid UUID.');

    $this->addAnotherItem();

    $after = $this->renderedUuids();
    $this->assertCount(2, $after, 'Adding an item renders a second delta.');
    $this->assertTrue(Uuid::isValid($after[1]));
    $this->assertSame(
      $before[0],
      $after[0],
      'The existing unsaved delta kept its UUID across the rebuild.'
    );
    $this->assertNotSame($after[0], $after[1], 'Each delta gets a distinct UUID.');

    // A second rebuild, this time with a sibling subfield populated.
    $this->getSession()->getPage()->fillField('field_test[0][title]', 'Filled');
    $this->addAnotherItem();

    $third = $this->renderedUuids();
    $this->assertCount(3, $third);
    $this->assertSame($before[0], $third[0], 'Delta 0 is still stable after a second rebuild.');
    $this->assertSame($after[1], $third[1], 'Delta 1 is still stable after a second rebuild.');
    $this->assertNotSame($third[0], $third[2]);
    $this->assertNotSame($third[1], $third[2]);
  }

  /**
   * Tests that the UUID a delta was built with is the one that gets stored.
   *
   * Closes the loop on the rebuild test above: stability is only useful if the
   * value that survived the rebuilds is also the value that reaches storage.
   */
  public function testUuidRenderedBeforeSaveIsTheUuidStored(): void {
    $this->drupalGet('/node/add/page');
    $this->assertNotEmpty($this->assertSession()->waitForElement('css', 'input[name="field_test[0][item_uuid]"]'));

    $first_render = $this->renderedUuids();

    $this->addAnotherItem();
    $page = $this->getSession()->getPage();
    $page->fillField('title[0][value]', 'Round trip node');
    $page->fillField('field_test[0][title]', 'First');
    $page->fillField('field_test[1][title]', 'Second');

    $pre_save = $this->renderedUuids();
    $this->assertSame($first_render[0], $pre_save[0], 'Delta 0 survived the rebuild.');

    $page->pressButton('Save');
    $this->assertSession()->waitForText('Round trip node');

    $node = $this->loadNodeByTitle('Round trip node');

    $stored = [];
    foreach ($node->get('field_test') as $delta => $item) {
      $stored[$delta] = $item->item_uuid;
    }

    $this->assertCount(2, $stored);
    $this->assertSame($pre_save[0], $stored[0], 'Delta 0 stored the UUID it was built with.');
    $this->assertSame($pre_save[1], $stored[1], 'Delta 1 stored the UUID it was built with.');
  }

  /**
   * Tests that opening and saving an untouched entity changes nothing.
   *
   * Guards the fix against the obvious way it could go wrong: a widget that
   * generates on build must not stamp new values onto deltas that already have
   * one, and must not grow the field by persisting the trailing empty delta
   * that the form renders for the next item.
   */
  public function testNoOpEditDoesNotAlterStoredUuids(): void {
    $this->drupalGet('/node/add/page');
    $this->assertNotEmpty($this->assertSession()->waitForElement('css', 'input[name="field_test[0][item_uuid]"]'));

    $this->addAnotherItem();
    $page = $this->getSession()->getPage();
    $page->fillField('title[0][value]', 'No-op node');
    $page->fillField('field_test[0][title]', 'First');
    $page->fillField('field_test[1][title]', 'Second');
    $page->pressButton('Save');
    $this->assertSession()->waitForText('No-op node');

    $node = $this->loadNodeByTitle('No-op node');
    $before = [];
    foreach ($node->get('field_test') as $delta => $item) {
      $before[$delta] = $item->item_uuid;
    }
    $this->assertCount(2, $before);

    // Open the edit form and save it again without touching anything.
    $this->drupalGet('/node/' . $node->id() . '/edit');
    $this->assertNotEmpty($this->assertSession()->waitForElement('css', 'input[name="field_test[0][item_uuid]"]'));
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->waitForText('No-op node');

    $reloaded = $this->reloadNode($node->id());

    $after = [];
    foreach ($reloaded->get('field_test') as $delta => $item) {
      $after[$delta] = $item->item_uuid;
    }

    $this->assertSame(
      $before,
      $after,
      'A no-op edit neither changed an existing UUID nor added a delta.'
    );
  }

  /**
   * Tests that a saved delta keeps its UUID across an AJAX rebuild.
   *
   * This is the boundary of the defect characterized above: once a delta exists
   * in storage, UuidWidget::widget() finds a value on $items[$delta] and reuses
   * it, so rebuilding the widget leaves it alone. Unlike the other two tests
   * here, this asserts genuinely desired behavior and must keep passing.
   */
  public function testSavedDeltaUuidSurvivesAjaxRebuild(): void {
    $this->drupalGet('/node/add/page');
    $this->assertNotEmpty($this->assertSession()->waitForElement('css', 'input[name="field_test[0][item_uuid]"]'));

    $page = $this->getSession()->getPage();
    $page->fillField('title[0][value]', 'Saved delta node');
    $page->fillField('field_test[0][title]', 'Persisted');
    $page->pressButton('Save');
    $this->assertSession()->waitForText('Saved delta node');

    $node = $this->loadNodeByTitle('Saved delta node');
    $stored_uuid = $node->get('field_test')->item_uuid;
    $this->assertTrue(Uuid::isValid($stored_uuid));

    $this->drupalGet('/node/' . $node->id() . '/edit');
    $this->assertNotEmpty($this->assertSession()->waitForElement('css', 'input[name="field_test[0][item_uuid]"]'));
    $this->assertSame(
      $stored_uuid,
      $this->renderedUuids()[0],
      'The saved delta renders the UUID that is in storage.'
    );

    $this->addAnotherItem();

    $this->assertSame(
      $stored_uuid,
      $this->renderedUuids()[0],
      'A saved delta keeps its UUID across an AJAX rebuild.'
    );
  }

  /**
   * Tests that a stored delta with no UUID is given one by a no-op edit.
   *
   * CHARACTERIZATION OF CURRENT BEHAVIOR, recorded because it is a write that
   * a "no-op" edit performs and someone will eventually be surprised by it.
   *
   * A delta can reach storage without a UUID — written through the API, a
   * migration, or saved before the uuid subfield was added to the field. The
   * widget's documented job is to generate when the value is empty, and it does
   * not distinguish "empty because new" from "empty because it predates the
   * subfield". So merely opening and saving the form backfills a UUID.
   *
   * That is almost certainly what you want (it is how existing content acquires
   * identity), but it is a content write triggered by a form save that changed
   * nothing the editor can see. If it ever needs to stop, the place to change
   * is UuidWidget::resolveUuid().
   */
  public function testUuidIsBackfilledOntoDeltaWithoutOne(): void {
    // Write a delta with no UUID at all, bypassing the widget.
    $node = $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'Backfill node',
      'field_test' => [
        ['title' => 'No uuid on this one'],
      ],
    ]);

    $this->assertNull(
      $node->get('field_test')->item_uuid,
      'Precondition: the stored delta has no UUID.'
    );

    $this->drupalGet('/node/' . $node->id() . '/edit');
    $this->assertNotEmpty($this->assertSession()->waitForElement('css', 'input[name="field_test[0][item_uuid]"]'));
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->waitForText('No-op node');

    $reloaded = $this->reloadNode($node->id());

    $backfilled = $reloaded->get('field_test')->item_uuid;
    $this->assertNotNull($backfilled);
    $this->assertTrue(
      Uuid::isValid($backfilled),
      'A no-op edit backfilled a valid UUID onto the delta that lacked one.'
    );
    $this->assertCount(1, $reloaded->get('field_test'), 'No extra delta was added.');
  }

  /**
   * Tests that a delta carrying only a UUID is dropped on save.
   *
   * CHARACTERIZATION OF CURRENT BEHAVIOR.
   *
   * WidgetBase::extractFormValues() calls filterEmptyItems(), and
   * CustomItem::isEmpty() reports an item empty as soon as any subfield with
   * check_empty set is blank. never_check_empty on the uuid type keeps the UUID
   * itself from counting as empty, but does not rescue the item from a blank
   * sibling. So "add an item, save without typing anything" silently discards
   * the delta and the UUID that was generated for it.
   *
   * Note this happens at save, not during the session: the delta stays in the
   * form across AJAX rebuilds because the form's delta count comes from
   * items_count in widget state, not from the entity's item list.
   */
  public function testDeltaWithOnlyUuidIsDroppedOnSave(): void {
    $this->drupalGet('/node/add/page');
    $this->assertNotEmpty($this->assertSession()->waitForElement('css', 'input[name="field_test[0][item_uuid]"]'));

    $this->addAnotherItem();
    $this->assertCount(2, $this->renderedUuids(), 'Two deltas are present before saving.');

    // Delta 1's title is deliberately left blank.
    $page = $this->getSession()->getPage();
    $page->fillField('title[0][value]', 'Blank delta node');
    $page->fillField('field_test[0][title]', 'Only this one is filled');

    $pre_save = $this->renderedUuids();
    $page->pressButton('Save');
    $this->assertSession()->waitForText('Blank delta node');

    $node = $this->loadNodeByTitle('Blank delta node');

    $this->assertCount(
      1,
      $node->get('field_test'),
      'The delta carrying only a UUID was dropped by filterEmptyItems().'
    );
    $this->assertSame('Only this one is filled', $node->get('field_test')->title);
    $this->assertSame(
      $pre_save[0],
      $node->get('field_test')->item_uuid,
      'The surviving delta stored the UUID it was rendered with.'
    );
  }

  /**
   * Tests that clearing the hidden input cannot strip an item's UUID.
   *
   * The value lives in the page now, so it can come back empty — cleared in the
   * DOM, or dropped by something altering the form. An item that already has a
   * UUID must keep it whatever is submitted.
   */
  public function testClearedInputCannotRemoveAnExistingUuid(): void {
    $this->drupalGet('/node/add/page');
    $this->assertNotEmpty($this->assertSession()->waitForElement('css', 'input[name="field_test[0][item_uuid]"]'));

    $page = $this->getSession()->getPage();
    $page->fillField('title[0][value]', 'Cleared input node');
    $page->fillField('field_test[0][title]', 'Keeps its identity');
    $page->pressButton('Save');
    $this->assertSession()->waitForText('Cleared input node');

    $node = $this->loadNodeByTitle('Cleared input node');
    $original_uuid = $node->get('field_test')->item_uuid;
    $this->assertTrue(Uuid::isValid($original_uuid));

    // Re-open the form, wipe the hidden input, and save.
    $this->drupalGet('/node/' . $node->id() . '/edit');
    $this->assertNotEmpty($this->assertSession()->waitForElement('css', 'input[name="field_test[0][item_uuid]"]'));
    $this->assertSame($original_uuid, $this->renderedUuids()[0]);

    $this->clearUuidInput(0);
    $this->assertSame('', $this->renderedUuids()[0], 'The input really was cleared.');

    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->waitForText('Cleared input node');

    $reloaded = $this->reloadNode($node->id());
    $this->assertSame(
      $original_uuid,
      $reloaded->get('field_test')->item_uuid,
      'The stored UUID survived the cleared input.'
    );
    $this->assertSame('Keeps its identity', $reloaded->get('field_test')->title);
  }

  /**
   * Tests that clearing the input on a new item still stores a valid UUID.
   *
   * The other half of the rule: an item that never had a UUID cannot be saved
   * without one just because the input arrived empty.
   */
  public function testClearedInputOnNewItemStillStoresUuid(): void {
    $this->drupalGet('/node/add/page');
    $this->assertNotEmpty($this->assertSession()->waitForElement('css', 'input[name="field_test[0][item_uuid]"]'));

    $page = $this->getSession()->getPage();
    $page->fillField('title[0][value]', 'Cleared new item node');
    $page->fillField('field_test[0][title]', 'Brand new');

    $this->clearUuidInput(0);
    $this->assertSame('', $this->renderedUuids()[0], 'The input really was cleared.');

    $page->pressButton('Save');
    $this->assertSession()->waitForText('Cleared new item node');

    $node = $this->loadNodeByTitle('Cleared new item node');
    $stored = $node->get('field_test')->item_uuid;
    $this->assertNotNull($stored);
    $this->assertTrue(
      Uuid::isValid($stored),
      sprintf('Expected a valid generated UUID, got: %s', var_export($stored, TRUE))
    );
  }

  /**
   * Blanks the hidden uuid input for a delta, as a tamperer or a bug would.
   *
   * @param int $delta
   *   The delta whose input should be cleared.
   */
  protected function clearUuidInput(int $delta): void {
    $this->getSession()->executeScript(
      sprintf(
        'document.querySelector(\'input[name="field_test[%d][item_uuid]"]\').value = "";',
        $delta
      )
    );
  }

}
