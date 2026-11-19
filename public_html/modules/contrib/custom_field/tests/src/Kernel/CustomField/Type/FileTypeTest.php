<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Kernel\CustomField\Type;

use Drupal\Component\Utility\Bytes;
use Drupal\Component\Utility\Environment;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\custom_field\Plugin\CustomField\FieldType\FileType;
use Drupal\custom_field\Plugin\DataType\CustomFieldEntityReference;
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
 * Tests the 'file' custom field sub-field type.
 *
 * The core counterpart is \Drupal\Tests\file\Kernel\FileItemTest, and this
 * class follows its axes: storing and resolving a file id, changing the
 * target, sample generation into the configured directory with an allowed
 * extension, and the upload location. Three structural differences drive
 * everything that looks different here.
 *
 * 1. The file id lives in the sub-field's main column ('sub'), and the file
 *    entity is a computed sibling property ('sub__entity'), where core has
 *    'target_id' and 'entity' on its own field item. The type shares the
 *    entity reference sub-field's data type and computed property class, but
 *    not its plugin class: it extends the plugin base directly.
 * 2. There is no 'display' or 'description' column, and 'uri_scheme' is a
 *    column (storage) setting.
 * 3. File usage is reconciled by the custom field's item list, under this
 *    module's name rather than core's 'file'.
 *
 * What is deliberately NOT covered here, to avoid duplicating coverage that
 * already exists: the upload widget (the Functional and FunctionalJavascript
 * FileWidgetTest, which cover uploads, the extension check on upload and the
 * settings form), the formatters (the Functional formatter tests), and the
 * default widget/formatter wiring (TypeRegistryTest).
 *
 * Divergences from core's behavior are called out explicitly where they
 * occur, and are never "fixed" toward core in this characterization-first
 * test class.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 * @covers \Drupal\custom_field\Plugin\CustomField\FieldType\FileType
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
#[CoversClass(FileType::class)]
class FileTypeTest extends CustomFieldTypeKernelTestBase {

  use UserCreationTrait;

  /**
   * A permanent public text file.
   */
  private FileInterface $file;

  /**
   * A second permanent public text file.
   */
  private FileInterface $secondFile;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file',
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
    $this->installConfig(['file']);
    $this->setUpCurrentUser([], ['access content']);

    $this->file = $this->createFile('public://first.txt');
    $this->secondFile = $this->createFile('public://second.txt');
  }

  /**
   * {@inheritdoc}
   */
  protected static function typeId(): string {
    return 'file';
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
   * The allowed extensions are restated here rather than read from the
   * shipped test config, so the setting the sample cases depend on is
   * visible in this class.
   */
  protected function defaultFieldSettings(): array {
    return [
      'file_extensions' => 'txt',
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   *
   * Every expectation reads the stored item through
   * FieldItemInterface::get(), so the id comes back as the string the
   * database returns. The first row is what both cases of the retired test
   * plugin stored; the others are the input shapes
   * CustomFieldEntityReference::setValue() unwraps, and a private-scheme
   * column.
   */
  protected function roundTripCases(): array {
    $id = (string) $this->file->id();

    return [
      'a file id round-trips' => [
        'value' => ['sub' => (int) $this->file->id()],
        'expected' => ['sub' => $id],
      ],
      'a target_id array is unwrapped to the id' => [
        'value' => ['sub' => ['target_id' => $this->file->id()]],
        'expected' => ['sub' => $id],
      ],
      'a UUID is resolved to the id' => [
        'value' => ['sub' => $this->file->uuid()],
        'expected' => ['sub' => $id],
      ],
      'a file id round-trips on a private-scheme column' => [
        'column' => ['uri_scheme' => 'private'],
        'value' => ['sub' => (int) $this->secondFile->id()],
        'expected' => ['sub' => (string) $this->secondFile->id()],
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
   * The column is an unsigned integer with no index and no foreign key.
   *
   * One divergence from core, deliberate here and not "fixed": core's
   * FileItem::schema() indexes 'target_id' and declares a foreign key to
   * 'file_managed', and also stores 'display' and 'description' columns.
   */
  public function testSchema(): void {
    $this->assertSame([
      'sub' => [
        'description' => 'The ID of the file entity.',
        'type' => 'int',
        'unsigned' => TRUE,
      ],
    ], FileType::schema(['name' => 'sub', 'target_type' => 'file']));

    $field_name = $this->createCaseField([], 'schema');
    $storage = FieldStorageConfig::loadByName('node', $field_name);
    $this->assertNotNull($storage);
    $schema = $storage->getSchema();
    $this->assertSame([], $schema['indexes']);
    $this->assertSame([], $schema['foreign keys']);
  }

  /**
   * The property definitions: the file id and the computed file entity.
   *
   * Unlike the entity reference sub-field, the id property carries no
   * 'data_type' setting.
   */
  public function testPropertyDefinitions(): void {
    $properties = FileType::propertyDefinitions(['name' => 'sub', 'target_type' => 'file']);

    $this->assertSame(['sub', 'sub__entity'], array_keys($properties));

    $this->assertSame('custom_field_entity_reference', $properties['sub']->getDataType());
    $this->assertSame('sub ID', (string) $properties['sub']->getLabel());
    $this->assertFalse($properties['sub']->isRequired());
    $this->assertSame(['unsigned' => TRUE, 'target_type' => 'file'], $properties['sub']->getSettings());

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
   * There are no extra property names: the computed entity has no column.
   */
  public function testExtraPropertyNamesAreExact(): void {
    $this->assertSame([], FileType::getExtraPropertyNames(['name' => 'sub', 'target_type' => 'file']));
  }

  /**
   * The declared default field settings: the base set plus three file keys.
   *
   * Core also declares 'description_field', which has no counterpart here.
   */
  public function testDefaultFieldSettings(): void {
    $this->assertSame([
      'file_extensions' => 'txt',
      'file_directory' => '[date:custom:Y]-[date:custom:m]',
      'max_filesize' => '',
      'label' => '',
      'required' => FALSE,
      'translatable' => FALSE,
      'description' => '',
      'description_display' => 'after',
      'check_empty' => FALSE,
    ], FileType::defaultFieldSettings());
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
   * CONTRACT: the stored id resolves to the file entity.
   *
   * Replaces the old shared matrix's per-type check: the id is a known
   * fixture file, and the computed entity carries that file's id and URI.
   */
  public function testReferencedFileResolvesThroughTheComputedProperty(): void {
    $field_name = $this->createCaseField([], 'resolve');
    $node = $this->createHost($field_name, $this->file);

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item);
    $this->assertSame((string) $this->file->id(), $item->get('sub')->getValue());
    $file = $this->referencedFile($item);
    $this->assertSame($this->file->id(), $file->id());
    $this->assertSame('public://first.txt', $file->getFileUri());
    $this->assertSame('first.txt', $file->getFilename());

    // The data type's own accessor agrees.
    $this->assertSame($this->file->id(), $this->mainProperty($item)->getEntity()?->id());
  }

  /**
   * CONTRACT: changing the file before the first read is followed.
   *
   * Core's FileItemTest makes the same change to a second file. The stored
   * value always follows; the computed entity follows only if nothing read
   * it before the change, see ::testCachedFileGoesStaleAfterTheTargetChanges().
   */
  public function testChangingTheFileBeforeTheFirstReadIsFollowed(): void {
    $field_name = $this->createCaseField([], 'retarget');
    $node = $this->createHost($field_name, $this->file);

    $host = $this->reloadedNode($node);
    $item = $this->itemAt($host->get($field_name));
    $item->get('sub')->setValue($this->secondFile->id());
    $this->assertSame($this->secondFile->id(), $this->referencedFile($item)->id());

    $host->save();
    $stored = $this->storedItem($node, $field_name);
    $this->assertNotNull($stored);
    $this->assertSame((string) $this->secondFile->id(), $stored->get('sub')->getValue());
    $this->assertSame('public://second.txt', $this->referencedFile($stored)->getFileUri());
  }

  /**
   * CHARACTERIZATION: once read, the computed file ignores id changes.
   *
   * The computed property loads the file once and never resets it. After one
   * read, changing the id leaves it returning the old file, while the stored
   * id and the data type's own getEntity() follow the change. Core's
   * computed 'entity' follows a changed target_id. The same behavior is
   * covered on every write path for the entity reference sub-field, which
   * shares the computed property class. A future fix changes these
   * expectations.
   *
   * @param string $path
   *   The write path under test.
   *
   * @dataProvider providerWritePaths
   */
  #[DataProvider('providerWritePaths')]
  public function testCachedFileGoesStaleAfterTheTargetChanges(string $path): void {
    $field_name = $this->createCaseField([], 'stale_' . substr(md5($path), 0, 8));
    $node = $this->createHost($field_name, $this->file);
    $item = $this->itemAt($this->reloadedNode($node)->get($field_name));
    $this->assertSame($this->file->id(), $this->referencedFile($item)->id());

    $new_id = $this->secondFile->id();
    match ($path) {
      'magic setter' => $this->setRawValue($item, 'sub', $new_id),
      'property setValue' => $item->get('sub')->setValue($new_id),
      default => $this->fail(sprintf('Unknown write path "%s".', $path)),
    };

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
    ];
  }

  /**
   * CHARACTERIZATION: assigning a file to the computed property is ignored.
   *
   * Core's file item updates 'target_id' when 'entity' is assigned. Here the
   * assignment changes nothing, and the original file is what a save stores.
   * A future fix changes these expectations.
   */
  public function testAssigningTheComputedFileIsIgnored(): void {
    $field_name = $this->createCaseField([], 'assign');
    $node = $this->createHost($field_name, $this->file);
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
   * CHARACTERIZATION: a new file entity is saved on first READ.
   *
   * Core's FileItemTest assigns an unsaved file entity and expects it to be
   * saved with the host. Here the assignment stores the raw
   * ['entity' => $file] array, and the file is saved the first time the id
   * property object is instantiated, which a plain get() read does, even if
   * the host is never saved. A future fix changes these expectations.
   */
  public function testNewFileIsSavedWhenThePropertyIsFirstRead(): void {
    $field_name = $this->createCaseField([], 'read_save');
    file_put_contents('public://new.txt', 'new');
    $new = File::create(['uri' => 'public://new.txt']);
    $node = Node::create(['type' => 'page', 'title' => 'never saved']);
    $node->set($field_name, [['sub' => ['entity' => $new]]]);
    $item = $this->itemAt($node->get($field_name));

    $this->assertTrue($new->isNew(), 'Assigning does not save the file.');
    $raw = $this->rawValue($item, 'sub');
    $this->assertIsArray($raw);
    $this->assertSame($new, $raw['entity']);

    $this->assertNotNull($item->get('sub')->getValue());
    $this->assertFalse($new->isNew(), 'Reading the property saved the file.');
    $this->assertSame((string) $new->id(), (string) $item->get('sub')->getValue());
    $this->assertTrue($node->isNew(), 'The host was never saved.');
  }

  /**
   * CHARACTERIZATION: a nonexistent file id is stored as given.
   *
   * The computed entity resolves to NULL.
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
  }

  /**
   * CHARACTERIZATION: malformed ids are stored converted.
   *
   * The integer column converts the value on save.
   *
   * @param int|string $value
   *   The id to write.
   * @param string $stored
   *   The value read back.
   *
   * @dataProvider providerMalformedIds
   */
  #[DataProvider('providerMalformedIds')]
  public function testMalformedIdsAreStoredConverted(int|string $value, string $stored): void {
    $field_name = $this->createCaseField([], 'bad_' . substr(md5((string) $value), 0, 8));
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
   * @return array<string, array{int|string, string}>
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
   * CHARACTERIZATION: a deleted file's id is kept, and loaded items keep it.
   *
   * An item that had already loaded the file keeps returning it after the
   * file is deleted; a fresh load resolves to NULL; and the dangling id is
   * re-saved with the host.
   */
  public function testDeletedFileIdIsKept(): void {
    $field_name = $this->createCaseField([], 'deleted');
    $node = $this->createHost($field_name, $this->file);
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
  }

  /**
   * CONTRACT: file usage follows the host through its lifecycle.
   *
   * Insert adds one usage; replacing the file moves it; a new revision adds
   * one more; deleting a revision removes one; deleting the host removes the
   * rest. The counts match a core file field's. The difference from core is
   * the module the usage is recorded under: 'custom_field' here, 'file' for
   * a core file field.
   */
  public function testFileUsageFollowsTheHostLifecycle(): void {
    $field_name = $this->createCaseField([], 'usage');
    $node = $this->createHost($field_name, $this->file);
    $nid = (string) $node->id();
    $this->assertSame(['custom_field' => ['node' => [$nid => '1']]], $this->usage($this->file));

    $host = $this->reloadedNode($node);
    $host->set($field_name, [['sub' => $this->secondFile->id()]]);
    $host->save();
    $this->assertSame([], $this->usage($this->file));
    $this->assertSame(['custom_field' => ['node' => [$nid => '1']]], $this->usage($this->secondFile));

    $host = $this->reloadedNode($node);
    $first_revision = $host->getRevisionId();
    $this->assertNotNull($first_revision);
    $host->setNewRevision(TRUE);
    $host->save();
    $this->assertSame(['custom_field' => ['node' => [$nid => '2']]], $this->usage($this->secondFile));

    $this->container->get('entity_type.manager')->getStorage('node')->deleteRevision($first_revision);
    $this->assertSame(['custom_field' => ['node' => [$nid => '1']]], $this->usage($this->secondFile));

    $this->reloadedNode($node)->delete();
    $this->assertSame([], $this->usage($this->secondFile));
  }

  /**
   * The upload validators follow the field settings.
   *
   * The size limit is always present and capped at the PHP upload limit; the
   * extension check is present only when extensions are set. One divergence
   * from core: the limit is the float that Bytes::toNumber() returns, where
   * core casts it to an integer.
   *
   * @param array<string, string> $settings
   *   The field settings to apply over the defaults.
   * @param float|null $limit
   *   The expected size limit, or NULL for the PHP upload limit.
   * @param string|null $extensions
   *   The expected allowed extensions, or NULL when no check is expected.
   *
   * @dataProvider providerUploadValidators
   */
  #[DataProvider('providerUploadValidators')]
  public function testUploadValidators(array $settings, ?float $limit, ?string $extensions): void {
    $php_limit = Bytes::toNumber(Environment::getUploadMaxSize());
    $expected = ['FileSizeLimit' => ['fileLimit' => min($limit ?? $php_limit, $php_limit)]];
    if ($extensions !== NULL) {
      $expected['FileExtension'] = ['extensions' => $extensions];
    }

    $this->assertSame($expected, $this->typeInstance($settings)->getUploadValidators());
  }

  /**
   * Data provider for ::testUploadValidators().
   *
   * @return array<string, array{array<string, string>, float|null, string|null}>
   *   Test cases.
   */
  public static function providerUploadValidators(): array {
    return [
      'the defaults' => [[], NULL, 'txt'],
      'a size in bytes' => [['max_filesize' => '512'], 512.0, 'txt'],
      'a size with a unit' => [['max_filesize' => '2 MB'], 2097152.0, 'txt'],
      'several extensions' => [['file_extensions' => 'txt pdf'], NULL, 'txt pdf'],
      'no extensions' => [['file_extensions' => ''], NULL, NULL],
    ];
  }

  /**
   * The upload location: tokens replaced, slashes trimmed, scheme prepended.
   *
   * The scheme is taken from the settings array the caller passes, not from
   * the instance's own settings; core's FileItem::getUploadLocation() reads
   * its own. The upload widget passes the column's scheme.
   */
  public function testUploadLocation(): void {
    $instance = $this->typeInstance();
    $year = date('Y');

    $this->assertSame('public://custom/' . $year, $instance->getUploadLocation([
      'uri_scheme' => 'public',
      'file_directory' => '/custom/[date:custom:Y]/',
    ]));
    // Without a directory, the type's default token is used.
    $this->assertSame('private://' . date('Y-m'), $instance->getUploadLocation(['uri_scheme' => 'private']));
  }

  /**
   * The check_empty matrix, with a sibling column to keep the row alive.
   *
   * Emptiness is NULL or the empty string only, so a zero id keeps the item
   * even with check_empty on. With check_empty off, an empty string is
   * stored as 0 by the integer column.
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
    $node->set($field_name, [['sub' => $value, 'keep' => 'populated']]);
    $node->save();

    $item = $this->storedItem($node, $field_name);
    if (!$kept) {
      $this->assertNull($item, 'The item was dropped as empty.');
      return;
    }
    $this->assertNotNull($item, 'The item was kept.');
    $this->assertSame($stored, $item->get('sub')->getValue());
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
   * A generated sample is a new permanent file in the upload location.
   *
   * The sample is the scalar file id, unlike core's, which also carries
   * 'display' and 'description'. The extension is one of the allowed ones,
   * or 'txt' when none is set. Core names the file with a unique random name;
   * this type does not ask for uniqueness.
   *
   * @param array<string, mixed> $case
   *   The case's 'column' and 'settings' overrides.
   * @param string $prefix
   *   The expected start of the file URI.
   * @param list<string> $extensions
   *   The extensions the generated file may have.
   *
   * @dataProvider providerSampleValues
   */
  #[DataProvider('providerSampleValues')]
  public function testSampleValueIsNewFileInUploadLocation(array $case, string $prefix, array $extensions): void {
    $field_name = $this->createCaseField($case, 'sample_' . substr(md5(serialize($case)), 0, 8));
    $node = Node::create(['type' => 'page', 'title' => 'sample']);
    $node->get($field_name)->generateSampleItems();

    $value = $node->get($field_name)->getValue();
    $this->assertCount(1, $value);
    $this->assertIsString($value[0]['sub']);
    $file = File::load($value[0]['sub']);
    $this->assertInstanceOf(FileInterface::class, $file);
    $this->assertTrue($file->isPermanent());
    $uri = $file->getFileUri();
    $this->assertIsString($uri);
    $expected_prefix = str_replace('{Y-m}', date('Y-m'), $prefix);
    $this->assertTrue(str_starts_with($uri, $expected_prefix), sprintf('"%s" starts with "%s".', $uri, $expected_prefix));
    $this->assertContains(pathinfo($uri, PATHINFO_EXTENSION), $extensions);
    $this->assertFileExists($uri);
  }

  /**
   * Data provider for ::testSampleValueIsNewFileInUploadLocation().
   *
   * @return array<string, array{array<string, mixed>, string, list<string>}>
   *   Test cases.
   */
  public static function providerSampleValues(): array {
    return [
      'the default directory and extension' => [[], 'public://{Y-m}/', ['txt']],
      'one of several extensions' => [
        ['settings' => ['file_extensions' => 'pdf png']],
        'public://{Y-m}/',
        ['pdf', 'png'],
      ],
      'txt when no extension is set' => [['settings' => ['file_extensions' => '']], 'public://{Y-m}/', ['txt']],
      'a custom directory on the private scheme' => [
        ['column' => ['uri_scheme' => 'private'], 'settings' => ['file_directory' => 'sub/dir']],
        'private://sub/dir/',
        ['txt'],
      ],
    ];
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
   * Creates a permanent file entity with a file on disk.
   */
  private function createFile(string $uri): FileInterface {
    file_put_contents($uri, 'Test file contents.');
    $file = File::create(['uri' => $uri, 'status' => 1]);
    $file->save();

    return $file;
  }

  /**
   * Creates and saves a page host referencing one file.
   */
  private function createHost(string $field_name, FileInterface $file): Node {
    $node = Node::create(['type' => 'page', 'title' => 'host for ' . $field_name]);
    $node->set($field_name, [['sub' => $file->id()]]);
    $node->save();

    return $node;
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
   * does not declare getEntity().
   */
  private function mainProperty(FieldItemInterface $item): CustomFieldEntityReference {
    $property = $item->get('sub');
    $this->assertInstanceOf(CustomFieldEntityReference::class, $property);

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
  private function typeInstance(array $settings = []): FileType {
    $column = $this->defaultColumn($this->columnName());
    $options = $this->typeManager->createOptionsForInstance($settings + $this->defaultFieldSettings(), $column);
    $instance = $this->typeManager->createInstance(static::typeId(), $options['configuration']);
    $this->assertInstanceOf(FileType::class, $instance);

    return $instance;
  }

}
