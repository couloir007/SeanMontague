<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Kernel\CustomField\Type;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\custom_field\Plugin\CustomField\FieldType\ImageType;
use Drupal\custom_field\Plugin\DataType\CustomFieldImage;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\Tests\custom_field\Kernel\CustomField\CustomFieldTypeKernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the 'image' custom field sub-field type.
 *
 * The core counterpart is \Drupal\Tests\image\Kernel\ImageItemTest, and this
 * class follows its axes: storing a file id with its alt and title, deriving
 * the width and height from the file, changing the target, and sample
 * generation. The type extends the file sub-field type, so everything
 * FileTypeTest covers about the id, the computed file and file usage is
 * pinned again here on this type's own data type. Four structural differences
 * drive everything that looks different here.
 *
 * 1. The file id lives in the sub-field's main column ('sub'), and alt,
 *    title, width and height are sibling columns ('sub__alt' and friends),
 *    where core has properties of its own field item. The file entity is a
 *    computed sibling ('sub__entity').
 * 2. The id property is the 'custom_field_image' data type, whose setValue()
 *    also accepts the nested shape ['target_id', 'alt', 'title', 'width',
 *    'height'] and writes the parts onto the siblings.
 * 3. The width and height are derived by CustomItem::preSave(), not by the
 *    type, and only when either is empty.
 * 4. All four siblings are internal properties; core's alt and title are not.
 *
 * What is deliberately NOT covered here, to avoid duplicating coverage that
 * already exists: the upload widget, its alt/title requirement and its
 * resolution check (the Functional and FunctionalJavascript ImageWidgetTest),
 * the formatters (the Functional formatter tests), and the default
 * widget/formatter wiring (TypeRegistryTest).
 *
 * Divergences from core's behavior are called out explicitly where they
 * occur, and are never "fixed" toward core in this characterization-first
 * test class.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 * @covers \Drupal\custom_field\Plugin\CustomField\FieldType\ImageType
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
#[CoversClass(ImageType::class)]
class ImageTypeTest extends CustomFieldTypeKernelTestBase {

  use UserCreationTrait;

  /**
   * The width of the first image, in pixels.
   */
  protected const IMAGE_WIDTH = 88;

  /**
   * The height of the first image, in pixels.
   */
  protected const IMAGE_HEIGHT = 100;

  /**
   * The width of the second image, in pixels.
   */
  protected const SECOND_IMAGE_WIDTH = 360;

  /**
   * The height of the second image, in pixels.
   */
  protected const SECOND_IMAGE_HEIGHT = 240;

  /**
   * A permanent public PNG image, 88x100.
   */
  private FileInterface $file;

  /**
   * A second permanent public PNG image, 360x240.
   */
  private FileInterface $secondFile;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file',
    'image',
  ];

  /**
   * {@inheritdoc}
   *
   * A private files directory, so a private-scheme column can be exercised.
   */
  protected function setUpFilesystem(): void {
    parent::setUpFilesystem();
    $private = $this->siteDirectory . '/private';
    mkdir($private, 0775, TRUE);
    $this->setSetting('file_private_path', $private);
  }

  /**
   * {@inheritdoc}
   *
   * The tests run as an ordinary authenticated user with the 'access content'
   * permission, the way content is normally saved, and the fixture files are
   * owned by that user. Re-saving a node as such a user writes node grants,
   * which needs the node_access table.
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('node', ['node_access']);
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
    $this->installConfig(['file', 'image']);
    $this->setUpCurrentUser([], ['access content']);

    $this->file = $this->createImage('public://first.png', 'core/misc/druplicon.png');
    $this->secondFile = $this->createImage('public://second.png', 'core/tests/fixtures/files/image-1.png');
  }

  /**
   * {@inheritdoc}
   */
  protected static function typeId(): string {
    return 'image';
  }

  /**
   * {@inheritdoc}
   *
   * 'target_type' is a required column key: propertyDefinitions()
   * unpacks it unguarded. The field UI always stores 'file' for this
   * type. 'uri_scheme' is read by sample generation.
   */
  protected function defaultColumn(string $name): array {
    return parent::defaultColumn($name) + [
      'target_type' => 'file',
      'uri_scheme' => 'public',
    ];
  }

  /**
   * {@inheritdoc}
   *
   * The allowed extensions and the alt requirement are restated here rather
   * than read from the shipped test config, so the settings this class runs
   * under are visible in it. The alt requirement is enforced by the widget
   * only, so it has no effect at this level.
   */
  protected function defaultFieldSettings(): array {
    return [
      'file_extensions' => 'png gif jpg jpeg',
      'alt_field_required' => 1,
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   *
   * Every expectation reads the stored item through
   * FieldItemInterface::get(), so the id and the dimensions come back as the
   * strings the database returns. The first row is what the first case of the
   * retired test plugin stored; the others are the input shapes
   * CustomFieldImage::setValue() accepts, and a private-scheme column.
   */
  protected function roundTripCases(): array {
    $id = (string) $this->file->id();
    $dimensions = [
      'sub__width' => (string) self::IMAGE_WIDTH,
      'sub__height' => (string) self::IMAGE_HEIGHT,
    ];

    return [
      'an image id with alt and title round-trips' => [
        'value' => ['sub' => (int) $this->file->id(), 'sub__alt' => 'Alt text', 'sub__title' => 'Title text'],
        'expected' => ['sub' => $id, 'sub__alt' => 'Alt text', 'sub__title' => 'Title text'] + $dimensions,
      ],
      'the nested shape is written onto the siblings' => [
        'value' => ['sub' => ['target_id' => $this->file->id(), 'alt' => 'Nested alt', 'title' => 'Nested title']],
        'expected' => ['sub' => $id, 'sub__alt' => 'Nested alt', 'sub__title' => 'Nested title'] + $dimensions,
      ],
      'a bare id derives the dimensions and leaves alt and title empty' => [
        'value' => ['sub' => (int) $this->file->id()],
        'expected' => ['sub' => $id, 'sub__alt' => NULL, 'sub__title' => NULL] + $dimensions,
      ],
      'a UUID is resolved to the id' => [
        'value' => ['sub' => $this->file->uuid()],
        'expected' => ['sub' => $id] + $dimensions,
      ],
      'an image id round-trips on a private-scheme column' => [
        'column' => ['uri_scheme' => 'private'],
        'value' => ['sub' => (int) $this->secondFile->id()],
        'expected' => [
          'sub' => (string) $this->secondFile->id(),
          'sub__width' => (string) self::SECOND_IMAGE_WIDTH,
          'sub__height' => (string) self::SECOND_IMAGE_HEIGHT,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   *
   * Empty, and that emptiness is checked rather than trusted: the type's one
   * property-level constraint is listed in ::unreachableConstraints().
   */
  protected function validationCases(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function unreachableConstraints(): array {
    return [
      [
        'property' => 'sub__entity',
        'constraint' => 'EntityType',
        'reason' => 'The computed entity is always loaded from file storage, and assigning an entity to it is ignored, so its value can never be of another entity type.',
        'test' => 'testEntityTypeConstraintCannotBeTriggered',
      ],
    ];
  }

  /**
   * The columns: id, width, height, alt and title, with no index or key.
   *
   * The alt and title lengths match core's. One divergence from core,
   * deliberate here and not "fixed": core's ImageItem::schema() indexes
   * 'target_id' and declares a foreign key to 'file_managed'.
   */
  public function testSchema(): void {
    $this->assertSame([
      'sub' => [
        'description' => 'The ID of the file entity.',
        'type' => 'int',
        'unsigned' => TRUE,
      ],
      'sub__width' => [
        'description' => 'The width of the image in pixels.',
        'type' => 'int',
        'unsigned' => TRUE,
      ],
      'sub__height' => [
        'description' => 'The height of the image in pixels.',
        'type' => 'int',
        'unsigned' => TRUE,
      ],
      'sub__alt' => [
        'description' => "Alternative image text, for the image's 'alt' attribute.",
        'type' => 'varchar',
        'length' => 512,
      ],
      'sub__title' => [
        'description' => "Image title text, for the image's 'title' attribute.",
        'type' => 'varchar',
        'length' => 1024,
      ],
    ], ImageType::schema(['name' => 'sub', 'target_type' => 'file']));

    $field_name = $this->createCaseField([], 'schema');
    $storage = FieldStorageConfig::loadByName('node', $field_name);
    $this->assertNotNull($storage);
    $schema = $storage->getSchema();
    $this->assertSame([], $schema['indexes']);
    $this->assertSame([], $schema['foreign keys']);
  }

  /**
   * The property definitions: the id, four internal siblings, the entity.
   *
   * One divergence from core: core's alt and title are public properties of
   * the field item, while all four siblings here are internal.
   */
  public function testPropertyDefinitions(): void {
    $properties = ImageType::propertyDefinitions(['name' => 'sub', 'target_type' => 'file']);

    $this->assertSame(
      ['sub', 'sub__width', 'sub__height', 'sub__alt', 'sub__title', 'sub__entity'],
      array_keys($properties),
    );

    $this->assertSame('custom_field_image', $properties['sub']->getDataType());
    $this->assertSame('sub ID', (string) $properties['sub']->getLabel());
    $this->assertFalse($properties['sub']->isRequired());
    $this->assertFalse($properties['sub']->isInternal());
    $this->assertSame(['unsigned' => TRUE, 'target_type' => 'file'], $properties['sub']->getSettings());

    $siblings = [
      'sub__width' => ['integer', 'sub width'],
      'sub__height' => ['integer', 'sub height'],
      'sub__alt' => ['string', 'sub alternative text'],
      'sub__title' => ['string', 'sub title'],
    ];
    foreach ($siblings as $name => [$data_type, $label]) {
      $this->assertSame($data_type, $properties[$name]->getDataType(), $name);
      $this->assertSame($label, (string) $properties[$name]->getLabel(), $name);
      $this->assertTrue($properties[$name]->isInternal(), $name . ' is internal.');
      $this->assertFalse($properties[$name]->isComputed(), $name);
    }

    $entity = $properties['sub__entity'];
    $this->assertSame('entity_reference', $entity->getDataType());
    $this->assertTrue($entity->isComputed());
    // Writable in its definition, although writing to it does nothing; see
    // ::testAssigningTheComputedFileIsIgnored().
    $this->assertFalse($entity->isReadOnly());
    $this->assertSame(
      'Drupal\custom_field\Plugin\CustomField\EntityReferenceComputed',
      ltrim((string) $entity->getClass(), '\\'),
    );
    $this->assertSame(['target_id' => 'sub', 'target_type' => 'file'], $entity->getSettings());
    $this->assertSame('File', (string) $entity->getLabel());
    $this->assertSame(['EntityType' => 'file'], $entity->toArray()['constraints'] ?? []);
  }

  /**
   * The extra property names are the four stored siblings.
   *
   * The computed entity has no column, so it is not one of them.
   */
  public function testExtraPropertyNamesAreExact(): void {
    $this->assertSame(
      ['sub__width', 'sub__height', 'sub__alt', 'sub__title'],
      ImageType::getExtraPropertyNames(['name' => 'sub', 'target_type' => 'file']),
    );
  }

  /**
   * The declared default field settings: the file set plus seven image keys.
   *
   * The image defaults replace the file type's allowed extensions.
   */
  public function testDefaultFieldSettings(): void {
    $this->assertSame([
      'file_extensions' => 'png gif jpg jpeg webp',
      'alt_field' => 1,
      'alt_field_required' => 1,
      'title_field' => 0,
      'title_field_required' => 0,
      'max_resolution' => '',
      'min_resolution' => '',
      'file_directory' => '[date:custom:Y]-[date:custom:m]',
      'max_filesize' => '',
      'label' => '',
      'required' => FALSE,
      'translatable' => FALSE,
      'description' => '',
      'description_display' => 'after',
      'check_empty' => FALSE,
    ], ImageType::defaultFieldSettings());
  }

  /**
   * The upload validators are the file type's, with the image extensions.
   */
  public function testUploadValidatorsUseTheImageExtensions(): void {
    $validators = $this->typeInstance(['file_extensions' => 'png gif jpg jpeg webp'])->getUploadValidators();

    $this->assertSame(['FileSizeLimit', 'FileExtension'], array_keys($validators));
    $this->assertSame(['extensions' => 'png gif jpg jpeg webp'], $validators['FileExtension']);
  }

  /**
   * CHARACTERIZATION: the EntityType constraint can never be violated.
   *
   * Every way of putting an entity of another type into the computed
   * property is a no-op: the magic setter and the property-level setValue()
   * both leave the stored id alone, and the computed property keeps loading
   * by that id from file storage. So the constraint has nothing to reject,
   * and this test is the proof behind the declaration in
   * ::unreachableConstraints().
   */
  public function testEntityTypeConstraintCannotBeTriggered(): void {
    $user = User::create(['name' => 'not a file']);
    $user->save();
    $field_name = $this->createCaseField([], 'et');
    $node = Node::create(['type' => 'page', 'title' => 'entity type']);
    $node->set($field_name, [['sub' => $this->file->id()]]);
    $item = $this->itemAt($node->get($field_name));

    $this->setRawValue($item, 'sub__entity', $user);
    $this->assertSame((string) $this->file->id(), (string) $this->rawValue($item, 'sub'));
    $this->assertSame($this->file->id(), $this->referencedFile($item)->id());
    $this->assertNoViolations($node, 'after the magic setter');

    $item->get('sub__entity')->setValue($user);
    $this->assertSame($this->file->id(), $this->referencedFile($item)->id());
    $this->assertCount(0, $item->get('sub__entity')->validate());
    $this->assertNoViolations($node, 'after the property-level setValue()');
  }

  /**
   * CONTRACT: the stored id resolves to the image file.
   *
   * Replaces the old shared matrix's per-type check: the id is a known
   * fixture file, the computed entity carries that file's id, UUID and URI,
   * and the stored dimensions are the ones the image toolkit reads from the
   * file.
   */
  public function testReferencedImageResolvesThroughTheComputedProperty(): void {
    $field_name = $this->createCaseField([], 'resolve');
    $node = $this->createHost($field_name, ['sub' => $this->file->id(), 'sub__alt' => 'Alt text']);

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item);
    $this->assertSame((string) $this->file->id(), $item->get('sub')->getValue());
    $file = $this->referencedFile($item);
    $this->assertSame($this->file->id(), $file->id());
    $this->assertSame($this->file->uuid(), $file->uuid());
    $this->assertSame('public://first.png', $file->getFileUri());

    $image = $this->container->get('image.factory')->get('public://first.png');
    $this->assertSame((string) $image->getWidth(), $item->get('sub__width')->getValue());
    $this->assertSame((string) $image->getHeight(), $item->get('sub__height')->getValue());

    // The data type's own accessors agree, and return the dimensions as
    // integers.
    $main = $this->mainProperty($item);
    $this->assertSame($this->file->id(), $main->getEntity()?->id());
    $this->assertSame(self::IMAGE_WIDTH, $main->getWidth());
    $this->assertSame(self::IMAGE_HEIGHT, $main->getHeight());
    $this->assertSame('Alt text', $main->getAlt());
    $this->assertNull($main->getTitle());
  }

  /**
   * The dimensions are derived from the file whenever either one is empty.
   *
   * A submitted pair is kept, as core keeps it. A zero counts as empty, and so
   * does a single submitted dimension: both are re-derived from the file.
   * Core re-derives only when a dimension is NULL.
   *
   * @param array<string, int> $submitted
   *   The dimensions written alongside the id.
   * @param int $width
   *   The expected stored width.
   * @param int $height
   *   The expected stored height.
   *
   * @dataProvider providerDimensions
   */
  #[DataProvider('providerDimensions')]
  public function testDimensionsAreDerivedWhenEitherIsEmpty(array $submitted, int $width, int $height): void {
    $field_name = $this->createCaseField([], 'dim_' . substr(md5(serialize($submitted)), 0, 8));
    $node = $this->createHost($field_name, ['sub' => $this->file->id()] + $submitted);

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item);
    $this->assertSame((string) $width, $item->get('sub__width')->getValue());
    $this->assertSame((string) $height, $item->get('sub__height')->getValue());
  }

  /**
   * Data provider for ::testDimensionsAreDerivedWhenEitherIsEmpty().
   *
   * @return array<string, array{array<string, int>, int, int}>
   *   Test cases.
   */
  public static function providerDimensions(): array {
    return [
      'none submitted' => [[], self::IMAGE_WIDTH, self::IMAGE_HEIGHT],
      'both submitted are kept' => [['sub__width' => 7, 'sub__height' => 8], 7, 8],
      'both zero are re-derived' => [['sub__width' => 0, 'sub__height' => 0], self::IMAGE_WIDTH, self::IMAGE_HEIGHT],
      'one submitted is re-derived' => [['sub__width' => 500], self::IMAGE_WIDTH, self::IMAGE_HEIGHT],
    ];
  }

  /**
   * A second image derives its own dimensions.
   */
  public function testSecondImageDerivesItsOwnDimensions(): void {
    $field_name = $this->createCaseField([], 'second');
    $node = $this->createHost($field_name, ['sub' => $this->secondFile->id()]);

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item);
    $this->assertSame((string) self::SECOND_IMAGE_WIDTH, $item->get('sub__width')->getValue());
    $this->assertSame((string) self::SECOND_IMAGE_HEIGHT, $item->get('sub__height')->getValue());
  }

  /**
   * A file the image toolkit cannot read is stored with no dimensions.
   */
  public function testNonImageFileGetsNoDimensions(): void {
    file_put_contents('public://notes.txt', 'Not an image.');
    $text = File::create(['uri' => 'public://notes.txt', 'status' => 1]);
    $text->save();
    $field_name = $this->createCaseField(['settings' => ['file_extensions' => 'png txt']], 'text');
    $node = $this->createHost($field_name, ['sub' => $text->id()]);

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item);
    $this->assertSame((string) $text->id(), $item->get('sub')->getValue());
    $this->assertNull($item->get('sub__width')->getValue());
    $this->assertNull($item->get('sub__height')->getValue());
  }

  /**
   * CONTRACT: changing the image before the first read is followed.
   *
   * Core's ImageItemTest makes the same change to a second image. The stored
   * id always follows; the computed entity follows only if nothing read it
   * before the change, see ::testCachedFileGoesStaleAfterTheTargetChanges().
   *
   * The stored dimensions do NOT follow: they are not empty, so they are not
   * re-derived, and the item keeps the first image's dimensions against the
   * second image's id. Core's image item does exactly the same.
   */
  public function testChangingTheImageBeforeTheFirstReadIsFollowed(): void {
    $field_name = $this->createCaseField([], 'retarget');
    $node = $this->createHost($field_name, ['sub' => $this->file->id()]);

    $host = $this->reloadedNode($node);
    $item = $this->itemAt($host->get($field_name));
    $item->get('sub')->setValue($this->secondFile->id());
    $this->assertSame($this->secondFile->id(), $this->referencedFile($item)->id());

    $host->save();
    $stored = $this->storedItem($node, $field_name);
    $this->assertNotNull($stored);
    $this->assertSame((string) $this->secondFile->id(), $stored->get('sub')->getValue());
    $this->assertSame('public://second.png', $this->referencedFile($stored)->getFileUri());
    $this->assertSame((string) self::IMAGE_WIDTH, $stored->get('sub__width')->getValue(), 'The first image width is kept.');
    $this->assertSame((string) self::IMAGE_HEIGHT, $stored->get('sub__height')->getValue(), 'The first image height is kept.');
  }

  /**
   * Replacing the whole item re-derives the dimensions of the new image.
   *
   * Setting the list resets every sibling, so the new image's dimensions are
   * empty and derived again. Contrast
   * ::testChangingTheImageBeforeTheFirstReadIsFollowed().
   */
  public function testReplacingTheItemDerivesTheNewDimensions(): void {
    $field_name = $this->createCaseField([], 'replace');
    $node = $this->createHost($field_name, ['sub' => $this->file->id()]);

    $host = $this->reloadedNode($node);
    $host->set($field_name, [['sub' => $this->secondFile->id()]]);
    $host->save();

    $stored = $this->storedItem($node, $field_name);
    $this->assertNotNull($stored);
    $this->assertSame((string) self::SECOND_IMAGE_WIDTH, $stored->get('sub__width')->getValue());
    $this->assertSame((string) self::SECOND_IMAGE_HEIGHT, $stored->get('sub__height')->getValue());
  }

  /**
   * CHARACTERIZATION: once read, the computed file ignores id changes.
   *
   * The computed property loads the file once and never resets it, although
   * the image data type resets its own cached entity on a change. After one
   * read, changing the id leaves the computed property returning the old
   * file, while the stored id and the data type's own getEntity() follow the
   * change. Core's computed 'entity' follows a changed target_id. A future fix
   * changes these expectations.
   *
   * @param string $path
   *   The write path under test.
   *
   * @dataProvider providerWritePaths
   */
  #[DataProvider('providerWritePaths')]
  public function testCachedFileGoesStaleAfterTheTargetChanges(string $path): void {
    $field_name = $this->createCaseField([], 'stale_' . substr(md5($path), 0, 8));
    $node = $this->createHost($field_name, ['sub' => $this->file->id()]);
    $host = $this->reloadedNode($node);
    $items = $host->get($field_name);
    $item = $this->itemAt($items);
    $this->assertSame($this->file->id(), $this->referencedFile($item)->id());

    $new_id = $this->secondFile->id();
    match ($path) {
      'magic setter' => $this->setRawValue($item, 'sub', $new_id),
      'property setValue' => $item->get('sub')->setValue($new_id),
      'item setValue' => $item->setValue(['sub' => $new_id]),
      'list set' => $items->set(0, ['sub' => $new_id]),
      default => $this->fail(sprintf('Unknown write path "%s".', $path)),
    };

    $item = $this->itemAt($host->get($field_name));
    $this->assertSame((string) $new_id, (string) $item->get('sub')->getValue());
    $this->assertSame($new_id, $this->mainProperty($item)->getEntity()?->id());
    $this->assertSame($this->file->id(), $this->referencedFile($item)->id(), 'The computed file is still the old one.');
  }

  /**
   * Data provider for ::testCachedFileGoesStaleAfterTheTargetChanges().
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
   * CHARACTERIZATION: assigning a file to the computed property is ignored.
   *
   * Core's image item updates 'target_id' when 'entity' is assigned. Here the
   * assignment changes nothing, and the original image is what a save stores.
   * A future fix changes these expectations.
   */
  public function testAssigningTheComputedFileIsIgnored(): void {
    $field_name = $this->createCaseField([], 'assign');
    $node = $this->createHost($field_name, ['sub' => $this->file->id()]);
    $host = $this->reloadedNode($node);
    $item = $this->itemAt($host->get($field_name));

    $this->setRawValue($item, 'sub__entity', $this->secondFile);
    $this->assertSame((string) $this->file->id(), (string) $item->get('sub')->getValue());
    $this->assertSame($this->file->id(), $this->referencedFile($item)->id());

    $host->save();
    $stored = $this->storedItem($node, $field_name);
    $this->assertNotNull($stored);
    $this->assertSame((string) $this->file->id(), $stored->get('sub')->getValue());
  }

  /**
   * CHARACTERIZATION: a new image entity is saved on first READ.
   *
   * Core's ImageItemTest assigns an unsaved file entity and expects it to be
   * saved with the host. Here the assignment stores the raw nested array, and
   * the file is saved the first time the id property object is instantiated,
   * which a plain get() read does, even if the host is never saved. The alt
   * text in the same array is applied at that point. A future fix changes
   * these expectations.
   */
  public function testNewImageIsSavedWhenThePropertyIsFirstRead(): void {
    $field_name = $this->createCaseField([], 'read_save');
    copy($this->root . '/core/misc/druplicon.png', 'public://new.png');
    $new = File::create(['uri' => 'public://new.png']);
    $node = Node::create(['type' => 'page', 'title' => 'never saved']);
    $node->set($field_name, [['sub' => ['entity' => $new, 'alt' => 'New alt']]]);
    $item = $this->itemAt($node->get($field_name));

    $this->assertTrue($new->isNew(), 'Assigning does not save the file.');
    $this->assertIsArray($this->rawValue($item, 'sub'));

    $this->assertNotNull($item->get('sub')->getValue());
    $this->assertFalse($new->isNew(), 'Reading the property saved the file.');
    $this->assertSame((string) $new->id(), (string) $item->get('sub')->getValue());
    $this->assertSame('New alt', $item->get('sub__alt')->getValue());
    $this->assertTrue($node->isNew(), 'The host was never saved.');
  }

  /**
   * CHARACTERIZATION: a failed save of a new image entity is a fatal error.
   *
   * When saving the new file throws, the image data type catches the storage
   * exception, discards the entity and then reads its id, so the read ends in
   * an \Error. The file sub-field type stores NULL silently in the same
   * situation. A future fix changes this expectation.
   */
  public function testFailedSaveOfNewImageIsAnError(): void {
    $field_name = $this->createCaseField([], 'fail_save');
    copy($this->root . '/core/misc/druplicon.png', 'public://bad.png');
    // A filename longer than the column allows makes the save throw.
    $new = File::create(['uri' => 'public://bad.png', 'filename' => str_repeat('x', 300)]);
    $node = Node::create(['type' => 'page', 'title' => 'failed save']);
    $node->set($field_name, [['sub' => ['entity' => $new]]]);
    $item = $this->itemAt($node->get($field_name));

    $this->expectException(\Error::class);
    $this->expectExceptionMessage('Call to a member function id() on null');
    $item->get('sub');
  }

  /**
   * CHARACTERIZATION: a nonexistent file id is stored as given.
   *
   * The computed entity resolves to NULL, and there are no dimensions.
   */
  public function testMissingFileIdIsStored(): void {
    $field_name = $this->createCaseField([], 'missing');
    $node = Node::create(['type' => 'page', 'title' => 'missing file']);
    $node->set($field_name, [['sub' => 999]]);
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item);
    $this->assertSame('999', $item->get('sub')->getValue());
    $this->assertNull($this->rawValue($item, 'sub__entity'));
    $this->assertNull($item->get('sub__width')->getValue());
    $this->assertNull($item->get('sub__height')->getValue());
  }

  /**
   * CHARACTERIZATION: malformed ids are stored converted.
   *
   * The integer column converts the value on save.
   *
   * @param string $value
   *   The id to write.
   * @param string $stored
   *   The value read back.
   *
   * @dataProvider providerMalformedIds
   */
  #[DataProvider('providerMalformedIds')]
  public function testMalformedIdsAreStoredConverted(string $value, string $stored): void {
    $field_name = $this->createCaseField([], 'bad_' . substr(md5($value), 0, 8));
    $node = Node::create(['type' => 'page', 'title' => 'malformed id']);
    $node->set($field_name, [['sub' => $value]]);
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item);
    $this->assertSame($stored, $item->get('sub')->getValue());
  }

  /**
   * Data provider for ::testMalformedIdsAreStoredConverted().
   *
   * @return array<string, array{string, string}>
   *   Test cases.
   */
  public static function providerMalformedIds(): array {
    return [
      'a non-numeric string is stored as 0' => ['abc', '0'],
      'a decimal string is stored truncated' => ['1.5', '1'],
    ];
  }

  /**
   * CHARACTERIZATION: a negative id is rejected by the database on save.
   *
   * The id column is unsigned, so the error surfaces as a storage exception.
   */
  public function testNegativeIdIsRejectedByTheDatabase(): void {
    $field_name = $this->createCaseField([], 'negative');
    $node = Node::create(['type' => 'page', 'title' => 'negative id']);
    $node->set($field_name, [['sub' => -1]]);

    $this->expectException(EntityStorageException::class);
    $node->save();
  }

  /**
   * CHARACTERIZATION: a deleted image's id is kept, and loaded items keep it.
   *
   * An item that had already loaded the file keeps returning it after the
   * file is deleted; a fresh load resolves to NULL; and the dangling id is
   * re-saved with the host, together with its alt text and dimensions.
   */
  public function testDeletedImageIdIsKept(): void {
    $field_name = $this->createCaseField([], 'deleted');
    $node = $this->createHost($field_name, ['sub' => $this->file->id(), 'sub__alt' => 'Alt text']);
    $file_id = $this->file->id();

    $loaded = $this->reloadedNode($node);
    $this->assertSame($file_id, $this->referencedFile($this->itemAt($loaded->get($field_name)))->id());

    $this->file->delete();

    $this->assertSame($file_id, $this->referencedFile($this->itemAt($loaded->get($field_name)))->id(), 'An already-loaded item still returns the deleted file.');

    $fresh = $this->reloadedNode($node);
    $fresh_item = $this->itemAt($fresh->get($field_name));
    $this->assertSame((string) $file_id, $fresh_item->get('sub')->getValue());
    $this->assertNull($this->rawValue($fresh_item, 'sub__entity'));

    $fresh->save();
    $stored = $this->storedItem($node, $field_name);
    $this->assertNotNull($stored);
    $this->assertSame((string) $file_id, $stored->get('sub')->getValue());
    $this->assertSame('Alt text', $stored->get('sub__alt')->getValue());
    $this->assertSame((string) self::IMAGE_WIDTH, $stored->get('sub__width')->getValue());
  }

  /**
   * CONTRACT: file usage follows the host, and a referenced file is kept.
   *
   * Insert adds one usage, and a temporary file becomes permanent; replacing
   * the image moves the usage; deleting the host removes it. The counts match
   * a core image field's, under 'custom_field' rather than core's 'file'.
   */
  public function testFileUsageFollowsTheHostLifecycle(): void {
    $temporary = $this->createImage('public://temporary.png', 'core/misc/druplicon.png', FALSE);
    $field_name = $this->createCaseField([], 'usage');
    $node = $this->createHost($field_name, ['sub' => $temporary->id()]);
    $nid = (string) $node->id();
    $this->assertSame(['custom_field' => ['node' => [$nid => '1']]], $this->usage($temporary));
    $reloaded = File::load($temporary->id());
    $this->assertInstanceOf(FileInterface::class, $reloaded);
    $this->assertTrue($reloaded->isPermanent());

    $host = $this->reloadedNode($node);
    $host->set($field_name, [['sub' => $this->secondFile->id()]]);
    $host->save();
    $this->assertSame([], $this->usage($temporary));
    $this->assertSame(['custom_field' => ['node' => [$nid => '1']]], $this->usage($this->secondFile));

    $this->reloadedNode($node)->delete();
    $this->assertSame([], $this->usage($this->secondFile));
  }

  /**
   * The check_empty matrix, with a sibling column to keep the row alive.
   *
   * Emptiness is NULL or the empty string only, so a zero id keeps the item
   * even with check_empty on. With check_empty off, an empty string is stored
   * as 0 by the integer column. Whenever the id is empty, the alt text written
   * alongside it is cleared.
   *
   * @param bool $check_empty
   *   The sub-field's check_empty setting.
   * @param int|string|null $value
   *   The id to assign.
   * @param string|null $stored
   *   The stored id.
   * @param bool $kept
   *   Whether the item survives the save.
   *
   * @dataProvider providerCheckEmpty
   */
  #[DataProvider('providerCheckEmpty')]
  public function testCheckEmpty(bool $check_empty, int|string|null $value, ?string $stored, bool $kept): void {
    $field_name = 'field_sub_ce_' . substr(md5(serialize([$check_empty, $value])), 0, 8);
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

    $node = Node::create(['type' => 'page', 'title' => 'check_empty ' . $field_name]);
    $node->set($field_name, [['sub' => $value, 'sub__alt' => 'Alt text', 'keep' => 'populated']]);
    $node->save();

    $item = $this->storedItem($node, $field_name);
    if (!$kept) {
      $this->assertNull($item, 'The item was dropped as empty.');
      return;
    }
    $this->assertNotNull($item, 'The item was kept.');
    $this->assertSame($stored, $item->get('sub')->getValue());
    $this->assertNull($item->get('sub__alt')->getValue());
  }

  /**
   * Data provider for ::testCheckEmpty().
   *
   * @return array<string, array{bool, int|string|null, string|null, bool}>
   *   Test cases.
   */
  public static function providerCheckEmpty(): array {
    return [
      'check_empty on, NULL' => [TRUE, NULL, NULL, FALSE],
      'check_empty on, empty string' => [TRUE, '', NULL, FALSE],
      'check_empty on, zero' => [TRUE, 0, '0', TRUE],
      'check_empty on, string zero' => [TRUE, '0', '0', TRUE],
      'check_empty off, NULL' => [FALSE, NULL, NULL, TRUE],
      'check_empty off, empty string' => [FALSE, '', '0', TRUE],
      'check_empty off, zero' => [FALSE, 0, '0', TRUE],
      'check_empty off, string zero' => [FALSE, '0', '0', TRUE],
    ];
  }

  /**
   * An empty id clears every sibling, and keeps the item only for a sibling.
   *
   * The retired test plugin's third case wrote alt and title with no id and
   * expected all four siblings to be empty after the save.
   */
  public function testEmptyIdClearsEverySibling(): void {
    $field_name = 'field_sub_clear';
    $this->createCustomField(
      $field_name,
      [
        'sub' => $this->defaultColumn('sub'),
        'keep' => ['name' => 'keep', 'type' => 'string'],
      ],
      [
        'sub' => $this->defaultFieldSettings(),
        'keep' => ['label' => 'Populated sibling', 'check_empty' => FALSE],
      ],
    );
    $node = Node::create(['type' => 'page', 'title' => 'cleared']);
    $node->set($field_name, [[
      'sub' => NULL,
      'sub__alt' => 'Alt text',
      'sub__title' => 'Title text',
      'sub__width' => 5,
      'sub__height' => 6,
      'keep' => 'populated',
    ],
    ]);
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item);
    foreach (['sub', 'sub__alt', 'sub__title', 'sub__width', 'sub__height'] as $property) {
      $this->assertNull($item->get($property)->getValue(), $property . ' is empty.');
    }
  }

  /**
   * A generated sample is a new image in the upload location, with metadata.
   *
   * The sample is the nested shape, carrying alt, title and the image's real
   * dimensions, within the configured resolution bounds. The extension is
   * one of the allowed ones that the generator can produce. Once the host is
   * saved, the file is permanent.
   *
   * @param array<string, mixed> $case
   *   The case's 'column' and 'settings' overrides.
   * @param string $prefix
   *   The expected start of the file URI.
   * @param list<string> $extensions
   *   The extensions the generated file may have.
   * @param array{int, int, int, int}|null $bounds
   *   The expected minimum width, minimum height, maximum width and maximum
   *   height, or NULL to skip the check.
   *
   * @dataProvider providerSampleValues
   */
  #[DataProvider('providerSampleValues')]
  public function testSampleValueIsNewImageInUploadLocation(array $case, string $prefix, array $extensions, ?array $bounds): void {
    $field_name = $this->createCaseField($case, 'sample_' . substr(md5(serialize($case)), 0, 8));
    $node = Node::create(['type' => 'page', 'title' => 'sample']);
    $node->get($field_name)->generateSampleItems();

    $value = $node->get($field_name)->getValue();
    $this->assertCount(1, $value);
    $sample = $value[0]['sub'];
    $this->assertIsArray($sample);
    $this->assertSame(['target_id', 'alt', 'title', 'width', 'height'], array_keys($sample));
    $this->assertIsString($sample['alt']);
    $this->assertIsString($sample['title']);

    $file = File::load($sample['target_id']);
    $this->assertInstanceOf(FileInterface::class, $file);
    $uri = $file->getFileUri();
    $this->assertIsString($uri);
    $expected_prefix = str_replace('{Y-m}', date('Y-m'), $prefix);
    $this->assertTrue(str_starts_with($uri, $expected_prefix), sprintf('"%s" starts with "%s".', $uri, $expected_prefix));
    $this->assertContains(pathinfo($uri, PATHINFO_EXTENSION), $extensions);
    $this->assertFileExists($uri);

    $image = $this->container->get('image.factory')->get($uri);
    $this->assertSame($image->getWidth(), $sample['width']);
    $this->assertSame($image->getHeight(), $sample['height']);
    if ($bounds !== NULL) {
      [$min_width, $min_height, $max_width, $max_height] = $bounds;
      $this->assertGreaterThanOrEqual($min_width, $sample['width']);
      $this->assertGreaterThanOrEqual($min_height, $sample['height']);
      $this->assertLessThanOrEqual($max_width, $sample['width']);
      $this->assertLessThanOrEqual($max_height, $sample['height']);
    }

    $node->save();
    $saved = File::load($file->id());
    $this->assertInstanceOf(FileInterface::class, $saved);
    $this->assertTrue($saved->isPermanent());
  }

  /**
   * Data provider for ::testSampleValueIsNewImageInUploadLocation().
   *
   * @return array<string, array{array<string, mixed>, string, list<string>, array{int, int, int, int}|null}>
   *   Test cases.
   */
  public static function providerSampleValues(): array {
    return [
      'the default directory, within the default bounds' => [
        [],
        'public://{Y-m}/',
        ['png', 'gif', 'jpg', 'jpeg'],
        [100, 100, 600, 600],
      ],
      'the one allowed extension' => [
        ['settings' => ['file_extensions' => 'png']],
        'public://{Y-m}/',
        ['png'],
        NULL,
      ],
      'webp is skipped when another extension is allowed' => [
        ['settings' => ['file_extensions' => 'webp png']],
        'public://{Y-m}/',
        ['png'],
        NULL,
      ],
      'the configured resolution bounds' => [
        ['settings' => ['min_resolution' => '50x40', 'max_resolution' => '60x45']],
        'public://{Y-m}/',
        ['png', 'gif', 'jpg', 'jpeg'],
        [50, 40, 60, 45],
      ],
      'a custom directory on the private scheme' => [
        [
          'column' => ['uri_scheme' => 'private'],
          'settings' => ['file_directory' => 'img/dir', 'file_extensions' => 'png'],
        ],
        'private://img/dir/',
        ['png'],
        NULL,
      ],
    ];
  }

  /**
   * CHARACTERIZATION: sample generation fails when only webp is allowed.
   *
   * The generator can only produce png, gif, jpg and jpeg, and picks from the
   * allowed extensions that are in that list. With 'webp' alone nothing is
   * left to pick from, and array_rand() throws a \ValueError. 'webp' is one
   * of the type's own default extensions. A future fix changes this
   * expectation.
   */
  public function testSampleGenerationFailsWhenOnlyWebpIsAllowed(): void {
    $field_name = $this->createCaseField(['settings' => ['file_extensions' => 'webp']], 'webp');
    $node = Node::create(['type' => 'page', 'title' => 'webp sample']);

    $this->expectException(\ValueError::class);
    $node->get($field_name)->generateSampleItems();
  }

  /**
   * The storage depends on the file module; the field adds nothing.
   *
   * The type implements no dependency methods of its own.
   */
  public function testCalculateDependencies(): void {
    $field_name = $this->createCaseField([], 'deps');

    $storage = FieldStorageConfig::loadByName('node', $field_name);
    $this->assertNotNull($storage);
    $this->assertSame(['custom_field', 'file', 'node'], $storage->getDependencies()['module']);

    $field = FieldConfig::loadByName('node', 'page', $field_name);
    $this->assertNotNull($field);
    $this->assertSame([
      'config' => ['field.storage.node.' . $field_name, 'node.type.page'],
      'module' => ['custom_field'],
    ], $field->getDependencies());
  }

  /**
   * A repeated programmatic setValue() must keep the alt and title.
   *
   * This is the JSON:API / REST / migrate / custom-code shape: the nested
   * value array with no widget promotion involved. Without the extraction
   * override the second pass resets both siblings to NULL, because core's
   * Map::setValue() re-syncs every already instantiated property object from
   * the incoming values array.
   */
  public function testProgrammaticNestedValueSurvivesRepeatedSetValue(): void {
    $field_name = $this->createCaseField([], 'resync');
    $values = [
      'sub' => [
        'target_id' => $this->file->id(),
        'alt' => 'Alt text',
        'title' => 'Title text',
      ],
    ];

    $node = Node::create(['type' => 'page', 'title' => 'Programmatic']);
    $items = $node->get($field_name);

    $items->setValue([$values]);
    $node->validate();
    $this->assertSame('Alt text', $this->itemAt($items)->get('sub__alt')->getValue(), 'Set on first pass.');

    $items->setValue([$values]);
    $this->assertSame('Alt text', $this->itemAt($items)->get('sub__alt')->getValue(), 'Survives second pass.');

    $node->save();

    $stored = $this->storedValues($node, $field_name);
    $this->assertSame((string) $this->file->id(), $stored['target_id']);
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
    $field_name = $this->createCaseField([], 'resync_dim');
    $values = [
      'sub' => [
        'target_id' => $this->file->id(),
        'alt' => 'Alt text',
        'title' => 'Title text',
      ],
    ];

    $node = Node::create(['type' => 'page', 'title' => 'Dimensions']);
    $items = $node->get($field_name);

    $items->setValue([$values]);
    $node->validate();
    $items->setValue([$values]);
    $node->save();

    $stored = $this->storedValues($node, $field_name);
    $this->assertSame((string) self::IMAGE_WIDTH, $stored['width'], 'Width was re-derived from the file.');
    $this->assertSame((string) self::IMAGE_HEIGHT, $stored['height'], 'Height was re-derived from the file.');
  }

  /**
   * Explicitly submitted dimensions are left to the data type.
   *
   * CustomFieldImage::setValue() writes submitted width and height onto the
   * siblings, and CustomItem::preSave() only re-derives them when either is
   * empty - so a caller that supplies dimensions keeps them. The extraction
   * override deliberately does not change that: leaving width and height out
   * of it means this path stores exactly what it stored before.
   *
   * CHARACTERIZATION, not contract: this pins what the code does today, and
   * does not endorse it. A caller can store dimensions that do not match the
   * referenced file, and nothing checks them. The very same values ARE
   * re-derived once the item is set twice, per
   * ::testRepeatedSetValueDerivesDimensionsAgain(), so storage today depends
   * on how many times the item was set. A future fix may legitimately make
   * this expectation wrong, for example by always re-deriving from the file.
   */
  public function testSubmittedDimensionsAreLeftToTheDataType(): void {
    $field_name = $this->createCaseField([], 'submitted');
    $node = Node::create(['type' => 'page', 'title' => 'Submitted dimensions']);
    $node->get($field_name)->setValue([
      [
        'sub' => [
          'target_id' => $this->file->id(),
          'alt' => 'Alt text',
          'width' => 4242,
          'height' => 2424,
        ],
      ],
    ]);
    $node->save();

    $stored = $this->storedValues($node, $field_name);
    $this->assertSame('4242', $stored['width'], 'The submitted width was kept.');
    $this->assertSame('2424', $stored['height'], 'The submitted height was kept.');
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
    $field_name = $this->createCaseField([], 'stale_dim');
    $values = [
      'sub' => [
        'target_id' => $this->file->id(),
        'alt' => 'Alt text',
        'width' => 4242,
        'height' => 2424,
      ],
    ];

    $node = Node::create(['type' => 'page', 'title' => 'Stale dimensions']);
    $items = $node->get($field_name);

    $items->setValue([$values]);
    $node->validate();
    $items->setValue([$values]);
    $node->save();

    $stored = $this->storedValues($node, $field_name);
    $this->assertSame('Alt text', $stored['alt'], 'The alt text survived the second pass.');
    $this->assertSame((string) self::IMAGE_WIDTH, $stored['width'], 'The stale width was re-derived.');
    $this->assertSame((string) self::IMAGE_HEIGHT, $stored['height'], 'The stale height was re-derived.');
  }

  /**
   * The node path stores the alt and title the nested value carries.
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
      $value['target_id'] = $this->file->id();
    }

    $field_name = $this->createCaseField([], 'matrix_' . substr(md5(serialize($value)), 0, 8));
    $node = Node::create(['type' => 'page', 'title' => 'Matrix ' . md5(serialize($value))]);
    $node->get($field_name)->setValue([['sub' => $value]]);
    $node->save();

    $stored = $this->storedValues($node, $field_name);
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
    $this->assertSame($expected, ImageType::extractExtraPropertyValues(['name' => 'img'], $value));
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

  /**
   * Copies an image fixture to a URI and saves a file entity for it.
   *
   * @param string $uri
   *   The destination URI.
   * @param string $source
   *   The fixture path relative to the Drupal root.
   * @param bool $permanent
   *   Whether the file entity is permanent.
   */
  private function createImage(string $uri, string $source, bool $permanent = TRUE): FileInterface {
    copy($this->root . '/' . $source, $uri);
    $file = File::create(['uri' => $uri, 'status' => (int) $permanent]);
    $file->save();

    return $file;
  }

  /**
   * Creates and saves a page host with one item.
   *
   * @param string $field_name
   *   The field to populate.
   * @param array<string, mixed> $value
   *   The item value.
   */
  private function createHost(string $field_name, array $value): Node {
    $node = Node::create(['type' => 'page', 'title' => 'host for ' . $field_name]);
    $node->set($field_name, [$value]);
    $node->save();

    return $node;
  }

  /**
   * Returns the stored id and siblings of a saved node, read back.
   *
   * @return array<string, mixed>
   *   The reloaded target_id/alt/title/width/height values, all NULL when the
   *   item was dropped on save.
   */
  private function storedValues(Node $node, string $field_name): array {
    $item = $this->storedItem($node, $field_name);

    return [
      'target_id' => $item?->get('sub')->getValue(),
      'alt' => $item?->get('sub__alt')->getValue(),
      'title' => $item?->get('sub__title')->getValue(),
      'width' => $item?->get('sub__width')->getValue(),
      'height' => $item?->get('sub__height')->getValue(),
    ];
  }

  /**
   * Returns a file's usage, read fresh from the usage backend.
   *
   * @return array<string, array<string, array<string, string>>>
   *   The usage, keyed by module, type and id.
   */
  private function usage(FileInterface $file): array {
    return $this->container->get('file.usage')->listUsage($file);
  }

  /**
   * Returns the computed entity of an item, asserted to be a file.
   */
  private function referencedFile(FieldItemInterface $item): FileInterface {
    $entity = $this->rawValue($item, 'sub__entity');
    $this->assertInstanceOf(FileInterface::class, $entity);

    return $entity;
  }

  /**
   * Returns the id property narrowed to this type's data type.
   *
   * FieldItemInterface::get() is typed to return TypedDataInterface, which
   * does not declare the image accessors.
   */
  private function mainProperty(FieldItemInterface $item): CustomFieldImage {
    $property = $item->get('sub');
    $this->assertInstanceOf(CustomFieldImage::class, $property);

    return $property;
  }

  /**
   * Returns a sibling property's value through the item's magic getter.
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
   *
   * @param array<string, mixed> $settings
   *   Field settings to apply over this class's defaults.
   */
  private function typeInstance(array $settings = []): ImageType {
    $column = $this->defaultColumn($this->columnName());
    $options = $this->typeManager->createOptionsForInstance($settings + $this->defaultFieldSettings(), $column);
    $instance = $this->typeManager->createInstance(static::typeId(), $options['configuration']);
    $this->assertInstanceOf(ImageType::class, $instance);

    return $instance;
  }

}
