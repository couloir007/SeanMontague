<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field_viewfield\Kernel\CustomField\Type;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\custom_field\Plugin\Field\FieldType\CustomItem;
use Drupal\custom_field_viewfield\Plugin\CustomField\FieldType\ViewfieldType;
use Drupal\custom_field_viewfield\Plugin\CustomField\FieldWidget\ViewfieldSelectWidget;
use Drupal\custom_field_viewfield\Plugin\DataType\CustomFieldViewfield;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\Tests\custom_field\Kernel\CustomField\CustomFieldTypeKernelTestBase;
use Drupal\user\Entity\User;
use Drupal\views\Entity\View;
use Drupal\views\ViewEntityInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the 'viewfield' custom field sub-field type.
 *
 * Core has no viewfield, so the model is the entity reference sub-field type
 * this type extends, and core's EntityReferenceItemTest behind it: the main
 * column stores the id of a view, and the referenced view is the computed
 * sibling property 'sub__entity'. What a viewfield adds is three stored
 * siblings: the display id ('sub__display'), the argument string
 * ('sub__arguments') and the number of items to display ('sub__items').
 *
 * Two things drive the shape of this class.
 *
 * 1. Those siblings are written by the main property's data type, from the
 *    nested value shape the widget, JSON:API, REST and migrations produce
 *    (target_id, display_id, arguments, items_to_display), and core's
 *    Map::setValue() re-syncs every already-instantiated property object from
 *    the incoming values array. The tests that guard that interaction against
 *    repeated setValue() calls, as Paragraphs performs them, are kept here
 *    under their original names.
 * 2. The constraint the type inherits (EntityType, on 'sub__entity') cannot be
 *    triggered through any supported write path, so it is declared in
 *    ::unreachableConstraints() and proven by
 *    ::testEntityTypeConstraintCannotBeTriggered(). The validation cases are
 *    the automatic primitive-type checks the siblings' core data types carry.
 *
 * What is deliberately NOT covered here, to avoid duplicating coverage that
 * already exists or belongs elsewhere: the widget and its settings form (the
 * Functional and FunctionalJavascript ViewfieldSelectWidgetTest classes), the
 * formatter and rendering, the JSON:API normalization, the token browser
 * setting, translation of the siblings (needs the language and content
 * translation modules), and the default widget/formatter wiring
 * (TypeRegistryTest). The 'force_default' setting is restated in the field
 * settings but not varied: only the formatter reads it.
 *
 * @group custom_field
 * @group custom_field_viewfield
 * @runTestsInSeparateProcesses
 * @covers \Drupal\custom_field_viewfield\Plugin\CustomField\FieldType\ViewfieldType
 */
#[Group('custom_field')]
#[Group('custom_field_viewfield')]
#[RunTestsInSeparateProcesses]
#[CoversClass(ViewfieldType::class)]
class ViewfieldTypeTest extends CustomFieldTypeKernelTestBase {

  /**
   * {@inheritdoc}
   *
   * 'file' is needed by any re-save of a loaded host: the custom field's item
   * list reconciles file usage on update and loads the file storage to do so,
   * whatever the sub-field types.
   */
  protected static $modules = [
    'file',
    'filter',
    'views',
    'custom_field_viewfield',
  ];

  /**
   * The field created in setUp() for the sibling-property tests.
   */
  private string $fieldName;

  /**
   * {@inheritdoc}
   *
   * The node_access table and the file schema serve the re-saves of loaded
   * hosts, see $modules.
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('node', ['node_access']);
    $this->installEntitySchema('file');
    $this->installConfig(['filter']);

    $this->createView('view_one', [
      'page_1' => [
        'display_title' => 'Page One',
        'display_plugin' => 'page',
        'display_options' => ['path' => 'view-one-page'],
      ],
    ]);
    $this->createView('view_two', [
      'embed_1' => [
        'display_title' => 'Embed',
        'display_plugin' => 'embed',
        'display_options' => [],
      ],
    ]);

    $this->fieldName = $this->createCaseField([], 'main');
  }

  /**
   * {@inheritdoc}
   */
  protected static function typeId(): string {
    return 'viewfield';
  }

  /**
   * {@inheritdoc}
   *
   * 'target_type' is a required column key: the type never sets it itself,
   * and the inherited schema() and propertyDefinitions() destructure it
   * unguarded. The storage settings form and the update path always write
   * 'view' for this type.
   */
  protected function defaultColumn(string $name): array {
    return parent::defaultColumn($name) + [
      'target_type' => 'view',
    ];
  }

  /**
   * {@inheritdoc}
   *
   * Restated here rather than read from the shipped test config.
   */
  protected function defaultFieldSettings(): array {
    return [
      'force_default' => 0,
      'allowed_views' => [
        'view_one' => ['default' => 'default', 'page_1' => 'page_1'],
        'view_two' => ['default' => 'default', 'embed_1' => 'embed_1'],
      ],
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   *
   * Every expectation reads the stored item through
   * FieldItemInterface::get(), so the item count comes back as the string the
   * database returns. The first row is the retired test plugin's first case,
   * with fixed arguments instead of random ones.
   */
  protected function roundTripCases(): array {
    return [
      'a view, display, arguments and item count round-trip' => [
        'value' => [
          'sub' => 'view_two',
          'sub__display' => 'embed_1',
          'sub__arguments' => 'arg/one',
          'sub__items' => 10,
        ],
        'expected' => [
          'sub' => 'view_two',
          'sub__display' => 'embed_1',
          'sub__arguments' => 'arg/one',
          'sub__items' => '10',
        ],
      ],
      'the nested value shape is mapped onto the siblings' => [
        'value' => [
          'sub' => [
            'target_id' => 'view_one',
            'display_id' => 'page_1',
            'arguments' => 'a/b',
            'items_to_display' => 7,
          ],
        ],
        'expected' => [
          'sub' => 'view_one',
          'sub__display' => 'page_1',
          'sub__arguments' => 'a/b',
          'sub__items' => '7',
        ],
      ],
      'a view id alone leaves the siblings empty' => [
        'value' => ['sub' => 'view_one'],
        'expected' => ['sub' => 'view_one', 'sub__display' => NULL, 'sub__arguments' => NULL, 'sub__items' => NULL],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   *
   * The type declares no constraint of its own that can be triggered. These
   * are the automatic primitive-type checks of the siblings' core data types,
   * which reject a value of the wrong shape.
   */
  protected function validationCases(): array {
    $message = 'This value should be of the correct primitive type.';
    $base = ['sub' => 'view_one', 'sub__display' => 'page_1'];

    return [
      'a non-numeric item count' => [
        'value' => $base + ['sub__items' => 'abc'],
        'property' => 'sub__items',
        'message' => $message,
      ],
      'a fractional item count' => [
        'value' => $base + ['sub__items' => '1.5'],
        'property' => 'sub__items',
        'message' => $message,
      ],
      'an array as the argument string' => [
        'value' => $base + ['sub__arguments' => ['a', 'b']],
        'property' => 'sub__arguments',
        'message' => $message,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function unreachableConstraints(): array {
    return [
      [
        'property' => 'sub__entity',
        'constraint' => 'EntityType',
        'reason' => 'The computed entity is always loaded from the view storage, and assigning an entity to it is ignored, so its value can never be of another entity type.',
        'test' => 'testEntityTypeConstraintCannotBeTriggered',
      ],
    ];
  }

  /**
   * Runs the real viewfield_select widget's massageFormValue().
   *
   * @param mixed $submitted
   *   The raw submitted subfield form values.
   *
   * @return mixed
   *   The massaged subfield value.
   */
  protected function massageSubfieldValue(mixed $submitted): mixed {
    $field = FieldConfig::loadByName('node', 'page', $this->fieldName);
    $this->assertNotNull($field);
    $settings = $field->getSettings();
    $subfield = $this->typeManager->getCustomFieldItems($settings)['sub'];
    $widget = $this->widgetManager->getInstance(
      $this->widgetManager->createOptionsForInstance($this->fieldName, $subfield, 'viewfield_select', [], 'default')
    );
    $this->assertInstanceOf(ViewfieldSelectWidget::class, $widget, 'Failed to create the viewfield_select widget.');

    return $widget->massageFormValue($submitted, $settings['columns']['sub']);
  }

  /**
   * Returns the item values a submitted viewfield widget produces.
   *
   * Runs the real widget plugin and then the promotion loop of
   * CustomWidgetBase::massageFormValues(), so the fixture cannot drift from
   * production.
   *
   * @param array<string, mixed>|null $submitted
   *   Raw submitted subfield values, or NULL for a fully populated default.
   *
   * @return array<string, mixed>
   *   The massaged item values.
   */
  protected function massagedWidgetValues(?array $submitted = NULL): array {
    $submitted ??= [
      'target_id' => 'view_one',
      'display_id' => 'page_1',
      'view_options' => [
        'arguments' => 'a/b',
        'items_to_display' => '7',
      ],
    ];

    $massaged = $this->massageSubfieldValue($submitted);
    $values = ['sub' => $massaged];
    if (is_array($massaged)) {
      foreach ($massaged as $sub_key => $sub_value) {
        $values['sub__' . $sub_key] = $sub_value;
      }
    }

    return $values;
  }

  /**
   * Returns the stored siblings of a saved node, read back from storage.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The saved node.
   * @param int $delta
   *   The field delta to read.
   *
   * @return array<string, mixed>
   *   The reloaded view/display/arguments/items values.
   */
  protected function storedValues(Node $node, int $delta = 0): array {
    $item = $this->storedItem($node, $this->fieldName, $delta);
    $this->assertNotNull($item);

    return [
      'view' => $this->rawValue($item, 'sub'),
      'display' => $this->rawValue($item, 'sub__display'),
      'arguments' => $this->rawValue($item, 'sub__arguments'),
      'items' => $this->rawValue($item, 'sub__items'),
    ];
  }

  /**
   * The node path - one extraction pass - keeps the display id.
   */
  public function testSingleExtractionKeepsDisplayId(): void {
    $node = Node::create(['type' => 'page', 'title' => 'Single']);
    $node->get($this->fieldName)->setValue([$this->massagedWidgetValues()]);
    $node->save();

    $stored = $this->storedValues($node);
    $this->assertSame('view_one', $stored['view']);
    $this->assertSame('page_1', $stored['display']);
  }

  /**
   * A second extraction pass on the same item must keep the display id.
   *
   * Paragraphs keeps ONE paragraph entity object in widget state and runs
   * extractFormValues() against it more than once per request (validate, then
   * submit), so the field item receives setValue() twice.
   */
  public function testRepeatedExtractionKeepsDisplayId(): void {
    $node = Node::create(['type' => 'page', 'title' => 'Repeated']);
    $items = $node->get($this->fieldName);

    // Pass 1, then a read of the item - which is what instantiates the
    // property objects. In a real request that read happens during validation.
    $items->setValue([$this->massagedWidgetValues()]);
    $this->assertNoViolations($node, 'after the first pass');
    $this->assertSame('page_1', $this->rawValue($this->itemAt($items), 'sub__display'), 'Display id set on first pass.');

    // Pass 2: the same values are extracted onto the same item object again.
    $items->setValue([$this->massagedWidgetValues()]);
    $this->assertSame('page_1', $this->rawValue($this->itemAt($items), 'sub__display'), 'Display id survives second pass.');

    $node->save();

    $stored = $this->storedValues($node);
    $this->assertSame('view_one', $stored['view']);
    $this->assertSame('page_1', $stored['display']);
    $this->assertSame('a/b', $stored['arguments']);
    $this->assertSame(7, (int) $stored['items']);
  }

  /**
   * A repeated programmatic setValue() must keep the display id.
   *
   * This is the JSON:API / REST / migrate / custom-code shape: the nested
   * value array with no widget promotion involved.
   */
  public function testProgrammaticNestedValueSurvivesRepeatedSetValue(): void {
    $values = [
      'sub' => [
        'target_id' => 'view_one',
        'display_id' => 'page_1',
        'arguments' => 'a/b',
        'items_to_display' => '7',
      ],
    ];

    $node = Node::create(['type' => 'page', 'title' => 'Programmatic']);
    $items = $node->get($this->fieldName);

    $items->setValue([$values]);
    $this->assertNoViolations($node, 'after the first pass');
    $this->assertSame('page_1', $this->rawValue($this->itemAt($items), 'sub__display'), 'Set on first pass.');

    $items->setValue([$values]);
    $this->assertSame('page_1', $this->rawValue($this->itemAt($items), 'sub__display'), 'Survives second pass.');

    $node->save();
    $this->assertSame('page_1', $this->storedValues($node)['display']);
  }

  /**
   * Every delta of a multi-value custom field survives a repeated pass.
   *
   * ItemList::setValue() reuses the existing item object per delta, so each
   * delta hits the same re-sync that delta 0 does.
   */
  public function testMultipleDeltasSurviveRepeatedExtraction(): void {
    $first = $this->massagedWidgetValues();
    // Any two distinct display ids will do here; massageFormValue() does not
    // validate them against allowed_views, and neither does storage.
    $second = $this->massagedWidgetValues([
      'target_id' => 'view_one',
      'display_id' => 'default',
      'view_options' => ['arguments' => 'c/d', 'items_to_display' => '3'],
    ]);

    $node = Node::create(['type' => 'page', 'title' => 'Two deltas']);
    $items = $node->get($this->fieldName);

    $items->setValue([$first, $second]);
    $this->assertNoViolations($node, 'after the first pass');
    $items->setValue([$first, $second]);

    $this->assertSame('page_1', $this->rawValue($this->itemAt($items), 'sub__display'));
    $this->assertSame('default', $this->rawValue($this->itemAt($items, 1), 'sub__display'));

    $node->save();
    $this->assertSame('page_1', $this->storedValues($node)['display']);
    $this->assertSame('default', $this->storedValues($node, 1)['display']);
  }

  /**
   * Sibling writes via $item->set() need the property object to exist.
   *
   * FieldItemBase::writePropertyValue() calls the property object's setValue()
   * only when that object has already been instantiated; otherwise it stores
   * the raw value and the data type never runs. So on a fresh item $item->set()
   * does NOT produce the siblings. VERIFIED identical with CustomItem's
   * setValue() override disabled - this is pre-existing behavior, documented
   * here so a future change to the side-effect writes is not judged against a
   * guarantee that never held. If a future change makes the fresh-item case
   * write the siblings, that is an improvement - update the assertion rather
   * than reading it as a regression.
   */
  public function testItemSetWritesSiblingsOnlyWhenPropertyExists(): void {
    $value = [
      'target_id' => 'view_one',
      'display_id' => 'page_1',
      'arguments' => 'a/b',
      'items_to_display' => '7',
    ];

    $fresh = Node::create(['type' => 'page', 'title' => 'Item set fresh'])
      ->get($this->fieldName)->appendItem([]);
    $fresh->set('sub', $value);
    $this->assertNull($this->rawValue($fresh, 'sub__display'), 'Fresh item: data type never ran.');

    $warm = Node::create(['type' => 'page', 'title' => 'Item set warm'])
      ->get($this->fieldName)->appendItem([]);
    // Instantiate the property object first, as reading or validating does.
    $warm->get('sub');
    $warm->set('sub', $value);
    $this->assertSame('page_1', $this->rawValue($warm, 'sub__display'));
    $this->assertSame('a/b', $this->rawValue($warm, 'sub__arguments'));
    $this->assertSame(7, $this->rawValue($warm, 'sub__items'));
  }

  /**
   * Setting the property object directly still writes the siblings.
   *
   * The other BC path that bypasses CustomItem::setValue().
   */
  public function testPropertySetValueWritesSiblings(): void {
    $node = Node::create(['type' => 'page', 'title' => 'Property set']);
    $item = $node->get($this->fieldName)->appendItem([]);
    $item->get('sub')->setValue([
      'target_id' => 'view_one',
      'display_id' => 'page_1',
      'arguments' => 'a/b',
      'items_to_display' => '7',
    ]);

    $this->assertSame('page_1', $this->rawValue($item, 'sub__display'));
    $this->assertSame('a/b', $this->rawValue($item, 'sub__arguments'));
    $this->assertSame(7, $this->rawValue($item, 'sub__items'));
  }

  /**
   * The node path stores exactly the same values as before the fix.
   *
   * The expected values were MEASURED against unpatched 5.0.2, not invented,
   * so this locks node-path storage against drift.
   *
   * @param array<string, mixed> $submitted
   *   Raw submitted subfield values.
   * @param array<string, mixed> $expected
   *   The expected reloaded view/display/arguments/items values.
   *
   * @dataProvider providerNodePathStoredValues
   */
  #[DataProvider('providerNodePathStoredValues')]
  public function testNodePathStoredValues(array $submitted, array $expected): void {
    $node = Node::create(['type' => 'page', 'title' => 'Matrix ' . md5(serialize($submitted))]);
    $node->get($this->fieldName)->setValue([$this->massagedWidgetValues($submitted)]);
    $node->save();

    $this->assertSame($expected, $this->storedValues($node));
  }

  /**
   * Data provider for testNodePathStoredValues().
   *
   * @return array<string, array{array<string, mixed>, array<string, mixed>}>
   *   Test cases.
   */
  public static function providerNodePathStoredValues(): array {
    $case = static function (mixed $display, mixed $items, bool $display_set = TRUE, bool $items_set = TRUE, mixed $arguments = 'a/b', bool $arguments_set = TRUE): array {
      $submitted = ['target_id' => 'view_one', 'view_options' => []];
      if ($arguments_set) {
        $submitted['view_options']['arguments'] = $arguments;
      }
      if ($display_set) {
        $submitted['display_id'] = $display;
      }
      if ($items_set) {
        $submitted['view_options']['items_to_display'] = $items;
      }
      return $submitted;
    };

    $expect = static function (mixed $display, mixed $arguments, mixed $items): array {
      return [
        'view' => 'view_one',
        'display' => $display,
        'arguments' => $arguments,
        'items' => $items,
      ];
    };

    return [
      'display missing' => [
        $case(NULL, '5', FALSE),
        $expect(NULL, 'a/b', '5'),
      ],
      'display NULL' => [
        $case(NULL, '5'),
        $expect(NULL, 'a/b', '5'),
      ],
      'display empty string' => [
        $case('', '5'),
        $expect(NULL, 'a/b', '5'),
      ],
      'display valid' => [
        $case('page_1', '5'),
        $expect('page_1', 'a/b', '5'),
      ],
      'items missing' => [
        $case('page_1', NULL, TRUE, FALSE),
        $expect('page_1', 'a/b', NULL),
      ],
      'items NULL' => [
        $case('page_1', NULL),
        $expect('page_1', 'a/b', NULL),
      ],
      'items empty string' => [
        $case('page_1', ''),
        $expect('page_1', 'a/b', NULL),
      ],
      'items string zero' => [
        $case('page_1', '0'),
        $expect('page_1', 'a/b', NULL),
      ],
      'items int zero' => [
        $case('page_1', 0),
        $expect('page_1', 'a/b', NULL),
      ],
      'items string five' => [
        $case('page_1', '5'),
        $expect('page_1', 'a/b', '5'),
      ],
      'items int five' => [
        $case('page_1', 5),
        $expect('page_1', 'a/b', '5'),
      ],
      // The empty-arguments cases are the ''-versus-NULL risk: the promotion
      // in CustomWidgetBase::massageFormValues() already writes '' here today.
      'arguments missing' => [
        $case('page_1', '5', TRUE, TRUE, NULL, FALSE),
        $expect('page_1', NULL, '5'),
      ],
      'arguments empty string' => [
        $case('page_1', '5', TRUE, TRUE, ''),
        $expect('page_1', '', '5'),
      ],
    ];
  }

  /**
   * The type plugin maps a nested value onto its extra property names.
   *
   * Mirrors the input cases of ::providerNodePathStoredValues() at the level
   * of the mapping itself, so a change in either is visible in both.
   *
   * @param array<string, mixed> $value
   *   The nested subfield value.
   * @param array<string, mixed> $expected
   *   The expected extra property values.
   *
   * @dataProvider providerViewfieldMapping
   */
  #[DataProvider('providerViewfieldMapping')]
  public function testViewfieldExtraPropertyMapping(array $value, array $expected): void {
    $this->assertSame(
      $expected,
      ViewfieldType::extractExtraPropertyValues(['name' => 'viewfield_test'], $value)
    );
  }

  /**
   * Data provider for testViewfieldExtraPropertyMapping().
   *
   * @return array<string, array{array<string, mixed>, array<string, mixed>}>
   *   Test cases.
   */
  public static function providerViewfieldMapping(): array {
    $base = ['target_id' => 'view_one'];

    return [
      'display missing' => [
        $base + ['arguments' => 'a/b', 'items_to_display' => '5'],
        ['viewfield_test__arguments' => 'a/b', 'viewfield_test__items' => 5],
      ],
      'display NULL' => [
        $base + ['display_id' => NULL, 'arguments' => 'a/b'],
        ['viewfield_test__arguments' => 'a/b'],
      ],
      'display empty string' => [
        $base + ['display_id' => '', 'arguments' => 'a/b'],
        ['viewfield_test__arguments' => 'a/b'],
      ],
      'display valid' => [
        $base + ['display_id' => 'page_1'],
        ['viewfield_test__display' => 'page_1'],
      ],
      'items missing' => [
        $base + ['display_id' => 'page_1'],
        ['viewfield_test__display' => 'page_1'],
      ],
      'items NULL' => [
        $base + ['display_id' => 'page_1', 'items_to_display' => NULL],
        ['viewfield_test__display' => 'page_1'],
      ],
      'items empty string' => [
        $base + ['display_id' => 'page_1', 'items_to_display' => ''],
        ['viewfield_test__display' => 'page_1'],
      ],
      'items string zero' => [
        $base + ['display_id' => 'page_1', 'items_to_display' => '0'],
        ['viewfield_test__display' => 'page_1'],
      ],
      'items int zero' => [
        $base + ['display_id' => 'page_1', 'items_to_display' => 0],
        ['viewfield_test__display' => 'page_1'],
      ],
      'items string five' => [
        $base + ['display_id' => 'page_1', 'items_to_display' => '5'],
        ['viewfield_test__display' => 'page_1', 'viewfield_test__items' => 5],
      ],
      'items int five' => [
        $base + ['display_id' => 'page_1', 'items_to_display' => 5],
        ['viewfield_test__display' => 'page_1', 'viewfield_test__items' => 5],
      ],
      'arguments empty string' => [
        $base + ['display_id' => 'page_1', 'arguments' => ''],
        ['viewfield_test__display' => 'page_1'],
      ],
      'not an array' => [
        [],
        [],
      ],
    ];
  }

  /**
   * The columns: the view id plus three siblings, with no index.
   */
  public function testSchema(): void {
    $this->assertSame([
      'sub' => [
        'type' => 'varchar_ascii',
        'description' => 'The ID of the view',
        'length' => 255,
      ],
      'sub__display' => [
        'description' => 'The ID of the view display.',
        'type' => 'varchar_ascii',
        'length' => 255,
      ],
      'sub__arguments' => [
        'description' => 'Arguments to be passed to the display.',
        'type' => 'varchar',
        'length' => 255,
      ],
      'sub__items' => [
        'description' => 'Items to display.',
        'type' => 'int',
        'size' => 'small',
        'unsigned' => TRUE,
      ],
    ], ViewfieldType::schema(['name' => 'sub', 'target_type' => 'view']));

    $storage = FieldStorageConfig::loadByName('node', $this->fieldName);
    $this->assertNotNull($storage);
    $this->assertSame([], $storage->getSchema()['indexes']);
    $this->assertContains('views', $storage->getDependencies()['module']);
  }

  /**
   * The property definitions: the view id, the computed view, three siblings.
   */
  public function testPropertyDefinitions(): void {
    $properties = ViewfieldType::propertyDefinitions(['name' => 'sub', 'target_type' => 'view']);

    $this->assertSame(['sub', 'sub__entity', 'sub__display', 'sub__arguments', 'sub__items'], array_keys($properties));

    $this->assertSame('custom_field_viewfield', $properties['sub']->getDataType());
    $this->assertSame(['target_type' => 'view', 'field_type' => 'viewfield'], $properties['sub']->getSettings());
    $this->assertFalse($properties['sub']->isRequired());
    $this->assertFalse($properties['sub']->isInternal());

    $entity = $properties['sub__entity'];
    $this->assertSame('entity_reference', $entity->getDataType());
    $this->assertTrue($entity->isComputed());
    $this->assertTrue($entity->isInternal());
    // Writable in its definition, although writing to it does nothing; see
    // ::testAssigningTheComputedViewIsIgnored().
    $this->assertFalse($entity->isReadOnly());
    $this->assertSame(
      'Drupal\custom_field\Plugin\CustomField\EntityReferenceComputed',
      ltrim((string) $entity->getClass(), '\\'),
    );
    $this->assertSame(['target_id' => 'sub', 'target_type' => 'view'], $entity->getSettings());

    foreach (['sub__display' => 'string', 'sub__arguments' => 'string', 'sub__items' => 'integer'] as $name => $data_type) {
      $this->assertSame($data_type, $properties[$name]->getDataType(), $name);
      $this->assertFalse($properties[$name]->isInternal(), $name);
      $this->assertFalse($properties[$name]->isComputed(), $name);
    }
  }

  /**
   * The extra property names are exactly the three stored siblings.
   *
   * The computed view has no column, so it is not one of them.
   */
  public function testExtraPropertyNamesAreExact(): void {
    $this->assertSame(
      ['sub__display', 'sub__arguments', 'sub__items'],
      ViewfieldType::getExtraPropertyNames(['name' => 'sub', 'target_type' => 'view']),
    );
  }

  /**
   * The declared default field settings.
   *
   * The type's own three keys, the inherited 'handler_settings', and the base
   * set. There is no 'handler' key, here or in the parent.
   */
  public function testDefaultFieldSettings(): void {
    $this->assertSame([
      'force_default' => 0,
      'allowed_views' => [],
      'token_browser' => [
        'recursion_limit' => 3,
        'global_types' => FALSE,
      ],
      'handler_settings' => [],
      'label' => '',
      'required' => FALSE,
      'translatable' => FALSE,
      'description' => '',
      'description_display' => 'after',
      'check_empty' => FALSE,
    ], ViewfieldType::defaultFieldSettings());
  }

  /**
   * CONTRACT: the type's only declared constraint is on the computed property.
   *
   * The getConstraints() method is empty and the plugin attribute declares no
   * 'constraints' key; the one constraint is the inherited EntityType on
   * 'sub__entity'.
   */
  public function testConstraintsLiveOnThePropertyNotThePlugin(): void {
    $instance = $this->typeInstance();
    $this->assertSame([], $instance->getConstraints());
    $definition = $instance->getPluginDefinition();
    $this->assertIsArray($definition);
    $this->assertSame([], $definition['constraints'] ?? []);

    $properties = ViewfieldType::propertyDefinitions(['name' => 'sub', 'target_type' => 'view']);
    foreach (['sub', 'sub__display', 'sub__arguments', 'sub__items'] as $name) {
      $this->assertSame([], $properties[$name]->toArray()['constraints'] ?? [], $name);
    }
    $this->assertSame(['EntityType' => 'view'], $properties['sub__entity']->toArray()['constraints'] ?? []);
  }

  /**
   * CHARACTERIZATION: the EntityType constraint can never be violated.
   *
   * Every way of putting an entity of another type into the computed
   * property is a no-op: the magic setter and the property-level setValue()
   * leave the stored id alone, and the computed property keeps loading by
   * that id from the view storage. So the constraint has nothing to reject,
   * and this test is the proof behind the declaration in
   * ::unreachableConstraints().
   */
  public function testEntityTypeConstraintCannotBeTriggered(): void {
    $user = User::create(['name' => 'not a view']);
    $user->save();

    $node = Node::create(['type' => 'page', 'title' => 'entity type']);
    $node->set($this->fieldName, [['sub' => 'view_one', 'sub__display' => 'page_1']]);
    $item = $this->itemAt($node->get($this->fieldName));

    $this->setRawValue($item, 'sub__entity', $user);
    $this->assertSame('view_one', $this->rawValue($item, 'sub'));
    $this->assertSame('view_one', $this->referencedView($item)->id());
    $this->assertNoViolations($node, 'after the magic setter');

    $item->get('sub__entity')->setValue($user);
    $this->assertSame('view_one', $this->referencedView($item)->id());
    $this->assertCount(0, $item->get('sub__entity')->validate());
    $this->assertNoViolations($node, 'after the property-level setValue()');
  }

  /**
   * CONTRACT: the stored id resolves to the referenced view.
   */
  public function testReferencedViewResolvesThroughTheComputedProperty(): void {
    $node = $this->createHost('view_two', 'embed_1');

    $item = $this->storedItem($node, $this->fieldName);
    $this->assertNotNull($item);
    $this->assertSame('view_two', $item->get('sub')->getValue());
    $view = $this->referencedView($item);
    $this->assertSame('view_two', $view->id());
    $this->assertSame('View view_two', $view->label());

    // The data type's own accessors agree.
    $this->assertSame('view_two', $this->mainProperty($item)->getEntity()?->id());
    $this->assertSame('view_two', $this->mainProperty($item)->getCastedValue());
  }

  /**
   * CONTRACT: a changed view is followed when it was not read before.
   *
   * Retargeting a saved item to another view and display is what the retired
   * test plugin's second case exercised in the old shared matrix. The stored
   * value always follows; the computed view follows only if nothing read it
   * before the change, see ::testCachedViewGoesStaleAfterTheTargetChanges().
   */
  public function testChangingTheViewBeforeTheFirstReadIsFollowed(): void {
    $node = $this->createHost('view_one', 'page_1');

    $item = $this->itemAt($this->reloadedNode($node)->get($this->fieldName));
    $item->get('sub')->setValue('view_two');
    $this->assertSame('view_two', $this->referencedView($item)->id());

    // Saved and reloaded, the new view and display are what is stored.
    $reloaded = $this->reloadedNode($node);
    $reloaded->set($this->fieldName, [['sub' => 'view_two', 'sub__display' => 'default', 'sub__items' => 5]]);
    $reloaded->save();
    $this->assertSame(
      ['view' => 'view_two', 'display' => 'default', 'arguments' => NULL, 'items' => '5'],
      $this->storedValues($node),
    );
    $stored = $this->storedItem($node, $this->fieldName);
    $this->assertNotNull($stored);
    $this->assertSame('view_two', $this->referencedView($stored)->id());
  }

  /**
   * CHARACTERIZATION: once read, the computed view ignores id changes.
   *
   * The computed property loads the view once and never resets it. After one
   * read, changing the id through ANY write path leaves it returning the old
   * view, while the stored id and the data type's own getEntity() follow the
   * change. A future fix changes these expectations.
   *
   * @param string $path
   *   The write path under test.
   *
   * @dataProvider providerWritePaths
   */
  #[DataProvider('providerWritePaths')]
  public function testCachedViewGoesStaleAfterTheTargetChanges(string $path): void {
    $node = $this->createHost('view_one', 'page_1');
    $host = $this->reloadedNode($node);
    $item = $this->itemAt($host->get($this->fieldName));
    $this->assertSame('view_one', $this->referencedView($item)->id());

    match ($path) {
      'magic setter' => $this->setRawValue($item, 'sub', 'view_two'),
      'property setValue' => $item->get('sub')->setValue('view_two'),
      'item setValue' => $item->setValue(['sub' => 'view_two', 'sub__display' => 'default']),
      'list set' => $host->set($this->fieldName, [['sub' => 'view_two', 'sub__display' => 'default']]),
      default => $this->fail(sprintf('Unknown write path "%s".', $path)),
    };
    $item = $this->itemAt($host->get($this->fieldName));

    $this->assertSame('view_two', $item->get('sub')->getValue());
    $this->assertSame('view_two', $this->mainProperty($item)->getEntity()?->id());
    $this->assertSame('view_one', $this->referencedView($item)->id(), 'The computed view is still the old one.');
  }

  /**
   * Data provider for ::testCachedViewGoesStaleAfterTheTargetChanges().
   *
   * @return array<string, array{string}>
   *   Test cases.
   */
  public static function providerWritePaths(): array {
    return [
      'the magic setter' => ['magic setter'],
      'the property-level setValue()' => ['property setValue'],
      'the item setValue()' => ['item setValue'],
      'the list set()' => ['list set'],
    ];
  }

  /**
   * CHARACTERIZATION: assigning a view to the computed property is ignored.
   *
   * The assignment changes neither the stored id nor what the computed
   * property returns, and the original view is what a save stores. A future
   * fix changes these expectations.
   */
  public function testAssigningTheComputedViewIsIgnored(): void {
    $node = $this->createHost('view_one', 'page_1');
    $host = $this->reloadedNode($node);
    $item = $this->itemAt($host->get($this->fieldName));

    $this->setRawValue($item, 'sub__entity', View::load('view_two'));
    $this->assertSame('view_one', $item->get('sub')->getValue());
    $this->assertSame('view_one', $this->referencedView($item)->id());

    $host->save();
    $this->assertSame('view_one', $this->storedValues($node)['view']);
  }

  /**
   * CHARACTERIZATION: a new view in the value is saved on first READ.
   *
   * The assignment itself stores the raw ['entity' => $view, ...] array,
   * which the magic getter returns as-is. The view is saved the first time
   * the property object is instantiated, which a plain get() read does,
   * even if the host is never saved: reading the field creates a configuration
   * entity. A future fix changes these expectations.
   */
  public function testNewViewIsSavedWhenThePropertyIsFirstRead(): void {
    $new = $this->newView('view_created_by_a_read');
    $node = Node::create(['type' => 'page', 'title' => 'never saved']);
    $node->set($this->fieldName, [['sub' => ['entity' => $new, 'display_id' => 'default']]]);
    $item = $this->itemAt($node->get($this->fieldName));

    $this->assertTrue($new->isNew(), 'Assigning does not save the view.');
    $raw = $this->rawValue($item, 'sub');
    $this->assertIsArray($raw);
    $this->assertSame($new, $raw['entity']);

    $this->assertSame('view_created_by_a_read', $item->get('sub')->getValue());
    $this->assertFalse($new->isNew(), 'Reading the property saved the view.');
    $this->assertNotNull(View::load('view_created_by_a_read'));
    $this->assertSame('default', $item->get('sub__display')->getValue());
    $this->assertTrue($node->isNew(), 'The host was never saved.');
  }

  /**
   * CHARACTERIZATION: a view that fails to save becomes a silent NULL.
   *
   * The storage exception is caught inside the data type, the value becomes
   * NULL, and nothing reports it. A new view reusing an existing view's id is
   * the failing save; the existing view is left untouched.
   */
  public function testFailedSaveOfNewViewIsSwallowed(): void {
    $node = Node::create(['type' => 'page', 'title' => 'failing view host']);
    $node->set($this->fieldName, [['sub' => 'view_one']]);
    $property = $this->mainProperty($this->itemAt($node->get($this->fieldName)));

    $duplicate = $this->newView('view_one');
    $property->setValue(['entity' => $duplicate]);

    $this->assertNull($property->getValue());
    $this->assertTrue($duplicate->isNew());
    $this->assertSame('View view_one', View::load('view_one')?->label());
  }

  /**
   * CHARACTERIZATION: a nonexistent view id is stored as given.
   *
   * The computed view and the data type's getEntity() resolve to NULL.
   */
  public function testMissingViewIdIsStored(): void {
    $node = Node::create(['type' => 'page', 'title' => 'missing view']);
    $node->set($this->fieldName, [['sub' => 'no_such_view']]);
    $node->save();

    $item = $this->storedItem($node, $this->fieldName);
    $this->assertNotNull($item);
    $this->assertSame('no_such_view', $item->get('sub')->getValue());
    $this->assertNull($this->rawValue($item, 'sub__entity'));
    $this->assertNull($this->mainProperty($item)->getEntity());
  }

  /**
   * CONTRACT: siblings without a view are cleared when the item is kept.
   *
   * With no view id, the field type's preSave() sets all three siblings to
   * NULL. That is only observable when another column keeps the item, which
   * the old shared matrix provided with its other sub-fields; here a second,
   * populated column does. The retired test plugin's third case.
   */
  public function testEmptyViewClearsEverySibling(): void {
    $field_name = $this->createTwoColumnField('wipe', FALSE);
    $node = Node::create(['type' => 'page', 'title' => 'sibling wipe']);
    $node->set($field_name, [
      [
        'sub__display' => 'page_1',
        'sub__arguments' => 'a/b',
        'sub__items' => 3,
        'keep' => 'populated',
      ],
    ]);
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item, 'The populated sibling column kept the item.');
    foreach (['sub', 'sub__display', 'sub__arguments', 'sub__items'] as $property) {
      $this->assertNull($item->get($property)->getValue(), $property);
    }
    $this->assertSame('populated', $item->get('keep')->getValue());
  }

  /**
   * CONTRACT: siblings without a view, on a sole column, drop the item.
   *
   * The item has no populated column of its own once the siblings are
   * cleared, so it is not stored at all.
   */
  public function testSiblingsWithoutViewDropTheSoleItem(): void {
    $node = Node::create(['type' => 'page', 'title' => 'sole column']);
    $node->set($this->fieldName, [['sub__display' => 'page_1', 'sub__arguments' => 'a/b', 'sub__items' => 3]]);
    $node->save();

    $this->assertNull($this->storedItem($node, $this->fieldName));
  }

  /**
   * The check_empty setting on the view id column, with a populated sibling.
   *
   * Every row writes the 'page_1' display. The stored view id is a string
   * column, so an empty string is kept as ''. One row is worth noting: a
   * view id of '0' keeps the item and is stored, but the field type's
   * preSave() treats it as empty and clears the display.
   *
   * @param bool $check_empty
   *   The check_empty setting.
   * @param string|null $value
   *   The view id to assign.
   * @param array{string|null, string|null}|null $stored
   *   The stored view id and display, or NULL when the item is dropped.
   *
   * @dataProvider providerCheckEmpty
   */
  #[DataProvider('providerCheckEmpty')]
  public function testCheckEmpty(bool $check_empty, ?string $value, ?array $stored): void {
    $field_name = $this->createTwoColumnField('ce_' . substr(md5(serialize([$check_empty, $value])), 0, 8), $check_empty);
    $node = Node::create(['type' => 'page', 'title' => 'check_empty ' . $field_name]);
    $node->set($field_name, [['sub' => $value, 'sub__display' => 'page_1', 'keep' => 'populated']]);
    $node->save();

    $item = $this->storedItem($node, $field_name);
    if ($stored === NULL) {
      $this->assertNull($item, 'The item was dropped as empty.');
      return;
    }
    $this->assertNotNull($item, 'The item was kept.');
    $this->assertSame($stored, [$item->get('sub')->getValue(), $item->get('sub__display')->getValue()]);
  }

  /**
   * Data provider for ::testCheckEmpty().
   *
   * @return array<string, array{bool, string|null, array{string|null, string|null}|null}>
   *   Test cases.
   */
  public static function providerCheckEmpty(): array {
    return [
      'check_empty on, NULL' => [TRUE, NULL, NULL],
      'check_empty on, empty string' => [TRUE, '', NULL],
      'check_empty on, string zero' => [TRUE, '0', ['0', NULL]],
      'check_empty on, a view id' => [TRUE, 'view_one', ['view_one', 'page_1']],
      'check_empty off, NULL' => [FALSE, NULL, [NULL, NULL]],
      'check_empty off, empty string' => [FALSE, '', ['', NULL]],
      'check_empty off, string zero' => [FALSE, '0', ['0', NULL]],
      'check_empty off, a view id' => [FALSE, 'view_one', ['view_one', 'page_1']],
    ];
  }

  /**
   * With the type's own settings, there is no sample value.
   *
   * The inherited entity reference sampling returns nothing while
   * 'handler_settings' is empty, which it always is for this type, so the
   * inherited ::testSampleValueGeneration() saves an empty item.
   */
  public function testSampleValueIsNullForTheDefaultSettings(): void {
    $node = Node::create(['type' => 'page', 'title' => 'sample default']);
    $node->get($this->fieldName)->generateSampleItems();

    $this->assertSame([['sub' => NULL]], $node->get($this->fieldName)->getValue());
  }

  /**
   * CHARACTERIZATION: sample generation fails with non-empty handler settings.
   *
   * The inherited entity reference sampling then builds selection handler
   * options from a 'handler' setting this type never has, which warns; the
   * selection plugin manager then passes that NULL to str_contains(), which
   * PHP reports as deprecated, and is left with no handler group, which
   * throws. Both notices are captured and asserted, because the error is what
   * this test pins. Only hand-written configuration reaches this; the
   * settings form never writes handler settings for a viewfield. A future fix
   * changes these expectations.
   */
  public function testSampleGenerationFailsWithHandlerSettings(): void {
    $field_name = $this->createCaseField([
      'settings' => ['handler_settings' => ['target_bundles' => ['page' => 'page']]],
    ], 'sample_handler');
    $node = Node::create(['type' => 'page', 'title' => 'sample handler']);

    $warnings = [];
    set_error_handler(static function (int $errno, string $message) use (&$warnings): bool {
      $warnings[] = $message;
      return TRUE;
    }, E_WARNING | E_DEPRECATED);
    try {
      $node->get($field_name)->generateSampleItems();
      $this->fail('Sample generation with handler settings did not throw.');
    }
    catch (\TypeError $e) {
      $this->assertSame('uasort(): Argument #1 ($array) must be of type array, null given', $e->getMessage());
    }
    finally {
      restore_error_handler();
    }
    $this->assertContains('Undefined array key "handler"', $warnings);
    $this->assertContains('str_contains(): Passing null to parameter #1 ($haystack) of type string is deprecated', $warnings);
  }

  /**
   * CHARACTERIZATION: an over-long argument string raises no violation.
   *
   * The arguments column is 255 characters wide and nothing compares a value
   * with it, so a 256-character string validates cleanly. Pinned at the
   * validation level only: what the database then does differs per backend.
   * A future length constraint changes this expectation.
   */
  public function testOverLengthArgumentsProduceNoViolation(): void {
    $node = Node::create(['type' => 'page', 'title' => 'long arguments']);
    $node->set($this->fieldName, [
      [
        'sub' => 'view_one',
        'sub__display' => 'page_1',
        'sub__arguments' => str_repeat('a', 256),
      ],
    ]);

    $this->assertNoViolations($node);
  }

  /**
   * CHARACTERIZATION: an item count the column cannot hold raises no violation.
   *
   * The column is a small unsigned integer and the property has no range, so
   * a negative count and one above the column's capacity both validate
   * cleanly. The widget's own limits apply to the form only. Pinned at the
   * validation level only: the column's capacity differs per backend. A
   * future range constraint changes these expectations.
   *
   * @param int $items
   *   The item count to write.
   *
   * @dataProvider providerOutOfRangeItemCounts
   */
  #[DataProvider('providerOutOfRangeItemCounts')]
  public function testOutOfRangeItemCountProducesNoViolation(int $items): void {
    $node = Node::create(['type' => 'page', 'title' => 'items ' . $items]);
    $node->set($this->fieldName, [['sub' => 'view_one', 'sub__display' => 'page_1', 'sub__items' => $items]]);

    $this->assertNoViolations($node);
  }

  /**
   * Data provider for ::testOutOfRangeItemCountProducesNoViolation().
   *
   * @return array<string, array{int}>
   *   Test cases.
   */
  public static function providerOutOfRangeItemCounts(): array {
    return [
      'a negative count' => [-1],
      'a count above the column capacity' => [70000],
    ];
  }

  /**
   * The field depends on every allowed view with at least one display.
   *
   * With no allowed views ("all views"), or with every display of a view
   * switched off, there is no view dependency at all.
   */
  public function testCalculateDependencies(): void {
    $dependencies = $this->fieldDependencies($this->fieldName);
    $this->assertContains('views.view.view_one', $dependencies);
    $this->assertContains('views.view.view_two', $dependencies);

    $all_views = $this->createCaseField(['settings' => ['allowed_views' => []]], 'deps_all');
    $this->assertSame([], preg_grep('/^views\.view\./', $this->fieldDependencies($all_views)));

    $no_display = $this->createCaseField([
      'settings' => ['allowed_views' => ['view_two' => ['default' => 0, 'embed_1' => 0]]],
    ], 'deps_off');
    $this->assertSame([], preg_grep('/^views\.view\./', $this->fieldDependencies($no_display)));
  }

  /**
   * Deleting an allowed view keeps the field and prunes the view.
   *
   * The field and its storage survive, and the view is removed from the
   * allowed views, which then become empty (all views allowed). A field
   * allowing all views has no dependency on the view and is untouched,
   * keeping the deleted view's id.
   */
  public function testDeletingAnAllowedViewKeepsTheFieldAndPrunesTheView(): void {
    $this->createView('view_doomed', []);
    $allowing = $this->createCaseField(['settings' => ['allowed_views' => ['view_doomed' => ['default' => 'default']]]], 'doomed');
    $all_views = $this->createCaseField(['settings' => ['allowed_views' => []]], 'doomed_all');
    $node = Node::create(['type' => 'page', 'title' => 'view delete host']);
    $node->set($allowing, [['sub' => 'view_doomed', 'sub__display' => 'default']]);
    $node->set($all_views, [['sub' => 'view_doomed', 'sub__display' => 'default']]);
    $node->save();

    View::load('view_doomed')?->delete();

    $allowing_field = FieldConfig::loadByName('node', 'page', $allowing);
    $this->assertNotNull($allowing_field);
    $this->assertNotNull(FieldStorageConfig::loadByName('node', $allowing));
    $host = $this->reloadedNode($node);
    $this->assertTrue($host->hasField($allowing));
    $this->assertSame([], $allowing_field->getSetting('field_settings')['sub']['allowed_views']);

    $this->assertNotNull(FieldConfig::loadByName('node', 'page', $all_views));
    $item = $this->itemAt($host->get($all_views));
    $this->assertSame('view_doomed', $item->get('sub')->getValue());
    $this->assertNull($this->rawValue($item, 'sub__entity'));
  }

  /**
   * Dependency removal prunes the view from the sub-field's settings.
   *
   * The deleted view is removed from 'allowed_views', the field type reports
   * the field as changed, the pruned settings are not nested under any other
   * key, and the field no longer depends on the view.
   */
  public function testDependencyRemovalPrunesTheViewFromTheSubFieldSettings(): void {
    $field = FieldConfig::loadByName('node', 'page', $this->fieldName);
    $this->assertNotNull($field);
    $view = View::load('view_two');
    $this->assertInstanceOf(ViewEntityInterface::class, $view);

    $changed = CustomItem::onDependencyRemoval($field, [
      'config' => [$view->getConfigDependencyName() => $view],
    ]);

    $this->assertTrue($changed);
    $settings = $field->getSetting('field_settings')['sub'];
    $this->assertSame(['view_one'], array_keys($settings['allowed_views']));
    $this->assertNotContains('views.view.view_two', $field->calculateDependencies()->getDependencies()['config'] ?? []);
  }

  /**
   * Creates and saves a view on node data with a default display.
   *
   * @param string $id
   *   The view id; the label is "View <id>".
   * @param array<string, array<string, mixed>> $displays
   *   Further displays, keyed by display id.
   */
  private function createView(string $id, array $displays): void {
    $this->newView($id, $displays)->save();
  }

  /**
   * Builds an unsaved view on node data with a default display.
   *
   * @param string $id
   *   The view id; the label is "View <id>".
   * @param array<string, array<string, mixed>> $displays
   *   Further displays, keyed by display id.
   */
  private function newView(string $id, array $displays = []): ViewEntityInterface {
    $all = [
      'default' => [
        'display_title' => 'Default',
        'display_plugin' => 'default',
        'display_options' => [],
      ],
    ] + $displays;
    $position = 0;
    foreach ($all as $display_id => &$display) {
      $display += ['id' => $display_id, 'position' => $position++];
    }
    unset($display);

    return View::create([
      'id' => $id,
      'label' => 'View ' . $id,
      'base_table' => 'node_field_data',
      'status' => TRUE,
      'display' => $all,
    ]);
  }

  /**
   * Creates and saves a page host pointing at one view and display.
   */
  private function createHost(string $view_id, string $display_id): Node {
    $node = Node::create(['type' => 'page', 'title' => 'host for ' . $view_id]);
    $node->set($this->fieldName, [['sub' => $view_id, 'sub__display' => $display_id]]);
    $node->save();

    return $node;
  }

  /**
   * Creates a field with a viewfield column and a populated string sibling.
   *
   * @param string $suffix
   *   A suffix keeping the field name unique.
   * @param bool $check_empty
   *   The check_empty setting of the viewfield column.
   *
   * @return string
   *   The field name.
   */
  private function createTwoColumnField(string $suffix, bool $check_empty): string {
    $field_name = 'field_sub_' . $suffix;
    $this->createCustomField(
      $field_name,
      [
        'sub' => $this->defaultColumn('sub'),
        'keep' => ['name' => 'keep', 'type' => 'string'],
      ],
      [
        'sub' => ['check_empty' => $check_empty] + $this->defaultFieldSettings(),
        'keep' => ['label' => 'Populated sibling', 'check_empty' => FALSE],
      ],
    );

    return $field_name;
  }

  /**
   * Returns a field's config dependencies.
   *
   * @return list<string>
   *   The config dependency names.
   */
  private function fieldDependencies(string $field_name): array {
    $field = FieldConfig::loadByName('node', 'page', $field_name);
    $this->assertNotNull($field);

    return array_values($field->getDependencies()['config'] ?? []);
  }

  /**
   * Returns the computed view of an item, asserted to be a view.
   */
  private function referencedView(FieldItemInterface $item): ViewEntityInterface {
    $view = $this->rawValue($item, 'sub__entity');
    $this->assertInstanceOf(ViewEntityInterface::class, $view);

    return $view;
  }

  /**
   * Returns the view id property narrowed to this type's data type.
   *
   * FieldItemInterface::get() is typed to return TypedDataInterface, which
   * declares neither getEntity() nor getCastedValue().
   */
  private function mainProperty(FieldItemInterface $item): CustomFieldViewfield {
    $property = $item->get('sub');
    $this->assertInstanceOf(CustomFieldViewfield::class, $property);

    return $property;
  }

  /**
   * Returns a property's value through the item's magic getter.
   *
   * FieldItemBase::__get() returns the property object's value once that
   * object exists, and the raw stored value until then, so this differs
   * from get()->getValue() until something instantiates the property.
   */
  private function rawValue(FieldItemInterface $item, string $property): mixed {
    return $item->{$property};
  }

  /**
   * Assigns a property through the item's magic setter.
   */
  private function setRawValue(FieldItemInterface $item, string $property, mixed $value): void {
    $item->{$property} = $value;
  }

  /**
   * Returns a delta's item from a list, asserted to exist.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface<\Drupal\Core\Field\FieldItemInterface> $items
   *   The item list.
   * @param int $delta
   *   The delta to read.
   *
   * @return \Drupal\Core\Field\FieldItemInterface
   *   The item at that delta.
   */
  private function itemAt(FieldItemListInterface $items, int $delta = 0): FieldItemInterface {
    $item = $items[$delta];
    $this->assertInstanceOf(FieldItemInterface::class, $item);

    return $item;
  }

  /**
   * Reloads a saved node from storage.
   */
  private function reloadedNode(Node $node): NodeInterface {
    $id = $node->id();
    $this->assertNotNull($id);

    return $this->reloadNode($id);
  }

  /**
   * Builds a configured plugin instance the way production code does.
   */
  private function typeInstance(): CustomFieldTypeInterface {
    $column = $this->defaultColumn($this->columnName());
    $options = $this->typeManager->createOptionsForInstance($this->defaultFieldSettings(), $column);
    $instance = $this->typeManager->createInstance(static::typeId(), $options['configuration']);
    $this->assertInstanceOf(CustomFieldTypeInterface::class, $instance);

    return $instance;
  }

}
