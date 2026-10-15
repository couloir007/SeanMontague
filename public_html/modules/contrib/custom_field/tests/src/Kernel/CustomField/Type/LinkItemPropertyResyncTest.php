<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Kernel\CustomField\Type;

use Drupal\custom_field\Plugin\CustomField\FieldType\LinkType;
use Drupal\node\Entity\Node;
use Drupal\Tests\custom_field\Kernel\CustomField\CustomFieldKernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that link sibling properties survive repeated setValue() calls.
 *
 * CustomFieldLink::setValue() writes the title and options values onto SIBLING
 * properties of the field item. Core's Map::setValue() re-syncs every ALREADY
 * INSTANTIATED property object from the incoming values array, so any sibling
 * missing from that array is reset to NULL on the second pass.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 * @covers \Drupal\custom_field\Plugin\CustomField\FieldType\LinkType
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
#[CoversClass(LinkType::class)]
class LinkItemPropertyResyncTest extends CustomFieldKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'node',
    'text',
    'custom_field',
    'image',
    'link',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createCustomField(
      'field_test',
      ['lnk' => ['name' => 'lnk', 'type' => 'link']],
      ['lnk' => ['label' => 'Link', 'check_empty' => FALSE]],
    );
  }

  /**
   * Returns the stored siblings of a saved node, read back from storage.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The saved node.
   *
   * @return array<string, mixed>
   *   The reloaded uri/title/options values.
   */
  protected function storedValues(Node $node): array {
    $item = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadUnchanged($node->id())
      ->get('field_test')[0] ?? NULL;

    // An item whose every subfield is empty is dropped on save, so there is
    // nothing to read back.
    if ($item === NULL) {
      return ['uri' => NULL, 'title' => NULL, 'options' => NULL];
    }

    return [
      'uri' => $item->lnk,
      'title' => $item->lnk__title,
      'options' => $item->lnk__options,
    ];
  }

  /**
   * A repeated programmatic setValue() must keep the title and options.
   *
   * This is the JSON:API / REST / migrate / custom-code shape: the nested
   * value array with no widget promotion involved. Without the extraction
   * override the second pass resets both siblings to NULL.
   */
  public function testProgrammaticNestedValueSurvivesRepeatedSetValue(): void {
    $values = [
      'lnk' => [
        'uri' => 'https://example.com',
        'title' => 'Example',
        'options' => ['attributes' => ['target' => '_blank']],
      ],
    ];

    $node = Node::create(['type' => 'page', 'title' => 'Programmatic']);
    $items = $node->get('field_test');

    $items->setValue([$values]);
    $node->validate();
    $this->assertSame('Example', $items[0]->lnk__title, 'Set on first pass.');

    $items->setValue([$values]);
    $this->assertSame('Example', $items[0]->lnk__title, 'Survives second pass.');

    $node->save();

    $stored = $this->storedValues($node);
    $this->assertSame('https://example.com', $stored['uri']);
    $this->assertSame('Example', $stored['title']);
    $this->assertSame(['attributes' => ['target' => '_blank']], $stored['options']);
  }

  /**
   * The node path stores exactly the same values as before the override.
   *
   * Run this with the override reverted to prove it changed no stored value.
   *
   * @param array<string, mixed> $value
   *   The nested subfield value.
   * @param array<string, mixed> $expected
   *   The expected reloaded uri/title/options values.
   *
   * @dataProvider providerStoredValues
   */
  #[DataProvider('providerStoredValues')]
  public function testStoredValues(array $value, array $expected): void {
    $node = Node::create(['type' => 'page', 'title' => 'Matrix ' . md5(serialize($value))]);
    $node->get('field_test')->setValue([['lnk' => $value]]);
    $node->save();

    $this->assertSame($expected, $this->storedValues($node));
  }

  /**
   * Data provider for testStoredValues().
   *
   * @return array<string, array{array<string, mixed>, array<string, mixed>}>
   *   Test cases.
   */
  public static function providerStoredValues(): array {
    $uri = 'https://example.com';
    $expect = static function (mixed $title, mixed $options, mixed $stored_uri = 'https://example.com'): array {
      return ['uri' => $stored_uri, 'title' => $title, 'options' => $options];
    };

    return [
      'title missing' => [
        ['uri' => $uri],
        $expect(NULL, NULL),
      ],
      'title NULL' => [
        ['uri' => $uri, 'title' => NULL],
        $expect(NULL, NULL),
      ],
      'title empty string' => [
        ['uri' => $uri, 'title' => ''],
        $expect('', NULL),
      ],
      'title valid' => [
        ['uri' => $uri, 'title' => 'Example'],
        $expect('Example', NULL),
      ],
      'options missing' => [
        ['uri' => $uri, 'title' => 'Example'],
        $expect('Example', NULL),
      ],
      'options NULL' => [
        ['uri' => $uri, 'title' => 'Example', 'options' => NULL],
        $expect('Example', NULL),
      ],
      'options empty string is not an array' => [
        ['uri' => $uri, 'title' => 'Example', 'options' => ''],
        $expect('Example', NULL),
      ],
      'options empty array' => [
        ['uri' => $uri, 'title' => 'Example', 'options' => []],
        $expect('Example', []),
      ],
      'options populated' => [
        ['uri' => $uri, 'title' => 'Example', 'options' => ['attributes' => ['target' => '_blank']]],
        $expect('Example', ['attributes' => ['target' => '_blank']]),
      ],
      // An item whose only subfield has no uri is empty, so the whole item is
      // dropped on save. These lock that clearing behavior rather than the
      // extraction guard, which still runs and is simply discarded with it.
      'uri empty string drops the item' => [
        ['uri' => '', 'title' => 'Example', 'options' => ['attributes' => []]],
        $expect(NULL, NULL, NULL),
      ],
      'uri NULL drops the item' => [
        ['uri' => NULL, 'title' => 'Example', 'options' => ['attributes' => []]],
        $expect(NULL, NULL, NULL),
      ],
    ];
  }

  /**
   * The type plugin maps a nested value onto its extra property names.
   *
   * Mirrors the input cases of ::providerStoredValues() at the level of the
   * mapping itself, so a change in either is visible in both.
   *
   * @param mixed $value
   *   The nested subfield value.
   * @param array<string, mixed> $expected
   *   The expected extra property values.
   *
   * @dataProvider providerMapping
   */
  #[DataProvider('providerMapping')]
  public function testExtraPropertyMapping(mixed $value, array $expected): void {
    $class = $this->typeManager->getDefinitions()['link']['class'];

    $this->assertSame($expected, $class::extractExtraPropertyValues(['name' => 'lnk'], $value));
  }

  /**
   * Data provider for testExtraPropertyMapping().
   *
   * @return array<string, array{mixed, array<string, mixed>}>
   *   Test cases.
   */
  public static function providerMapping(): array {
    $base = ['uri' => 'https://example.com'];

    return [
      'title missing' => [$base, []],
      'title NULL' => [$base + ['title' => NULL], []],
      'title empty string' => [$base + ['title' => ''], ['lnk__title' => '']],
      'title valid' => [$base + ['title' => 'Example'], ['lnk__title' => 'Example']],
      'options missing' => [
        $base + ['title' => 'Example'],
        ['lnk__title' => 'Example'],
      ],
      'options NULL' => [
        $base + ['title' => 'Example', 'options' => NULL],
        ['lnk__title' => 'Example'],
      ],
      'options empty string is not an array' => [
        $base + ['title' => 'Example', 'options' => ''],
        ['lnk__title' => 'Example'],
      ],
      'options empty array' => [
        $base + ['title' => 'Example', 'options' => []],
        ['lnk__title' => 'Example', 'lnk__options' => []],
      ],
      'options populated' => [
        $base + ['options' => ['attributes' => ['target' => '_blank']]],
        ['lnk__options' => ['attributes' => ['target' => '_blank']]],
      ],
      'uri empty string still extracts' => [
        ['uri' => '', 'title' => 'Example'],
        ['lnk__title' => 'Example'],
      ],
      'not an array' => ['https://example.com', []],
      'empty array' => [[], []],
    ];
  }

}
