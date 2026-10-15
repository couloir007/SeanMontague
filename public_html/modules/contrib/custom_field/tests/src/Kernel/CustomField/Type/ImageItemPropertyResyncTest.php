<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Kernel\CustomField\Type;

use Drupal\custom_field\Plugin\CustomField\FieldType\ImageType;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\Tests\custom_field\Kernel\CustomField\CustomFieldKernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that image sibling properties survive repeated setValue() calls.
 *
 * CustomFieldImage::setValue() writes the alt and title values onto SIBLING
 * properties of the field item. Core's Map::setValue() re-syncs every ALREADY
 * INSTANTIATED property object from the incoming values array, so any sibling
 * missing from that array is reset to NULL on the second pass.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 * @covers \Drupal\custom_field\Plugin\CustomField\FieldType\ImageType
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
#[CoversClass(ImageType::class)]
class ImageItemPropertyResyncTest extends CustomFieldKernelTestBase {

  /**
   * The width of the test image, in pixels.
   */
  protected const IMAGE_WIDTH = 88;

  /**
   * The height of the test image, in pixels.
   */
  protected const IMAGE_HEIGHT = 100;

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
    'file',
  ];

  /**
   * The id of the referenced image file.
   */
  protected int $fileId;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);

    \Drupal::service('file_system')->copy($this->root . '/core/misc/druplicon.png', 'public://test.png');
    $file = File::create(['uri' => 'public://test.png']);
    $file->save();
    $this->fileId = (int) $file->id();

    $this->createCustomField(
      'field_test',
      [
        'img' => [
          'name' => 'img',
          'type' => 'image',
          'target_type' => 'file',
          'uri_scheme' => 'public',
        ],
      ],
      ['img' => ['label' => 'Image', 'check_empty' => FALSE]],
    );
  }

  /**
   * Returns the stored siblings of a saved node, read back from storage.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The saved node.
   *
   * @return array<string, mixed>
   *   The reloaded target_id/alt/title/width/height values.
   */
  protected function storedValues(Node $node): array {
    $item = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadUnchanged($node->id())
      ->get('field_test')[0] ?? NULL;

    // An item whose every subfield is empty is dropped on save, so there is
    // nothing to read back.
    if ($item === NULL) {
      return [
        'target_id' => NULL,
        'alt' => NULL,
        'title' => NULL,
        'width' => NULL,
        'height' => NULL,
      ];
    }

    return [
      'target_id' => $item->img,
      'alt' => $item->img__alt,
      'title' => $item->img__title,
      'width' => $item->img__width,
      'height' => $item->img__height,
    ];
  }

  /**
   * A repeated programmatic setValue() must keep the alt and title.
   *
   * This is the JSON:API / REST / migrate / custom-code shape: the nested
   * value array with no widget promotion involved. Without the extraction
   * override the second pass resets both siblings to NULL.
   */
  public function testProgrammaticNestedValueSurvivesRepeatedSetValue(): void {
    $values = [
      'img' => [
        'target_id' => $this->fileId,
        'alt' => 'Alt text',
        'title' => 'Title text',
      ],
    ];

    $node = Node::create(['type' => 'page', 'title' => 'Programmatic']);
    $items = $node->get('field_test');

    $items->setValue([$values]);
    $node->validate();
    $this->assertSame('Alt text', $items[0]->img__alt, 'Set on first pass.');

    $items->setValue([$values]);
    $this->assertSame('Alt text', $items[0]->img__alt, 'Survives second pass.');

    $node->save();

    $stored = $this->storedValues($node);
    $this->assertSame($this->fileId, (int) $stored['target_id']);
    $this->assertSame('Alt text', $stored['alt']);
    $this->assertSame('Title text', $stored['title']);
  }

  /**
   * The derived dimensions are still correct after a repeated setValue().
   *
   * The width and height columns are deliberately excluded from the
   * extraction because CustomItem::preSave() re-derives them from the
   * referenced file. That exclusion is only safe if the derivation actually
   * runs on this path, so assert the stored dimensions rather than assuming
   * it.
   */
  public function testDerivedDimensionsSurviveRepeatedSetValue(): void {
    $values = [
      'img' => [
        'target_id' => $this->fileId,
        'alt' => 'Alt text',
        'title' => 'Title text',
      ],
    ];

    $node = Node::create(['type' => 'page', 'title' => 'Dimensions']);
    $items = $node->get('field_test');

    $items->setValue([$values]);
    $node->validate();
    $items->setValue([$values]);
    $node->save();

    $stored = $this->storedValues($node);
    $this->assertSame(self::IMAGE_WIDTH, (int) $stored['width'], 'Width was re-derived from the file.');
    $this->assertSame(self::IMAGE_HEIGHT, (int) $stored['height'], 'Height was re-derived from the file.');
  }

  /**
   * Explicitly submitted dimensions are left to the data type, as before.
   *
   * CustomFieldImage::setValue() writes submitted width and height onto the
   * siblings, and CustomItem::preSave() only re-derives them when either is
   * empty - so a caller that supplies dimensions keeps them. That is
   * pre-existing behavior which the extraction override deliberately does not
   * change: leaving width and height out of the override means this path
   * stores exactly what it stored before. Asserted here so the exclusion is a
   * tested fact rather than an assumption about preSave().
   *
   * CHARACTERIZATION, not contract: this pins what the code does today, and
   * does not endorse it. A caller can store dimensions that do not match the
   * referenced file, and nothing validates them - note that the very same
   * values ARE re-derived once the item is set twice, per
   * ::testRepeatedSetValueDerivesDimensionsAgain(), so storage today
   * depends on how many times the item was set. Out of scope here: changing
   * it would change stored values on the single-set path.
   *
   * @todo A future fix may legitimately make this expectation wrong - for
   *   example by always re-deriving from the file. Update it with the guard
   *   rather than treating it as a regression.
   */
  public function testSubmittedDimensionsAreLeftToTheDataType(): void {
    $node = Node::create(['type' => 'page', 'title' => 'Submitted dimensions']);
    $node->get('field_test')->setValue([
      [
        'img' => [
          'target_id' => $this->fileId,
          'alt' => 'Alt text',
          'width' => 4242,
          'height' => 2424,
        ],
      ],
    ]);
    $node->save();

    $stored = $this->storedValues($node);
    $this->assertSame(4242, (int) $stored['width'], 'The submitted width was kept.');
    $this->assertSame(2424, (int) $stored['height'], 'The submitted height was kept.');
  }

  /**
   * A repeated setValue() re-derives dimensions instead of keeping submitted.
   *
   * This is the case the width/height exclusion exists for. On the second
   * pass Map::setValue() resets both siblings, and because the extraction
   * does not put them back, CustomItem::preSave() finds them empty and
   * re-derives them from the file. Had they been included in the extraction,
   * the caller's stale 4242x2424 would have survived instead.
   */
  public function testRepeatedSetValueDerivesDimensionsAgain(): void {
    $values = [
      'img' => [
        'target_id' => $this->fileId,
        'alt' => 'Alt text',
        'width' => 4242,
        'height' => 2424,
      ],
    ];

    $node = Node::create(['type' => 'page', 'title' => 'Stale dimensions']);
    $items = $node->get('field_test');

    $items->setValue([$values]);
    $node->validate();
    $items->setValue([$values]);
    $node->save();

    $stored = $this->storedValues($node);
    $this->assertSame('Alt text', $stored['alt'], 'The alt text survived the second pass.');
    $this->assertSame(self::IMAGE_WIDTH, (int) $stored['width'], 'The stale width was re-derived.');
    $this->assertSame(self::IMAGE_HEIGHT, (int) $stored['height'], 'The stale height was re-derived.');
  }

  /**
   * The node path stores exactly the same values as before the override.
   *
   * Run this with the override reverted to prove it changed no stored value.
   *
   * @param array<string, mixed> $value
   *   The nested subfield value, with 'target_id' resolved by the test.
   * @param array<string, mixed> $expected
   *   The expected reloaded alt/title values.
   *
   * @dataProvider providerStoredValues
   */
  #[DataProvider('providerStoredValues')]
  public function testStoredValues(array $value, array $expected): void {
    if (($value['target_id'] ?? NULL) === 'FILE') {
      $value['target_id'] = $this->fileId;
    }

    $node = Node::create(['type' => 'page', 'title' => 'Matrix ' . md5(serialize($value))]);
    $node->get('field_test')->setValue([['img' => $value]]);
    $node->save();

    $stored = $this->storedValues($node);
    $this->assertSame($expected['alt'], $stored['alt']);
    $this->assertSame($expected['title'], $stored['title']);
  }

  /**
   * Data provider for testStoredValues().
   *
   * @return array<string, array{array<string, mixed>, array<string, mixed>}>
   *   Test cases.
   */
  public static function providerStoredValues(): array {
    $base = ['target_id' => 'FILE'];
    $expect = static function (mixed $alt, mixed $title): array {
      return ['alt' => $alt, 'title' => $title];
    };

    return [
      'alt missing' => [$base, $expect(NULL, NULL)],
      'alt NULL' => [$base + ['alt' => NULL], $expect(NULL, NULL)],
      'alt empty string' => [$base + ['alt' => ''], $expect('', NULL)],
      'alt valid' => [$base + ['alt' => 'A'], $expect('A', NULL)],
      'title missing' => [$base + ['alt' => 'A'], $expect('A', NULL)],
      'title NULL' => [$base + ['alt' => 'A', 'title' => NULL], $expect('A', NULL)],
      'title empty string' => [$base + ['alt' => 'A', 'title' => ''], $expect('A', '')],
      'title valid' => [$base + ['alt' => 'A', 'title' => 'T'], $expect('A', 'T')],
      // CustomItem::preSave() clears every sibling when there is no file, and
      // an item with no other subfield is then dropped entirely. These lock
      // that clearing behavior rather than the extraction guard.
      'target_id NULL drops the item' => [
        ['target_id' => NULL, 'alt' => 'A', 'title' => 'T'],
        $expect(NULL, NULL),
      ],
      'target_id empty string drops the item' => [
        ['target_id' => '', 'alt' => 'A', 'title' => 'T'],
        $expect(NULL, NULL),
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
    $class = $this->typeManager->getDefinitions()['image']['class'];

    $this->assertSame($expected, $class::extractExtraPropertyValues(['name' => 'img'], $value));
  }

  /**
   * Data provider for testExtraPropertyMapping().
   *
   * @return array<string, array{mixed, array<string, mixed>}>
   *   Test cases.
   */
  public static function providerMapping(): array {
    $base = ['target_id' => 1];

    return [
      'alt missing' => [$base, []],
      'alt NULL' => [$base + ['alt' => NULL], []],
      'alt empty string' => [$base + ['alt' => ''], ['img__alt' => '']],
      'alt valid' => [$base + ['alt' => 'A'], ['img__alt' => 'A']],
      'title missing' => [$base + ['alt' => 'A'], ['img__alt' => 'A']],
      'title NULL' => [$base + ['alt' => 'A', 'title' => NULL], ['img__alt' => 'A']],
      'title empty string' => [
        $base + ['alt' => 'A', 'title' => ''],
        ['img__alt' => 'A', 'img__title' => ''],
      ],
      'title valid' => [
        $base + ['alt' => 'A', 'title' => 'T'],
        ['img__alt' => 'A', 'img__title' => 'T'],
      ],
      // The exclusion that keeps CustomItem::preSave() authoritative.
      'width and height are never extracted' => [
        $base + ['alt' => 'A', 'width' => 10, 'height' => 20],
        ['img__alt' => 'A'],
      ],
      'target_id empty still extracts' => [
        ['target_id' => NULL, 'alt' => 'A'],
        ['img__alt' => 'A'],
      ],
      'not an array' => [1, []],
      'empty array' => [[], []],
    ];
  }

}
