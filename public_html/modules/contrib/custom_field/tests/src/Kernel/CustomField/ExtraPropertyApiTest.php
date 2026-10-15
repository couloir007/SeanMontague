<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Kernel\CustomField;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\custom_field\Plugin\CustomFieldTypeBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the extra-property contract on subfield type plugins.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class ExtraPropertyApiTest extends KernelTestBase {

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
    'custom_field_extra_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig(['field', 'node', 'filter']);
    NodeType::create(['type' => 'page', 'name' => 'Page'])->save();
  }

  /**
   * Settings permissive enough to build any type's schema.
   *
   * @param string $name
   *   The subfield name.
   * @param string $type
   *   The type plugin id.
   *
   * @return array<string, mixed>
   *   Column settings.
   */
  protected function settingsFor(string $name, string $type): array {
    return [
      'name' => $name,
      'type' => $type,
      'length' => 255,
      'datetime_type' => 'datetime',
      'target_type' => $type === 'viewfield' ? 'view' : 'node',
      'uri_scheme' => 'public',
    ];
  }

  /**
   * Every derived extra property name is a real, non-computed property.
   */
  public function testDerivedNamesMatchPropertyDefinitions(): void {
    $manager = $this->container->get('plugin.manager.custom_field_type');
    $checked = 0;

    foreach ($manager->getDefinitions() as $id => $definition) {
      $class = $definition['class'];
      $settings = $this->settingsFor('sub', $id);
      $names = $class::getExtraPropertyNames($settings);
      if ($names === []) {
        continue;
      }
      $properties = $class::propertyDefinitions($settings);
      $this->assertIsArray($properties, sprintf('%s returns property definitions.', $id));
      foreach ($names as $name) {
        $this->assertArrayHasKey($name, $properties, sprintf('%s declares property %s.', $id, $name));
        $this->assertFalse(
          $properties[$name]->isComputed(),
          sprintf('%s property %s is not computed.', $id, $name)
        );
      }
      $checked++;
    }

    $this->assertGreaterThan(3, $checked, 'Several types declare extra properties.');
  }

  /**
   * The base implementation extracts nothing.
   */
  public function testBaseExtractionDefaultIsEmpty(): void {
    $this->assertSame([], CustomFieldTypeBase::extractExtraPropertyValues(
      ['name' => 'sub'],
      ['anything' => 'here']
    ));

    // A concrete type that does not override it behaves the same. The type has
    // extra columns of its own, so the empty return is the absent override and
    // not the absence of anything to extract.
    $manager = $this->container->get('plugin.manager.custom_field_type');
    $class = $manager->getDefinitions()['extra_test_plain']['class'];
    $this->assertNotSame(
      [],
      $class::getExtraPropertyNames($this->settingsFor('sub', 'extra_test_plain')),
      'The type used here declares extra properties.'
    );
    $this->assertSame([], $class::extractExtraPropertyValues(
      ['name' => 'sub'],
      ['value' => 'main', 'first' => 'A', 'second' => 'B']
    ));
  }

  /**
   * An extracted key the type does not declare is discarded.
   */
  public function testUndeclaredExtraPropertyKeyIsIgnored(): void {
    $this->createField('field_extra', ['ex' => ['name' => 'ex', 'type' => 'extra_test']]);

    $node = Node::create(['type' => 'page', 'title' => 'Extra']);
    $node->get('field_extra')->setValue([
      ['ex' => ['value' => 'main', 'good' => 'kept', 'bogus' => 'dropped']],
    ]);

    $values = $node->get('field_extra')[0]->getValue();
    $this->assertArrayHasKey('ex__good', $values, 'The declared extra property was added.');
    $this->assertSame('kept', $values['ex__good']);
    $this->assertArrayNotHasKey('ex__bogus', $values, 'The undeclared key was discarded.');
  }

  /**
   * Types without an override are left untouched by CustomItem::setValue().
   */
  public function testTypesWithoutOverrideAreUntouched(): void {
    $class = $this->container->get('plugin.manager.custom_field_type')
      ->getDefinitions()['extra_test_plain']['class'];
    foreach (['one', 'two'] as $name) {
      $this->assertNotSame(
        [],
        $class::getExtraPropertyNames($this->settingsFor($name, 'extra_test_plain')),
        'The subfield declares extra properties that an override could fill.'
      );
    }

    $this->createField('field_plain', [
      'one' => ['name' => 'one', 'type' => 'extra_test_plain'],
      'two' => ['name' => 'two', 'type' => 'extra_test_plain'],
    ]);

    $submitted = [
      'one' => ['value' => 'a', 'first' => 'A', 'second' => 'B'],
      'two' => ['value' => 'b', 'first' => 'C', 'second' => 'D'],
    ];
    $node = Node::create(['type' => 'page', 'title' => 'Plain']);
    $node->get('field_plain')->setValue([$submitted]);

    // setValue() added nothing: the values array still holds exactly the two
    // nested subfield keys it was given.
    $values = $node->get('field_plain')[0]->getValue();
    $this->assertSame(['one', 'two'], array_keys($values));
  }

  /**
   * An array-valued subfield survives a repeated setValue() unchanged.
   *
   * Map is the subfield type whose legitimate value IS an array, so it is the
   * one that reaches the nested-value branch of CustomItem::setValue() without
   * declaring an override of its own. It must come back byte-identical, and
   * must never pick up sibling keys belonging to a type that does have one.
   * The payload is deliberately shaped like a viewfield value and
   * custom_field_viewfield is installed here, so that probe is a real one.
   */
  public function testArrayValuedSubfieldSurvivesRepeatedSetValue(): void {
    $map = [
      'display_id' => 'not a viewfield display',
      'items_to_display' => '99',
      'other' => ['nested' => TRUE],
    ];
    $this->createField('field_map', [
      'map_test' => ['name' => 'map_test', 'type' => 'map'],
      'string_test' => ['name' => 'string_test', 'type' => 'string'],
    ]);

    $values = ['map_test' => $map, 'string_test' => 'hello'];
    $node = Node::create(['type' => 'page', 'title' => 'Map guard']);
    $items = $node->get('field_map');

    $items->setValue([$values]);
    $node->validate();
    $this->assertSame($map, $items[0]->map_test, 'Map value intact after first pass.');

    $items->setValue([$values]);
    $this->assertSame($map, $items[0]->map_test, 'Map value intact after second pass.');
    $this->assertSame('hello', $items[0]->string_test);

    // No sibling keys were invented for a type that declares none.
    $this->assertArrayNotHasKey('map_test__display', $items[0]->getValue());
    $this->assertArrayNotHasKey('map_test__items', $items[0]->getValue());

    // The map subfield discards string keys on the storage round-trip. That is
    // pre-existing behavior of the map type - VERIFIED identical with
    // CustomItem's setValue() override disabled - and is asserted here only to
    // pin that the override does not change it.
    $node->save();
    $reloaded = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadUnchanged($node->id());
    $this->assertSame(array_values($map), $reloaded->get('field_map')[0]->map_test);
  }

  /**
   * Exactly the expected set of types overrides the extraction.
   *
   * CustomItem::extraPropertyMap() puts every overriding type on the setValue()
   * path, so adding an override changes behavior for that type's fields. Pin
   * the set here: a new override should be a deliberate edit to this list, not
   * a surprise in an unrelated test.
   *
   * Three of the expected ids come from modules other than custom_field, so
   * the assertion only means anything while those modules are installed. That
   * dependency is asserted up front rather than left to turn a trimmed
   * ::$modules list into an unexplained array diff.
   */
  public function testOverridingTypesAreExactlyTheExpectedSet(): void {
    $module_handler = $this->container->get('module_handler');
    foreach (['custom_field_viewfield', 'custom_field_extra_test'] as $module) {
      $this->assertTrue(
        $module_handler->moduleExists($module),
        sprintf('%s must be installed, or this assertion silently stops covering its types.', $module)
      );
    }

    $expected = [
      'daterange',
      'datetime',
      // From custom_field_extra_test: extra_test declares the override and
      // extra_test_child inherits it from a non-base ancestor.
      'extra_test',
      'extra_test_child',
      'image',
      'link',
      'time_range',
      // From custom_field_viewfield.
      'viewfield',
    ];

    $overriding = [];
    foreach ($this->container->get('plugin.manager.custom_field_type')->getDefinitions() as $id => $definition) {
      $declaring = (new \ReflectionMethod($definition['class'], 'extractExtraPropertyValues'))
        ->getDeclaringClass()->getName();
      if ($declaring !== CustomFieldTypeBase::class) {
        $overriding[] = $id;
      }
    }
    sort($overriding);

    $this->assertSame($expected, $overriding, sprintf(
      'Overriding type ids differ. Unexpected: [%s]. Missing: [%s]. Every overriding type joins the CustomItem::setValue() path, so update this list deliberately rather than to make the test pass.',
      implode(', ', array_diff($overriding, $expected)) ?: 'none',
      implode(', ', array_diff($expected, $overriding)) ?: 'none'
    ));
  }

  /**
   * Derived names are exactly the declared extra columns, per type.
   *
   * The subset check in testDerivedNamesMatchPropertyDefinitions() cannot
   * catch a derivation that silently returns fewer names, so these pin the
   * whole set for the types that have one.
   */
  public function testDerivedNamesAreExact(): void {
    $definitions = $this->container->get('plugin.manager.custom_field_type')->getDefinitions();

    $image = $definitions['image']['class'];
    $this->assertSame(
      ['x__width', 'x__height', 'x__alt', 'x__title'],
      $image::getExtraPropertyNames($this->settingsFor('x', 'image'))
    );

    $daterange = $definitions['daterange']['class'];
    $this->assertSame(
      ['x__timezone', 'x__end', 'x__duration'],
      $daterange::getExtraPropertyNames($this->settingsFor('x', 'daterange'))
    );

    $viewfield = $definitions['viewfield']['class'];
    $this->assertSame(
      ['x__display', 'x__arguments', 'x__items'],
      $viewfield::getExtraPropertyNames($this->settingsFor('x', 'viewfield'))
    );

    // A single-column type reports nothing.
    $string = $definitions['string']['class'];
    $this->assertSame([], $string::getExtraPropertyNames($this->settingsFor('x', 'string')));
  }

  /**
   * Two field definitions in one process do not share a cached map.
   *
   * CustomItem::extraPropertyMap() memoizes per field definition object. If
   * that cache leaked between definitions, one field's property allowlist
   * would be applied to another field's values.
   */
  public function testExtraPropertyMapIsPerFieldDefinition(): void {
    $this->createField('field_a', ['ex' => ['name' => 'ex', 'type' => 'extra_test']]);
    $this->createField('field_b', [
      'zz' => ['name' => 'zz', 'type' => 'extra_test'],
      'txt' => ['name' => 'txt', 'type' => 'string'],
    ]);

    $node = Node::create(['type' => 'page', 'title' => 'Two fields']);
    $node->get('field_a')->setValue([['ex' => ['value' => 'a', 'good' => 'A', 'bogus' => 'x']]]);
    $node->get('field_b')->setValue([['zz' => ['value' => 'b', 'good' => 'B', 'bogus' => 'x'], 'txt' => 't']]);

    $a = $node->get('field_a')[0]->getValue();
    $b = $node->get('field_b')[0]->getValue();

    $this->assertSame('A', $a['ex__good']);
    $this->assertArrayNotHasKey('zz__good', $a, 'field_a did not pick up field_b names.');
    $this->assertSame('B', $b['zz__good']);
    $this->assertArrayNotHasKey('ex__good', $b, 'field_b did not pick up field_a names.');
    $this->assertArrayNotHasKey('zz__bogus', $b);
  }

  /**
   * A type inheriting an override from a non-base ancestor is not skipped.
   */
  public function testInheritedOverrideIsNotSkipped(): void {
    $this->createField('field_child', ['ch' => ['name' => 'ch', 'type' => 'extra_test_child']]);

    $node = Node::create(['type' => 'page', 'title' => 'Child']);
    $node->get('field_child')->setValue([['ch' => ['value' => 'v', 'good' => 'inherited']]]);

    $values = $node->get('field_child')[0]->getValue();
    $this->assertSame('inherited', $values['ch__good'] ?? NULL, 'The inherited override still ran.');
  }

  /**
   * Creates a custom field with the given columns.
   *
   * @param string $field_name
   *   The field name.
   * @param array<string, mixed> $columns
   *   Column settings.
   */
  protected function createField(string $field_name, array $columns): void {
    $storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'custom',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => ['columns' => $columns],
    ]);
    $storage->save();

    $field_settings = [];
    foreach ($columns as $name => $column) {
      $field_settings[$name] = ['label' => $name, 'check_empty' => FALSE];
    }
    FieldConfig::create([
      'field_storage' => $storage,
      'bundle' => 'page',
      'label' => $field_name,
      'settings' => ['field_settings' => $field_settings],
    ])->save();

    $this->container->get('entity_field.manager')->clearCachedFieldDefinitions();
  }

}
