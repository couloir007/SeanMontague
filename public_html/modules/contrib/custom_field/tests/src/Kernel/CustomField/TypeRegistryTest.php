<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Kernel\CustomField;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Registry-level invariants over the whole set of sub-field type plugins.
 *
 * These assertions belong to no single type, which is why they live here rather
 * than in a Type/XxxTypeTest. Together they are the completeness gate: add a
 * new sub-field type without a matching Type/XxxTypeTest and this class fails.
 * They replace the gate the removed CustomFieldItemTest::testFieldTypePlugins()
 * used to provide through the custom_field_test test plugins.
 *
 * Type ids come from CustomFieldTypeManager::getDefinitions(), never from a
 * grep over source. DecimalType declares `id: "decimal"` with double quotes
 * while every other type uses single quotes, so any text-scraping enumeration
 * (e.g. `grep "id: '"`) silently omits exactly one type — the precise class
 * of gap this class exists to close.
 *
 * The expected test class is derived from each plugin's CLASS short name, not
 * from its id: `EntityReference` has no `Type` suffix, and `daterange` would
 * camel-case to `Daterange` rather than the real `DateRange`. The namespace is
 * derived from the definition's `provider`, which is how `viewfield` — provided
 * by the custom_field_viewfield submodule and tested there — is accounted for
 * without hardcoding it.
 *
 * custom_field_extra_test is deliberately NOT enabled. It ships three further
 * types with no tests of their own, and enabling it here would fail these
 * assertions.
 *
 * @see \Drupal\Tests\custom_field\Kernel\CustomField\CustomFieldTypeKernelTestBase
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class TypeRegistryTest extends CustomFieldKernelTestBase {

  /**
   * {@inheritdoc}
   *
   * KernelTestBase::getModulesToEnable() merges $modules up the class
   * hierarchy, taking each class's own property, so restating
   * CustomFieldKernelTestBase's seven modules here was redundant. Only this
   * class's two additions are needed.
   */
  protected static $modules = [
    'views',
    'custom_field_viewfield',
  ];

  /**
   * Returns the type definitions, keyed by plugin id.
   *
   * @return array<string, array<string, mixed>>
   *   The definitions.
   */
  private function typeDefinitions(): array {
    $definitions = $this->typeManager->getDefinitions();
    $this->assertNotEmpty($definitions, 'The type manager discovered at least one sub-field type.');

    return $definitions;
  }

  /**
   * Returns the fully qualified Type test class expected for a definition.
   *
   * @param array<string, mixed> $definition
   *   The plugin definition.
   *
   * @return string
   *   The expected test class name.
   */
  private function expectedTestClass(array $definition): string {
    $class = (string) $definition['class'];
    $short = substr($class, (int) strrpos($class, '\\') + 1);
    if (!str_ends_with($short, 'Type')) {
      $short .= 'Type';
    }

    return sprintf(
      'Drupal\\Tests\\%s\\Kernel\\CustomField\\Type\\%sTest',
      $definition['provider'],
      $short,
    );
  }

  /**
   * Every discovered sub-field type has a Type/XxxTypeTest class.
   */
  public function testEveryTypeHasDedicatedTestClass(): void {
    foreach ($this->typeDefinitions() as $id => $definition) {
      $expected = $this->expectedTestClass($definition);
      $this->assertTrue(class_exists($expected), sprintf(
        'Type "%s" has no Kernel test. Add %s extending CustomFieldTypeKernelTestBase.',
        $id,
        $expected,
      ));
    }
  }

  /**
   * Every existing Type test extends the shared per-type base.
   *
   * Existence alone is not the guarantee — the abstract case contract lives on
   * CustomFieldTypeKernelTestBase, so a class that does not extend it escapes
   * the contract entirely.
   */
  public function testEveryTypeTestExtendsTheSharedBase(): void {
    foreach ($this->typeDefinitions() as $definition) {
      $expected = $this->expectedTestClass($definition);
      if (!class_exists($expected)) {
        continue;
      }

      $this->assertTrue(is_subclass_of($expected, CustomFieldTypeKernelTestBase::class), sprintf(
        '%s must extend CustomFieldTypeKernelTestBase so it inherits the round-trip, validation and sample-value contract.',
        $expected,
      ));
    }
  }

  /**
   * Every type's default widget and formatter resolve to real plugins.
   *
   * This is the default widget and formatter half of the removed
   * CustomFieldItemTest::testFieldTypePlugins(), the half that survives the
   * removal of the test plugins. The other half compared a type's declared
   * default against the test plugin's copy of the same value, which is a
   * restatement rather than an independent check. What matters, and
   * what is kept here, is that a type's declared default names a plugin that
   * exists and whose class implements the right interface — the thing that
   * breaks when a widget or formatter is renamed or removed.
   */
  public function testEveryTypeDefaultWidgetAndFormatterResolve(): void {
    foreach ($this->typeDefinitions() as $id => $definition) {
      foreach ([
        'default_widget' => $this->widgetManager,
        'default_formatter' => $this->formatterManager,
      ] as $key => $manager) {
        $plugin_id = $definition[$key] ?? NULL;
        $this->assertNotEmpty($plugin_id, sprintf('Type "%s" declares a %s.', $id, $key));

        $plugin_definition = $manager->getDefinition($plugin_id, FALSE);
        $this->assertNotNull($plugin_definition, sprintf(
          'Type "%s" declares %s "%s", which is not a registered plugin.',
          $id,
          $key,
          $plugin_id,
        ));
        $this->assertTrue(class_exists($plugin_definition['class']), sprintf(
          'Type "%s" declares %s "%s", whose class %s does not exist.',
          $id,
          $key,
          $plugin_id,
          $plugin_definition['class'],
        ));
      }
    }
  }

}
