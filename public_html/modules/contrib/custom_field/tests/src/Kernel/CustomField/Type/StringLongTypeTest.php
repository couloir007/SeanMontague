<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Kernel\CustomField\Type;

use Drupal\custom_field\Plugin\CustomField\FieldType\StringLongType;
use Drupal\custom_field\Plugin\DataType\CustomFieldStringLongInterface;
use Drupal\filter\Entity\FilterFormat;
use Drupal\node\Entity\Node;
use Drupal\Tests\custom_field\Kernel\CustomField\CustomFieldTypeKernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the 'string_long' sub-field type.
 *
 * Modelled on core's field type Kernel tests, closest counterpart
 * core/modules/text/tests/src/Kernel/TextWithSummaryItemTest.php.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 * @covers \Drupal\custom_field\Plugin\CustomField\FieldType\StringLongType
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
#[CoversClass(StringLongType::class)]
class StringLongTypeTest extends CustomFieldTypeKernelTestBase {

  /**
   * The id of the filter format created for the formatted cases.
   */
  protected const FORMAT_ID = 'custom_field_test_format';

  /**
   * {@inheritdoc}
   *
   * KernelTestBase::getModulesToEnable() merges $modules across the class
   * hierarchy, so only the additions to CustomFieldKernelTestBase's set belong
   * here. `filter` is required because StringLongType::create() fetches
   * FilterFormatRepositoryInterface from the container, so the plugin cannot be
   * instantiated without it.
   */
  protected static $modules = ['filter'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['filter']);

    FilterFormat::create([
      'format' => self::FORMAT_ID,
      'name' => 'Custom field test format',
      'filters' => [
        // Converts line breaks to <br /> / <p>, so processed output is
        // observably different from the raw value.
        'filter_autop' => ['status' => TRUE],
      ],
    ])->save();
  }

  /**
   * {@inheritdoc}
   */
  protected static function typeId(): string {
    return 'string_long';
  }

  /**
   * {@inheritdoc}
   */
  protected function roundTripCases(): array {
    return [
      'single line' => [
        'value' => ['sub' => 'A long string value.'],
        'expected' => ['sub' => 'A long string value.'],
      ],
      'multiple paragraphs survive intact' => [
        'value' => ['sub' => "First paragraph.\n\nSecond paragraph.\n\nThird."],
        'expected' => ['sub' => "First paragraph.\n\nSecond paragraph.\n\nThird."],
      ],
      'text far longer than a varchar column' => [
        'value' => ['sub' => str_repeat('Lorem ipsum dolor sit amet. ', 400)],
        'expected' => ['sub' => str_repeat('Lorem ipsum dolor sit amet. ', 400)],
      ],
      'markup is stored verbatim, not filtered on save' => [
        'value' => ['sub' => '<em>markup</em> & entities'],
        'expected' => ['sub' => '<em>markup</em> & entities'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function validationCases(): array {
    // Deliberately empty: string_long declares no constraints of its own and
    // inherits none — there is no length limit on a `text` column and no
    // format validation at the type level. Written down rather than omitted, so
    // the shared testValidation() skips with a message naming the type instead
    // of silently asserting nothing.
    return [];
  }

  /**
   * The schema is a big text column with no length.
   */
  public function testSchema(): void {
    $this->assertSame(
      ['sub' => ['type' => 'text', 'size' => 'big']],
      StringLongType::schema(['name' => 'sub']),
    );
  }

  /**
   * The property definition uses the module's own data type.
   */
  public function testPropertyDefinitions(): void {
    $properties = StringLongType::propertyDefinitions(['name' => 'sub']);

    $this->assertSame(['sub'], array_keys($properties));
    $this->assertSame('custom_field_string_long', $properties['sub']->getDataType());
    $this->assertFalse($properties['sub']->isRequired());
  }

  /**
   * The declared default field settings.
   */
  public function testDefaultFieldSettings(): void {
    $this->assertSame([
      'formatted' => FALSE,
      'default_format' => '',
      'format' => [
        'guidelines' => TRUE,
        'help' => TRUE,
      ],
      'label' => '',
      'required' => FALSE,
      'translatable' => FALSE,
      'description' => '',
      'description_display' => 'after',
      'check_empty' => FALSE,
    ], StringLongType::defaultFieldSettings());
  }

  /**
   * The raw value is returned unprocessed when `formatted` is off.
   */
  public function testGetProcessedReturnsRawValueWhenNotFormatted(): void {
    $property = $this->processedProperty("Line one.\n\nLine two.", FALSE, 'raw');

    $this->assertSame(
      "Line one.\n\nLine two.",
      (string) $property->getProcessed(),
      'An unformatted subfield returns its value unprocessed.',
    );
  }

  /**
   * The configured format is applied when `formatted` is on.
   */
  public function testGetProcessedAppliesTheConfiguredFormat(): void {
    $property = $this->processedProperty("Line one.\n\nLine two.", TRUE, 'fmt');

    $processed = (string) $property->getProcessed();
    $this->assertStringContainsString('<p>', $processed, 'filter_autop wrapped the text in paragraphs.');
    $this->assertNotSame(
      "Line one.\n\nLine two.",
      $processed,
      'The processed output differs from the raw value.',
    );
    $this->assertSame(
      "Line one.\n\nLine two.",
      $property->getValue(),
      'The raw stored value is unchanged by processing.',
    );
  }

  /**
   * Writing a new value invalidates the cached processed text.
   *
   * Guards the documented behavior of CustomFieldStringLong::setValue(), which
   * clears its cached FilterProcessResult so getProcessed() cannot return
   * filtered markup belonging to a superseded value.
   */
  public function testSetValueInvalidatesTheProcessedCache(): void {
    $item = $this->savedItemWithText('First value.', TRUE, 'inv');
    $property = $item->get('sub');
    $this->assertInstanceOf(CustomFieldStringLongInterface::class, $property);

    $first = (string) $property->getProcessed();
    $this->assertStringContainsString('First value.', $first);

    $item->set('sub', 'Second value.');

    $second = (string) $property->getProcessed();
    $this->assertStringContainsString('Second value.', $second);
    $this->assertStringNotContainsString('First value.', $second, 'Stale processed text was not returned.');
  }

  /**
   * Returns the saved item's sub-field property, narrowed to its interface.
   *
   * @param string $text
   *   The text to store.
   * @param bool $formatted
   *   Whether to enable the `formatted` setting with the test format.
   * @param string $suffix
   *   A per-case suffix keeping the generated field name unique.
   *
   * @return \Drupal\custom_field\Plugin\DataType\CustomFieldStringLongInterface
   *   The stored property object.
   */
  protected function processedProperty(string $text, bool $formatted, string $suffix): CustomFieldStringLongInterface {
    $property = $this->savedItemWithText($text, $formatted, $suffix)->get('sub');
    $this->assertInstanceOf(CustomFieldStringLongInterface::class, $property);

    return $property;
  }

  /**
   * Creates a field, saves a node with the given text, and returns the item.
   *
   * @param string $text
   *   The text to store.
   * @param bool $formatted
   *   Whether to enable the `formatted` setting with the test format.
   * @param string $suffix
   *   A per-case suffix keeping the generated field name unique.
   *
   * @return \Drupal\Core\Field\FieldItemInterface
   *   The stored item, read back from storage.
   */
  protected function savedItemWithText(string $text, bool $formatted, string $suffix) {
    $field_name = $this->createCaseField([
      'settings' => [
        'formatted' => $formatted,
        'default_format' => $formatted ? self::FORMAT_ID : '',
      ],
    ], $suffix);

    $node = Node::create(['type' => 'page', 'title' => 'string_long ' . $suffix]);
    $node->set($field_name, [['sub' => $text]]);
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item, 'The saved item was read back from storage.');

    return $item;
  }

}
