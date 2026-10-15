<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field_viewfield\Kernel;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\views\Entity\View;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that viewfield sibling properties survive repeated setValue() calls.
 *
 * The viewfield data type writes its display/arguments/items values onto
 * SIBLING properties of the field item. Core's Map::setValue() re-syncs every
 * ALREADY INSTANTIATED property object from the incoming values array, so any
 * sibling missing from that array is reset to NULL - which is what happens on
 * the second extraction pass Paragraphs performs, and on any repeated
 * programmatic setValue().
 *
 * @group custom_field
 * @group custom_field_viewfield
 * @RunTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[Group('custom_field_viewfield')]
#[RunTestsInSeparateProcesses]
class ViewfieldItemPropertyResyncTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'file',
    'filter',
    'image',
    'link',
    'node',
    'text',
    'views',
    'custom_field',
    'custom_field_viewfield',
  ];

  /**
   * The custom field config.
   */
  protected FieldConfig $fieldConfig;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig(['field', 'node', 'filter']);

    NodeType::create(['type' => 'page', 'name' => 'Page'])->save();

    View::create([
      'id' => 'view_one',
      'label' => 'View One',
      'base_table' => 'node_field_data',
      'status' => TRUE,
      'display' => [
        'default' => [
          'id' => 'default',
          'display_title' => 'Master',
          'display_plugin' => 'default',
          'position' => 0,
          'display_options' => [],
        ],
        'page_1' => [
          'id' => 'page_1',
          'display_title' => 'Page One',
          'display_plugin' => 'page',
          'position' => 1,
          'display_options' => ['path' => 'view-one-page'],
        ],
      ],
    ])->save();

    $storage = FieldStorageConfig::create([
      'field_name' => 'field_test',
      'entity_type' => 'node',
      'type' => 'custom',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => [
        'columns' => [
          'viewfield_test' => [
            'name' => 'viewfield_test',
            'type' => 'viewfield',
            'target_type' => 'view',
          ],
        ],
      ],
    ]);
    $storage->save();

    $this->fieldConfig = FieldConfig::create([
      'field_storage' => $storage,
      'bundle' => 'page',
      'label' => 'field_test',
      'settings' => [
        'field_settings' => [
          'viewfield_test' => [
            'label' => 'Viewfield test',
            'check_empty' => FALSE,
            'required' => FALSE,
            'description' => '',
            'force_default' => 0,
            'allowed_views' => [
              'view_one' => ['default' => 0, 'page_1' => 'page_1'],
            ],
          ],
        ],
      ],
    ]);
    $this->fieldConfig->save();

    $this->container->get('entity_field.manager')->clearCachedFieldDefinitions();
  }

  /**
   * Runs the REAL viewfield_select widget's massageFormValue().
   *
   * @param mixed $submitted
   *   The raw submitted subfield form values.
   *
   * @return mixed
   *   The massaged subfield value.
   */
  protected function massageSubfieldValue(mixed $submitted): mixed {
    $settings = $this->fieldConfig->getSettings();
    $subfield = $this->container->get('plugin.manager.custom_field_type')
      ->getCustomFieldItems($settings)['viewfield_test'];
    $widget_manager = $this->container->get('plugin.manager.custom_field_widget');
    $widget = $widget_manager->getInstance(
      $widget_manager->createOptionsForInstance('field_test', $subfield, 'viewfield_select', [])
    );
    $this->assertNotNull($widget, 'Failed to create the viewfield_select widget.');

    return $widget->massageFormValue($submitted, $settings['columns']['viewfield_test']);
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
    $values = ['viewfield_test' => $massaged];
    if (is_array($massaged)) {
      foreach ($massaged as $sub_key => $sub_value) {
        $values['viewfield_test__' . $sub_key] = $sub_value;
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
    $item = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadUnchanged($node->id())
      ->get('field_test')[$delta];

    return [
      'view' => $item->viewfield_test,
      'display' => $item->viewfield_test__display,
      'arguments' => $item->viewfield_test__arguments,
      'items' => $item->viewfield_test__items,
    ];
  }

  /**
   * The node path - one extraction pass - keeps the display id.
   */
  public function testSingleExtractionKeepsDisplayId(): void {
    $node = Node::create(['type' => 'page', 'title' => 'Single']);
    $node->get('field_test')->setValue([$this->massagedWidgetValues()]);
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
    $items = $node->get('field_test');

    // Pass 1, then a read of the item - which is what instantiates the
    // property objects. In a real request that read happens during validation.
    $items->setValue([$this->massagedWidgetValues()]);
    $node->validate();
    $this->assertSame('page_1', $items[0]->viewfield_test__display, 'Display id set on first pass.');

    // Pass 2: the same values are extracted onto the same item object again.
    $items->setValue([$this->massagedWidgetValues()]);
    $this->assertSame('page_1', $items[0]->viewfield_test__display, 'Display id survives second pass.');

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
      'viewfield_test' => [
        'target_id' => 'view_one',
        'display_id' => 'page_1',
        'arguments' => 'a/b',
        'items_to_display' => '7',
      ],
    ];

    $node = Node::create(['type' => 'page', 'title' => 'Programmatic']);
    $items = $node->get('field_test');

    $items->setValue([$values]);
    $node->validate();
    $this->assertSame('page_1', $items[0]->viewfield_test__display, 'Set on first pass.');

    $items->setValue([$values]);
    $this->assertSame('page_1', $items[0]->viewfield_test__display, 'Survives second pass.');

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
    $items = $node->get('field_test');

    $items->setValue([$first, $second]);
    $node->validate();
    $items->setValue([$first, $second]);

    $this->assertSame('page_1', $items[0]->viewfield_test__display);
    $this->assertSame('default', $items[1]->viewfield_test__display);

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
      ->get('field_test')->appendItem([]);
    $fresh->set('viewfield_test', $value);
    $this->assertNull($fresh->viewfield_test__display, 'Fresh item: data type never ran.');

    $warm = Node::create(['type' => 'page', 'title' => 'Item set warm'])
      ->get('field_test')->appendItem([]);
    // Instantiate the property object first, as reading or validating does.
    $warm->get('viewfield_test');
    $warm->set('viewfield_test', $value);
    $this->assertSame('page_1', $warm->viewfield_test__display);
    $this->assertSame('a/b', $warm->viewfield_test__arguments);
    $this->assertSame(7, $warm->viewfield_test__items);
  }

  /**
   * Setting the property object directly still writes the siblings.
   *
   * The other BC path that bypasses CustomItem::setValue().
   */
  public function testPropertySetValueWritesSiblings(): void {
    $node = Node::create(['type' => 'page', 'title' => 'Property set']);
    $item = $node->get('field_test')->appendItem([]);
    $item->get('viewfield_test')->setValue([
      'target_id' => 'view_one',
      'display_id' => 'page_1',
      'arguments' => 'a/b',
      'items_to_display' => '7',
    ]);

    $this->assertSame('page_1', $item->viewfield_test__display);
    $this->assertSame('a/b', $item->viewfield_test__arguments);
    $this->assertSame(7, $item->viewfield_test__items);
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
    $node->get('field_test')->setValue([$this->massagedWidgetValues($submitted)]);
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
    $class = $this->container->get('plugin.manager.custom_field_type')
      ->getDefinitions()['viewfield']['class'];

    $this->assertSame(
      $expected,
      $class::extractExtraPropertyValues(['name' => 'viewfield_test'], $value)
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

}
