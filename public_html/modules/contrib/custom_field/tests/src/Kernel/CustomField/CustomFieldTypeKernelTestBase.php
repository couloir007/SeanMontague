<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Kernel\CustomField;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\node\Entity\Node;

/**
 * Base class for per-sub-field-type Kernel tests.
 *
 * Every sub-field type gets one `Type/XxxTypeTest` extending this class,
 * modelled on core's field type Kernel tests (EmailItemTest, LinkItemTest,
 * ImageItemTest, DateTimeItemTest and friends). The three abstract members are
 * the contract that makes "every type asserts something" structural rather than
 * advisory: a concrete subclass that does not implement them cannot load at
 * all, and testRoundTrip() additionally refuses an empty case set.
 *
 * Each case creates its OWN field with explicit settings. Nothing here reads
 * custom_field_test's shipped config — that is default config, not a test
 * matrix, and a per-type test should own the settings it depends on. Adding a
 * settings variation is therefore one more array entry, not a new test method.
 *
 * Case arrays are keyed by a human-readable label, which is echoed in every
 * failure message. Recognized keys:
 *   - 'column'   (optional) column definition overrides, merged over
 *                ::defaultColumn().
 *   - 'settings' (optional) per-sub-field field_settings overrides, merged over
 *                ::defaultFieldSettings().
 *   - 'value'    (required) sub-field property => value to write.
 *   - 'expected' (round-trip only) sub-field property => expected stored value.
 *   - 'property' (validation only) the property expected to fail.
 *   - 'message'  (validation only) expected violation message, markup stripped.
 *   - 'count'    (validation only, default 1) expected number of violations.
 *
 * NOTE: PHP attributes are not inherited, so each concrete subclass must
 * declare its own #[Group('custom_field')], #[RunTestsInSeparateProcesses] and
 * #[CoversClass(...)].
 *
 * @see \Drupal\Tests\custom_field\Kernel\CustomField\CustomFieldWidgetKernelTestBase
 */
abstract class CustomFieldTypeKernelTestBase extends CustomFieldKernelTestBase {

  /**
   * The custom_field sub-field type plugin id under test.
   *
   * @return string
   *   The plugin id, e.g. 'string' or 'daterange'.
   */
  abstract protected static function typeId(): string;

  /**
   * Cases asserting that written values survive a save/reload round trip.
   *
   * Must not be empty — a type with no round-trip case is a type with no
   * coverage, which is exactly what this contract exists to prevent.
   *
   * @return array<string, array<string, mixed>>
   *   Cases keyed by label. See the class docblock for the recognized keys.
   */
  abstract protected function roundTripCases(): array;

  /**
   * Cases asserting that invalid values produce the expected violation.
   *
   * May legitimately be empty — several types have no validation constraints of
   * their own. Returning an empty array is a deliberate, visible statement, and
   * it is checked rather than trusted: testValidation() then asserts the type
   * really has no constraints of its own, on all three of the mechanisms a
   * type can carry one through (its own getConstraints(), a constraint added
   * to a property definition with addConstraint(), and any constraint a
   * declared property's typed-data primitive attaches automatically), and
   * fails loudly, naming the type, if any one turns out not to be empty.
   *
   * @return array<string, array<string, mixed>>
   *   Cases keyed by label. See the class docblock for the recognized keys.
   */
  abstract protected function validationCases(): array;

  /**
   * Constraints of the type's own that no supported input can trigger.
   *
   * A property definition can declare a constraint that is never violated in
   * practice, for example one validating a computed property whose value is
   * always loaded from the right storage. Such a constraint cannot get an
   * honest validation case, and silently ignoring it would defeat the guard
   * in ::assertNoConstraintsOfItsOwn(). Declaring it here is the explicit
   * alternative: each entry names the property, the constraint, the reason it
   * is unreachable, and the test method in the same class that proves it.
   * ::testValidation() checks every entry: the constraint must really be
   * declared on that property, the reason must not be empty, and the named
   * test method must exist.
   *
   * @return list<array{property: string, constraint: string, reason: string, test: string}>
   *   The declared exemptions. Empty by default.
   */
  protected function unreachableConstraints(): array {
    return [];
  }

  /**
   * The sub-field (column) machine name used by the generated fields.
   *
   * Deliberately NOT the type id. The shipped `field_test` field names every
   * column after its own type, and parts of the test suite quietly rely on that
   * coincidence; a neutral name here means these tests would catch code that
   * assumes column name == type id.
   *
   * @return string
   *   The column machine name.
   */
  protected function columnName(): string {
    return 'sub';
  }

  /**
   * The minimal column definition for this type.
   *
   * Override to add keys the type requires, e.g. 'datetime_type' for datetime.
   *
   * @param string $name
   *   The column machine name.
   *
   * @return array<string, mixed>
   *   The column definition.
   */
  protected function defaultColumn(string $name): array {
    return [
      'name' => $name,
      'type' => static::typeId(),
    ];
  }

  /**
   * The default per-sub-field field settings for the generated fields.
   *
   * @return array<string, mixed>
   *   The field settings for this type's single column.
   */
  protected function defaultFieldSettings(): array {
    return [
      'label' => 'Test subfield',
      'check_empty' => FALSE,
    ];
  }

  /**
   * The storage cardinality for the generated fields.
   *
   * @return int
   *   The cardinality.
   */
  protected function cardinality(): int {
    return FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED;
  }

  /**
   * Creates a single-column field for one case.
   *
   * @param array<string, mixed> $case
   *   The case array; only 'column' and 'settings' are read here.
   * @param string|int $suffix
   *   A per-case suffix keeping each generated field name unique.
   *
   * @return string
   *   The generated field machine name.
   */
  protected function createCaseField(array $case, string|int $suffix): string {
    $name = $this->columnName();
    $field_name = 'field_' . $name . '_' . $suffix;

    $this->createCustomField(
      $field_name,
      [$name => [...$this->defaultColumn($name), ...($case['column'] ?? [])]],
      [$name => [...$this->defaultFieldSettings(), ...($case['settings'] ?? [])]],
      'node',
      'page',
      $this->cardinality(),
    );

    return $field_name;
  }

  /**
   * Returns this type's column settings as stored in a case field's config.
   *
   * Reads the saved config rather than the field definition, so the result
   * shows what the config save cast each value to under the config schema.
   *
   * @param string $field_name
   *   A field created by ::createCaseField().
   *
   * @return array<string, mixed>
   *   The stored field settings of the column.
   */
  protected function storedColumnSettings(string $field_name): array {
    return $this->config('field.field.node.page.' . $field_name)
      ->get('settings.field_settings.' . $this->columnName());
  }

  /**
   * Asserts that every declared round-trip case survives a save and reload.
   */
  public function testRoundTrip(): void {
    $cases = $this->roundTripCases();
    $this->assertNotEmpty($cases, sprintf(
      'Type "%s" must declare at least one case in roundTripCases().',
      static::typeId(),
    ));

    $index = 0;
    foreach ($cases as $label => $case) {
      $context = sprintf('[%s / %s]', static::typeId(), $label);
      $field_name = $this->createCaseField($case, $index++);

      $node = Node::create([
        'type' => 'page',
        'title' => 'Round trip ' . $label,
      ]);
      $node->set($field_name, [$case['value']]);
      $this->assertNoViolations($node, $context);
      $node->save();

      $item = $this->storedItem($node, $field_name);
      $this->assertNotNull($item, $context . ' The saved item was read back from storage.');

      foreach ($case['expected'] as $property => $expected) {
        $this->assertSame(
          $expected,
          $item->get($property)->getValue(),
          sprintf('%s The "%s" property round-tripped.', $context, $property),
        );
      }
    }
  }

  /**
   * Asserts that every declared validation case produces its violation.
   *
   * An empty validationCases() is checked, not trusted, see
   * ::assertNoConstraintsOfItsOwn().
   */
  public function testValidation(): void {
    $this->assertUnreachableConstraintsAreDeclaredProperly();

    $cases = $this->validationCases();
    if ($cases === []) {
      $this->assertNoConstraintsOfItsOwn();
      return;
    }

    $index = 0;
    foreach ($cases as $label => $case) {
      $context = sprintf('[%s / %s]', static::typeId(), $label);
      $field_name = $this->createCaseField($case, 'v' . $index++);

      $node = Node::create([
        'type' => 'page',
        'title' => 'Validation ' . $label,
      ]);
      $node->set($field_name, [$case['value']]);

      $this->assertViolation(
        $node,
        $case['property'],
        $case['message'],
        $case['count'] ?? 1,
        $context,
      );
    }
  }

  /**
   * Asserts an empty validationCases() is honest: the type truly has none.
   *
   * A property-level constraint declared in ::unreachableConstraints() is
   * left out of the second check; that declaration is itself verified by
   * ::assertUnreachableConstraintsAreDeclaredProperly().
   *
   * Checked on all three mechanisms a type's constraints can come from. The
   * first two were discovered while writing the email sub-field type's test:
   * the type plugin's own getConstraints() must return nothing, and none of
   * its declared properties may use a typed-data primitive that itself carries
   * constraints — the way a type can pick up real validation for free without
   * adding any of its own (see EmailTypeTest's class docblock for the worked
   * example). The third was found converting datetime: a property definition
   * can carry a constraint of its own through addConstraint(), which is
   * exactly how datetime's and daterange's timezone AllowedValues list is
   * declared, and neither of the first two checks sees it. Any signal firing
   * fails loudly, naming the type, instead of a quiet, possibly-false skip.
   *
   * Exists to catch, mechanically, a mistake that was otherwise made twice
   * and caught only by running the suite each time: an empty-looking
   * validationCases() that actually hid a real constraint.
   */
  private function assertNoConstraintsOfItsOwn(): void {
    $type_id = static::typeId();
    $column = $this->defaultColumn($this->columnName());

    $options = $this->typeManager->createOptionsForInstance($this->defaultFieldSettings(), $column);
    $instance = $this->typeManager->createInstance($type_id, $options['configuration']);
    $this->assertInstanceOf(CustomFieldTypeInterface::class, $instance);
    $this->assertSame([], $instance->getConstraints(), sprintf(
      'Type "%s" declares validationCases() as empty, but its own getConstraints() is not empty. Add a validation case for it.',
      $type_id,
    ));

    $typed_data_manager = \Drupal::typedDataManager();
    foreach ($instance::propertyDefinitions($column) as $property_name => $definition) {
      // Only what the definition itself declares: getConstraints() would also
      // merge in the typed-data manager's defaults (PrimitiveType, NotNull),
      // which every primitive property carries and which are not the type's
      // own. toArray() is declared on the concrete class only.
      $this->assertInstanceOf(DataDefinition::class, $definition);
      $own_constraints = array_diff_key(
        $definition->toArray()['constraints'] ?? [],
        array_flip($this->exemptedConstraintNames((string) $property_name)),
      );
      $this->assertSame([], $own_constraints, sprintf(
        'Type "%s" declares validationCases() as empty, but its "%s" property definition carries its own constraints (added with addConstraint()). Add a validation case for it, or declare it in unreachableConstraints() with a test proving it.',
        $type_id,
        $property_name,
      ));

      $data_type = $definition->getDataType();
      $type_definition = $typed_data_manager->getDefinition($data_type);
      $this->assertEmpty($type_definition['constraints'] ?? [], sprintf(
        'Type "%s" declares validationCases() as empty, but its "%s" property uses the "%s" data type, which declares its own constraints (e.g. an automatic format check). Add a validation case for it.',
        $type_id,
        $property_name,
        $data_type,
      ));
    }
  }

  /**
   * Asserts every unreachableConstraints() entry is real, explained and tested.
   *
   * Runs for every class, whether or not its validationCases() is empty, so a
   * stale entry (the constraint was removed, or the proving test renamed)
   * fails instead of lingering. A class declaring no exemptions makes no
   * assertions here.
   */
  private function assertUnreachableConstraintsAreDeclaredProperly(): void {
    if ($this->unreachableConstraints() === []) {
      return;
    }

    $type_id = static::typeId();
    $column = $this->defaultColumn($this->columnName());
    $options = $this->typeManager->createOptionsForInstance($this->defaultFieldSettings(), $column);
    $instance = $this->typeManager->createInstance($type_id, $options['configuration']);
    $this->assertInstanceOf(CustomFieldTypeInterface::class, $instance);
    $definitions = $instance::propertyDefinitions($column);

    foreach ($this->unreachableConstraints() as $exemption) {
      $label = sprintf('Type "%s", unreachable constraint "%s" on "%s":', $type_id, $exemption['constraint'], $exemption['property']);
      $this->assertArrayHasKey($exemption['property'], $definitions, $label . ' the property does not exist.');
      $definition = $definitions[$exemption['property']];
      $this->assertInstanceOf(DataDefinition::class, $definition);
      $this->assertArrayHasKey($exemption['constraint'], $definition->toArray()['constraints'] ?? [], $label . ' the property does not declare it, so the exemption is stale.');
      $this->assertNotSame('', trim($exemption['reason']), $label . ' a reason is required.');
      $this->assertTrue(
        str_starts_with($exemption['test'], 'test') && method_exists($this, $exemption['test']),
        $label . sprintf(' the proving test method "%s" does not exist in %s.', $exemption['test'], static::class),
      );
    }
  }

  /**
   * The constraint names declared unreachable for one property.
   *
   * @param string $property_name
   *   The property name, e.g. 'sub__entity'.
   *
   * @return list<string>
   *   The exempted constraint names.
   */
  private function exemptedConstraintNames(string $property_name): array {
    $names = [];
    foreach ($this->unreachableConstraints() as $exemption) {
      if ($exemption['property'] === $property_name) {
        $names[] = $exemption['constraint'];
      }
    }
    return $names;
  }

  /**
   * Asserts that generated sample values for this type validate and save.
   */
  public function testSampleValueGeneration(): void {
    $this->assertGeneratedSampleSaves($this->createCaseField([], 'sample'));
  }

}
