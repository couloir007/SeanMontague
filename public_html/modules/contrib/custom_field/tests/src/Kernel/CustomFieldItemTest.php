<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Kernel;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\Tests\custom_field\Kernel\CustomField\CustomFieldKernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the custom field item as a whole, with many sub-fields in one item.
 *
 * Behavior that belongs to a single sub-field type is tested by that type's
 * own class under CustomField/Type/. This class keeps only what no per-type
 * class can see, because each of those builds a field with one or two
 * columns: several sub-field types living side by side in one item, on the
 * shipped 23-column field_test.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class CustomFieldItemTest extends CustomFieldKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'custom_field_viewfield',
    'custom_field_test',
    'file',
    'filter',
    'views',
  ];

  /**
   * The bundle that carries the shipped test fields.
   */
  private const BUNDLE = 'custom_field_entity_test';

  /**
   * The shipped field with one column of every sub-field type.
   */
  private const FIELD = 'field_test';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // The parent installs the node schema before this module's config, so the
    // shipped field storages are created with their entity schema in place.
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
    $this->installConfig(['custom_field_test']);
  }

  /**
   * Sample values for every sub-field type in one item validate and save.
   */
  public function testCustomFieldItem(): void {
    $this->assertNotNull(FieldStorageConfig::loadByName('node', self::FIELD), 'The field storage configuration exists.');
    $this->assertGeneratedSampleSaves(self::FIELD, self::BUNDLE);
  }

  /**
   * CONTRACT: every sub-field type keeps its own values beside all the others.
   *
   * All 23 columns of field_test, extra properties included, are written in
   * one item. The item is given a second, different set of values before the
   * first save, and every stored column is then compared with what was
   * written last, so nothing from the first round may survive. Each value is
   * one its type stores unchanged, so a type's own normalization cannot be
   * mistaken for interference between columns.
   *
   * Two neighbor pairs are included on purpose: time beside time_range, and
   * map beside map_string, where one column name is a prefix of the other's.
   *
   * The reference columns point at fixtures the test creates itself: two
   * article nodes, two text files, two images and the two views the module's
   * test config ships. Each is referenced by id, never as an unsaved entity.
   */
  public function testMultiColumnRoundTrip(): void {
    $articles = [$this->savedArticleId('First article'), $this->savedArticleId('Second article')];
    $files = [$this->savedFileId('public://first.txt'), $this->savedFileId('public://second.txt')];
    $images = [
      $this->savedFileId('public://first.png', 'core/misc/druplicon.png'),
      $this->savedFileId('public://second.png', 'core/tests/fixtures/files/image-1.png'),
    ];

    $node = Node::create(['type' => self::BUNDLE, 'title' => 'multi-column round trip']);
    $items = $node->get(self::FIELD);
    $items->setValue([[
      'string' => 'first string',
      'string_long' => 'First long text.',
      'integer' => 7,
      'decimal' => '1.50',
      'float' => 1.25,
      'email' => 'first@example.com',
      'telephone' => '+0123456789',
      'uri' => 'https://example.com/first-uri',
      'boolean' => 0,
      'uuid' => '0b0f2c1e-6a3b-4c5d-8e9f-0a1b2c3d4e5f',
      'color' => '#000000',
      'map' => [['key' => 'k1', 'value' => 'v1']],
      'map_string' => ['one'],
      'datetime' => '2016-11-04T00:21:00',
      'datetime__timezone' => 'Europe/Berlin',
      'daterange' => '2020-01-01T00:00:00',
      'daterange__end' => '2020-01-02T00:00:00',
      'daterange__timezone' => 'Europe/Berlin',
      'entity_reference' => $articles[0],
      'file' => $files[0],
      'image' => $images[0],
      'image__alt' => 'First alt',
      'image__title' => 'First title',
      'link' => 'https://example.com/first',
      'link__title' => 'First',
      'link__options' => ['query' => ['a' => '1']],
      'time' => 24030,
      'time_range' => 3600,
      'time_range__end' => 5400,
      'viewfield' => 'custom_field_test_2',
      'viewfield__display' => 'default',
      'viewfield__arguments' => 'first',
      'viewfield__items' => 3,
      'duration' => 86400,
    ],
    ]);
    $items->setValue([[
      'string' => 'second string',
      'string_long' => "Second long text.\n\nWith a second paragraph.",
      'integer' => 42,
      'decimal' => '18.20',
      'float' => 2.5,
      'email' => 'second@example.com',
      'telephone' => '+41791234567',
      'uri' => 'https://www.drupal.com',
      'boolean' => 1,
      'uuid' => '9a8b7c6d-5e4f-4a3b-9c2d-1e0f9a8b7c6d',
      'color' => '#5BCEFA',
      'map' => [['key' => 'k2', 'value' => 'v2'], ['key' => 'k3', 'value' => 'v3']],
      'map_string' => ['two', 'three'],
      'datetime' => '2014-01-01T20:00:00',
      'datetime__timezone' => 'America/New_York',
      'daterange' => '2024-01-15T10:00:00',
      'daterange__end' => '2024-01-15T12:00:00',
      'daterange__timezone' => 'UTC',
      'entity_reference' => $articles[1],
      'file' => $files[1],
      'image' => $images[1],
      'image__alt' => 'Second alt',
      'image__title' => 'Second title',
      'link' => 'https://www.drupal.org',
      'link__title' => 'Drupal',
      'link__options' => ['attributes' => ['class' => ['button']]],
      'time' => 49230,
      'time_range' => 36000,
      'time_range__end' => 43200,
      'viewfield' => 'custom_field_test',
      'viewfield__display' => 'block_1',
      'viewfield__arguments' => 'second',
      'viewfield__items' => 5,
      'duration' => 604800,
    ],
    ]);
    $this->assertNoViolations($node);
    $node->save();

    // Integer-backed columns read back as strings. Both durations are derived
    // on save from the start and end, and the image dimensions from the
    // second image file, so none of the first round's values can remain.
    $expected = [
      'string' => 'second string',
      'string_long' => "Second long text.\n\nWith a second paragraph.",
      'integer' => '42',
      'decimal' => '18.20',
      'float' => '2.5',
      'email' => 'second@example.com',
      'telephone' => '+41791234567',
      'uri' => 'https://www.drupal.com',
      'boolean' => '1',
      'uuid' => '9a8b7c6d-5e4f-4a3b-9c2d-1e0f9a8b7c6d',
      'color' => '#5BCEFA',
      'map' => [['key' => 'k2', 'value' => 'v2'], ['key' => 'k3', 'value' => 'v3']],
      'map_string' => ['two', 'three'],
      'datetime' => '2014-01-01T20:00:00',
      'datetime__timezone' => 'America/New_York',
      'daterange' => '2024-01-15T10:00:00',
      'daterange__timezone' => 'UTC',
      'daterange__end' => '2024-01-15T12:00:00',
      'daterange__duration' => '7200',
      'entity_reference' => $articles[1],
      'file' => $files[1],
      'image' => $images[1],
      'image__width' => '360',
      'image__height' => '240',
      'image__alt' => 'Second alt',
      'image__title' => 'Second title',
      'link' => 'https://www.drupal.org',
      'link__title' => 'Drupal',
      'link__options' => ['attributes' => ['class' => ['button']]],
      'time' => '49230',
      'time_range' => '36000',
      'time_range__end' => '43200',
      'time_range__duration' => '7200',
      'viewfield' => 'custom_field_test',
      'viewfield__display' => 'block_1',
      'viewfield__arguments' => 'second',
      'viewfield__items' => '5',
      'duration' => '604800',
    ];

    $item = $this->storedItem($node, self::FIELD);
    $this->assertNotNull($item);
    $stored_columns = array_keys((array) $item->getValue());
    $this->assertEqualsCanonicalizing(array_keys($expected), $stored_columns, 'Every stored column is written and asserted.');
    foreach ($stored_columns as $column) {
      $this->assertSame($expected[$column], $item->get($column)->getValue(), sprintf('Column "%s".', $column));
    }
  }

  /**
   * Saves an article node and returns its id as storage reads it back.
   */
  private function savedArticleId(string $title): string {
    $node = Node::create(['type' => 'article', 'title' => $title]);
    $node->save();
    $this->assertNotNull($node->id());

    return (string) $node->id();
  }

  /**
   * Saves a permanent file entity and returns its id as storage reads it back.
   *
   * @param string $uri
   *   The destination URI.
   * @param string|null $source
   *   An image to copy, relative to the Drupal root, or NULL for a text file.
   */
  private function savedFileId(string $uri, ?string $source = NULL): string {
    if ($source === NULL) {
      file_put_contents($uri, 'Fixture text.');
    }
    else {
      copy($this->root . '/' . $source, $uri);
    }
    $file = File::create(['uri' => $uri]);
    $file->setPermanent();
    $file->save();
    $this->assertNotNull($file->id());

    return (string) $file->id();
  }

}
