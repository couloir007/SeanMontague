<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Kernel\CustomField\Type;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Field\FieldException;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\custom_field\Plugin\CustomField\FieldType\EntityReference;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\custom_field\Plugin\DataType\CustomFieldEntityReference;
use Drupal\custom_field\Plugin\Field\FieldType\CustomItem;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\node\NodeTypeInterface;
use Drupal\Tests\custom_field\Kernel\CustomField\CustomFieldTypeKernelTestBase;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the 'entity_reference' custom field sub-field type.
 *
 * The core counterpart is
 * \Drupal\Tests\field\Kernel\EntityReference\EntityReferenceItemTest, and this
 * class follows its axes: an integer-id content target, a string-id config
 * target, the computed entity property, auto-creating a new target, the
 * selection handler settings, and sample generation including the
 * self-reference guard. Three structural differences drive everything that
 * looks different here.
 *
 * 1. The target id lives in the sub-field's main column ('sub'), and the
 *    referenced entity is a computed sibling property ('sub__entity'), where
 *    core has 'target_id' and 'entity' on its own field item. The id's data
 *    type and the column type both follow the target entity type's id field.
 * 2. The only constraint this type declares (EntityType, on 'sub__entity')
 *    cannot be triggered through any supported write path, so
 *    validationCases() is empty and that constraint is declared unreachable
 *    and proven so by ::testEntityTypeConstraintCannotBeTriggered().
 * 3. The handler settings live in each sub-field's own field settings, so the
 *    field's config dependencies on target bundles come from the sub-field
 *    plugin, and so does the clean-up when one of those bundles is deleted.
 *
 * What is deliberately NOT covered here, to avoid duplicating coverage that
 * already exists: the widgets (the Functional and FunctionalJavascript
 * autocomplete, select, radios and hierarchical select widget tests, which
 * also cover auto-create through the widget), the formatters, the Views
 * relationship (CustomFieldViewsRelationshipTest), prepareView()
 * (ReferenceSubfieldPrepareViewTest), the JSON:API normalizer (its own
 * submodule test), and the default widget/formatter wiring
 * (TypeRegistryTest). The 'sort' handler setting and the Views selection
 * handler are not varied: nothing outside the selection handler reads them.
 *
 * Divergences from core's behavior are called out explicitly where they
 * occur, and are never "fixed" toward core in this characterization-first
 * test class.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 * @covers \Drupal\custom_field\Plugin\CustomField\FieldType\EntityReference
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
#[CoversClass(EntityReference::class)]
class EntityReferenceTypeTest extends CustomFieldTypeKernelTestBase {

  /**
   * The first referenceable article.
   */
  private NodeInterface $article;

  /**
   * A second referenceable article.
   */
  private NodeInterface $secondArticle;

  /**
   * A user, for the user-target and wrong-entity-type cases.
   */
  private UserInterface $user;

  /**
   * {@inheritdoc}
   *
   * 'file' is needed by any re-save of a loaded host: the custom field's item
   * list reconciles file usage on update and loads the file storage to do so,
   * whatever the sub-field types.
   */
  protected static $modules = [
    'file',
  ];

  /**
   * {@inheritdoc}
   *
   * The selection handler's entity query runs with an access check, which
   * needs the node_access table; the file schema serves the re-saves, see
   * $modules.
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('node', ['node_access']);
    $this->installEntitySchema('file');

    NodeType::create(['type' => 'article', 'name' => 'Article'])->save();

    // Created before any user, so the user's id is also an existing node id;
    // ::testEntityTypeConstraintCannotBeTriggered() depends on that overlap.
    $this->article = $this->createNode('article', 'First article');
    $this->secondArticle = $this->createNode('article', 'Second article');

    $user = User::create(['name' => 'reference target']);
    $user->save();
    $this->user = $user;
  }

  /**
   * {@inheritdoc}
   */
  protected static function typeId(): string {
    return 'entity_reference';
  }

  /**
   * {@inheritdoc}
   *
   * 'target_type' is a required column key for this type: schema() and
   * propertyDefinitions() both destructure it unguarded. Cases that target
   * another entity type override it through their own 'column' key.
   */
  protected function defaultColumn(string $name): array {
    return parent::defaultColumn($name) + [
      'target_type' => 'node',
    ];
  }

  /**
   * {@inheritdoc}
   *
   * The handler settings are restated here rather than read from the shipped
   * test config, so the bundle restriction every node case depends on is
   * visible in this class. 'handler_settings' is this type's one declared
   * default; 'handler' is not declared anywhere and is only defaulted at the
   * point of use.
   */
  protected function defaultFieldSettings(): array {
    return [
      'handler' => 'default:node',
      'handler_settings' => [
        'target_bundles' => ['article' => 'article'],
      ],
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   *
   * Every expectation reads the stored item through
   * FieldItemInterface::get(), so the id comes back as the string the
   * database returns, on integer and string targets alike. The node row is
   * the first case of the retired test plugin; the others are the input
   * shapes CustomFieldEntityReference::setValue() unwraps, and the two other
   * target families core's test covers.
   */
  protected function roundTripCases(): array {
    $id = (string) $this->article->id();

    return [
      'a node id round-trips' => [
        'value' => ['sub' => (int) $this->article->id()],
        'expected' => ['sub' => $id],
      ],
      'a target_id array is unwrapped to the id' => [
        'value' => ['sub' => ['target_id' => $this->article->id()]],
        'expected' => ['sub' => $id],
      ],
      'a UUID is resolved to the id' => [
        'value' => ['sub' => $this->article->uuid()],
        'expected' => ['sub' => $id],
      ],
      'a config entity id round-trips on a node type target' => [
        'column' => ['target_type' => 'node_type'],
        'settings' => ['handler' => 'default:node_type', 'handler_settings' => []],
        'value' => ['sub' => 'article'],
        'expected' => ['sub' => 'article'],
      ],
      'a user id round-trips on a user target' => [
        'column' => ['target_type' => 'user'],
        'settings' => ['handler' => 'default:user', 'handler_settings' => []],
        'value' => ['sub' => (int) $this->user->id()],
        'expected' => ['sub' => (string) $this->user->id()],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   *
   * Empty, and that emptiness is checked rather than trusted: the type's one
   * declared constraint is listed in ::unreachableConstraints().
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
        'reason' => 'The computed entity is always loaded from the target type\'s own storage, and assigning an entity to it is ignored, so its value can never be of another entity type.',
        'test' => 'testEntityTypeConstraintCannotBeTriggered',
      ],
    ];
  }

  /**
   * The column follows the target entity type's id field.
   *
   * One divergence from core, deliberate here and not "fixed": core's
   * EntityReferenceItem::schema() indexes 'target_id', and this column has
   * no index, although this module ships a Views relationship that joins on
   * it.
   */
  public function testSchema(): void {
    $this->assertSame([
      'sub' => [
        'type' => 'int',
        'description' => 'The ID of the target entity.',
        'unsigned' => TRUE,
      ],
    ], EntityReference::schema(['name' => 'sub', 'target_type' => 'node']));

    // A config entity type that is the bundle of another type is limited to
    // the bundle machine-name length.
    $this->assertSame([
      'sub' => [
        'type' => 'varchar_ascii',
        'description' => 'The ID of the target entity.',
        'length' => 32,
      ],
    ], EntityReference::schema(['name' => 'sub', 'target_type' => 'node_type']));

    // Any other string id gets 255.
    $this->assertSame([
      'sub' => [
        'type' => 'varchar_ascii',
        'description' => 'The ID of the target entity.',
        'length' => 255,
      ],
    ], EntityReference::schema(['name' => 'sub', 'target_type' => 'field_storage_config']));

    $field_name = $this->createCaseField([], 'schema');
    $storage = FieldStorageConfig::loadByName('node', $field_name);
    $this->assertNotNull($storage);
    $this->assertSame([], $storage->getSchema()['indexes']);
  }

  /**
   * An unknown target type fails differently in the two static methods.
   *
   * The schema() method converts the plugin exception into a FieldException
   * naming the sub-field; propertyDefinitions() lets the plugin exception
   * through.
   */
  public function testUnknownTargetTypeFailsInBothStaticMethods(): void {
    try {
      EntityReference::schema(['name' => 'sub', 'target_type' => 'no_such_type']);
      $this->fail('schema() accepted an unknown target type.');
    }
    catch (FieldException $e) {
      $this->assertSame("Field 'sub' references a target entity type 'no_such_type' which does not exist.", $e->getMessage());
    }

    $this->expectException(PluginNotFoundException::class);
    EntityReference::propertyDefinitions(['name' => 'sub', 'target_type' => 'no_such_type']);
  }

  /**
   * The property definitions: the target id and the computed entity.
   */
  public function testPropertyDefinitions(): void {
    $properties = EntityReference::propertyDefinitions(['name' => 'sub', 'target_type' => 'node']);

    $this->assertSame(['sub', 'sub__entity'], array_keys($properties));

    $this->assertSame('custom_field_entity_reference', $properties['sub']->getDataType());
    $this->assertSame('sub ID', (string) $properties['sub']->getLabel());
    $this->assertFalse($properties['sub']->isRequired());
    $this->assertSame(
      ['data_type' => 'integer', 'target_type' => 'node', 'unsigned' => TRUE],
      $properties['sub']->getSettings(),
    );

    $entity = $properties['sub__entity'];
    $this->assertSame('entity_reference', $entity->getDataType());
    $this->assertTrue($entity->isComputed());
    // Writable in its definition, although writing to it does nothing; see
    // ::testAssigningTheComputedEntityIsIgnored().
    $this->assertFalse($entity->isReadOnly());
    $this->assertSame(
      'Drupal\custom_field\Plugin\CustomField\EntityReferenceComputed',
      ltrim((string) $entity->getClass(), '\\'),
    );
    $this->assertSame(['target_id' => 'sub', 'target_type' => 'node'], $entity->getSettings());
    $this->assertSame('Content', (string) $entity->getLabel());

    // A string-id target drops the unsigned setting.
    $config_properties = EntityReference::propertyDefinitions(['name' => 'sub', 'target_type' => 'node_type']);
    $this->assertSame(
      ['data_type' => 'string', 'target_type' => 'node_type'],
      $config_properties['sub']->getSettings(),
    );
  }

  /**
   * There are no extra property names: the computed entity has no column.
   */
  public function testExtraPropertyNamesAreExact(): void {
    $this->assertSame(
      [],
      EntityReference::getExtraPropertyNames(['name' => 'sub', 'target_type' => 'node']),
    );
  }

  /**
   * The declared default field settings: the base set plus handler_settings.
   */
  public function testDefaultFieldSettings(): void {
    $this->assertSame([
      'handler_settings' => [],
      'label' => '',
      'required' => FALSE,
      'translatable' => FALSE,
      'description' => '',
      'description_display' => 'after',
      'check_empty' => FALSE,
    ], EntityReference::defaultFieldSettings());
  }

  /**
   * CONTRACT: the type's only constraint is on the computed property.
   *
   * The getConstraints() method is empty and the plugin attribute declares no
   * 'constraints' key; the one constraint the type declares is EntityType on
   * 'sub__entity'.
   */
  public function testConstraintsLiveOnThePropertyNotThePlugin(): void {
    $instance = $this->typeInstance();
    $this->assertSame([], $instance->getConstraints());
    $definition = $instance->getPluginDefinition();
    $this->assertIsArray($definition);
    $this->assertSame([], $definition['constraints'] ?? []);

    $properties = EntityReference::propertyDefinitions(['name' => 'sub', 'target_type' => 'node']);
    $this->assertSame([], $properties['sub']->toArray()['constraints'] ?? []);
    $this->assertSame(['EntityType' => 'node'], $properties['sub__entity']->toArray()['constraints'] ?? []);
  }

  /**
   * CHARACTERIZATION: the EntityType constraint can never be violated.
   *
   * Core validates the entity a reference points at against the target
   * type. Here, every way of putting an entity of another type into the
   * computed property is a no-op: the magic setter, the property-level
   * setValue() and the values array all leave the stored id alone, and the
   * computed property keeps loading by that id from node storage. A user's
   * id written to the id column resolves to whichever NODE has that id. So
   * the constraint has nothing to reject, and this test is the proof behind
   * the declaration in ::unreachableConstraints().
   */
  public function testEntityTypeConstraintCannotBeTriggered(): void {
    $field_name = $this->createCaseField([], 'et');
    $node = Node::create(['type' => 'page', 'title' => 'entity type']);
    $node->set($field_name, [['sub' => $this->article->id()]]);
    $item = $this->itemAt($node->get($field_name));

    $this->setRawValue($item, 'sub__entity', $this->user);
    $this->assertSame((string) $this->article->id(), (string) $this->rawValue($item, 'sub'));
    $this->assertSame($this->article->id(), $this->referencedNode($item)->id());
    $this->assertNoViolations($node, 'after the magic setter');

    $item->get('sub__entity')->setValue($this->user);
    $this->assertSame($this->article->id(), $this->referencedNode($item)->id());
    $this->assertCount(0, $item->get('sub__entity')->validate());
    $this->assertNoViolations($node, 'after the property-level setValue()');

    $values_node = Node::create(['type' => 'page', 'title' => 'entity type values']);
    $values_node->set($field_name, [['sub' => $this->article->id(), 'sub__entity' => $this->user]]);
    $this->assertSame($this->article->id(), $this->referencedNode($this->itemAt($values_node->get($field_name)))->id());
    $this->assertNoViolations($values_node, 'with an entity in the values array');

    // The user's id is also an existing node's id, see setUp().
    $this->assertNotNull(Node::load($this->user->id()));
    $user_id_node = Node::create(['type' => 'page', 'title' => 'entity type user id']);
    $user_id_node->set($field_name, [['sub' => $this->user->id()]]);
    $this->assertSame($this->user->id(), $this->referencedNode($this->itemAt($user_id_node->get($field_name)))->id());
    $this->assertNoViolations($user_id_node, 'with a user id on a node target');
  }

  /**
   * CONTRACT: the stored id resolves to the referenced entity.
   *
   * Replaces the old shared matrix's per-type check: the id is a known
   * fixture node, and the computed entity carries that node's id and title.
   */
  public function testReferencedEntityResolvesThroughTheComputedProperty(): void {
    $field_name = $this->createCaseField([], 'resolve');
    $node = Node::create(['type' => 'page', 'title' => 'resolve']);
    $node->set($field_name, [['sub' => $this->article->id()]]);
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item);
    $this->assertSame((string) $this->article->id(), $item->get('sub')->getValue());
    $entity = $this->referencedNode($item);
    $this->assertSame($this->article->id(), $entity->id());
    $this->assertSame('First article', $entity->getTitle());

    // The data type's own accessor agrees.
    $this->assertSame($this->article->id(), $this->mainProperty($item)->getEntity()?->id());
  }

  /**
   * CONTRACT: a changed id is followed when the entity was not read before.
   *
   * Retargeting a saved item is what the retired plugin's second case
   * exercised in the old shared matrix. The stored value always follows;
   * the computed entity follows only if nothing read it before the change,
   * see ::testCachedEntityGoesStaleAfterTheTargetChanges().
   */
  public function testChangingTheTargetBeforeTheFirstReadIsFollowed(): void {
    $field_name = $this->createCaseField([], 'retarget');
    $node = $this->createHost($field_name, $this->article);

    $item = $this->itemAt($this->reloadedNode($node)->get($field_name));
    $item->get('sub')->setValue($this->secondArticle->id());
    $this->assertSame($this->secondArticle->id(), $this->referencedNode($item)->id());

    // Saved and reloaded, the new target is what is stored.
    $reloaded = $this->reloadedNode($node);
    $reloaded->set($field_name, [['sub' => $this->secondArticle->id()]]);
    $reloaded->save();
    $stored = $this->storedItem($node, $field_name);
    $this->assertNotNull($stored);
    $this->assertSame((string) $this->secondArticle->id(), $stored->get('sub')->getValue());
    $this->assertSame($this->secondArticle->id(), $this->referencedNode($stored)->id());
  }

  /**
   * CHARACTERIZATION: once read, the computed entity ignores id changes.
   *
   * The computed property loads the entity once and never resets it. After
   * one read, changing the id through ANY write path leaves it returning the
   * old entity, while the stored id and the data type's own getEntity()
   * follow the change. Core's computed 'entity' follows a changed target_id
   * on both the magic setter and the property-level setValue(). A future fix
   * changes these expectations.
   *
   * @param string $path
   *   The write path under test.
   *
   * @dataProvider providerWritePaths
   */
  #[DataProvider('providerWritePaths')]
  public function testCachedEntityGoesStaleAfterTheTargetChanges(string $path): void {
    $field_name = $this->createCaseField([], 'stale_' . substr(md5($path), 0, 8));
    $node = $this->createHost($field_name, $this->article);
    $host = $this->reloadedNode($node);
    $item = $this->itemAt($host->get($field_name));
    $this->assertSame($this->article->id(), $this->referencedNode($item)->id());

    $new_id = $this->secondArticle->id();
    match ($path) {
      'magic setter' => $this->setRawValue($item, 'sub', $new_id),
      'property setValue' => $item->get('sub')->setValue($new_id),
      'item setValue' => $item->setValue(['sub' => $new_id]),
      'list set' => $host->set($field_name, [['sub' => $new_id]]),
      default => $this->fail(sprintf('Unknown write path "%s".', $path)),
    };
    $item = $this->itemAt($host->get($field_name));

    $this->assertSame((string) $new_id, (string) $item->get('sub')->getValue());
    $this->assertSame($new_id, $this->mainProperty($item)->getEntity()?->id());
    $this->assertSame($this->article->id(), $this->referencedNode($item)->id(), 'The computed entity is still the old target.');
  }

  /**
   * Data provider for ::testCachedEntityGoesStaleAfterTheTargetChanges().
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
   * CHARACTERIZATION: assigning an entity to the computed property is ignored.
   *
   * Core's entity reference item updates 'target_id' when 'entity' is
   * assigned. Here the assignment changes nothing, and the original target
   * is what a save stores. A future fix changes these expectations.
   */
  public function testAssigningTheComputedEntityIsIgnored(): void {
    $field_name = $this->createCaseField([], 'assign');
    $node = $this->createHost($field_name, $this->article);
    $host = $this->reloadedNode($node);
    $item = $this->itemAt($host->get($field_name));

    $this->setRawValue($item, 'sub__entity', $this->secondArticle);
    $this->assertSame((string) $this->article->id(), (string) $item->get('sub')->getValue());
    $this->assertSame($this->article->id(), $this->referencedNode($item)->id());

    $host->save();
    $stored = $this->storedItem($node, $field_name);
    $this->assertNotNull($stored);
    $this->assertSame((string) $this->article->id(), $stored->get('sub')->getValue());
  }

  /**
   * CONTRACT: a new target entity is saved along with the host.
   *
   * The counterpart of core's auto-create test, through the
   * ['entity' => $entity] value shape.
   */
  public function testNewTargetIsSavedWithTheHost(): void {
    $field_name = $this->createCaseField([], 'autocreate');
    $new = Node::create(['type' => 'article', 'title' => 'Created through the reference']);
    $node = Node::create(['type' => 'page', 'title' => 'autocreate host']);
    $node->set($field_name, [['sub' => ['entity' => $new]]]);
    $node->save();

    $this->assertFalse($new->isNew());
    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item);
    $this->assertSame((string) $new->id(), $item->get('sub')->getValue());
    $this->assertSame('Created through the reference', $this->referencedNode($item)->getTitle());
  }

  /**
   * CHARACTERIZATION: a new target is saved on first READ, host or no host.
   *
   * The assignment itself stores the raw ['entity' => $entity] array, which
   * the magic getter returns as-is. The target is saved the first time the
   * property object is instantiated, which a plain get() read does, even if
   * the host is never saved. Core saves a new target from the field item's
   * preSave(), so only as part of saving the host. A future fix changes
   * these expectations.
   */
  public function testNewTargetIsSavedWhenThePropertyIsFirstRead(): void {
    $field_name = $this->createCaseField([], 'read_save');
    $new = Node::create(['type' => 'article', 'title' => 'Saved by a read']);
    $node = Node::create(['type' => 'page', 'title' => 'never saved']);
    $node->set($field_name, [['sub' => ['entity' => $new]]]);
    $item = $this->itemAt($node->get($field_name));

    $this->assertTrue($new->isNew(), 'Assigning does not save the target.');
    $raw = $this->rawValue($item, 'sub');
    $this->assertIsArray($raw);
    $this->assertSame($new, $raw['entity']);

    $this->assertNotNull($item->get('sub')->getValue());
    $this->assertFalse($new->isNew(), 'Reading the property saved the target.');
    $this->assertSame((string) $new->id(), (string) $item->get('sub')->getValue());
    $this->assertCount(1, $this->nodesTitled('Saved by a read'));
    $this->assertTrue($node->isNew(), 'The host was never saved.');
  }

  /**
   * CHARACTERIZATION: a target that fails to save becomes a silent NULL.
   *
   * The storage exception is caught inside the data type, the value becomes
   * NULL, and nothing reports it. A title longer than the node title column
   * is the failing save.
   */
  public function testFailedSaveOfNewTargetIsSwallowed(): void {
    $field_name = $this->createCaseField([], 'fail_save');
    $node = Node::create(['type' => 'page', 'title' => 'failing target host']);
    $node->set($field_name, [['sub' => $this->article->id()]]);
    $property = $this->mainProperty($this->itemAt($node->get($field_name)));

    $bad = Node::create(['type' => 'article', 'title' => str_repeat('x', 300)]);
    $property->setValue(['entity' => $bad]);

    $this->assertNull($property->getValue());
    $this->assertTrue($bad->isNew());
  }

  /**
   * CHARACTERIZATION: a nonexistent target id is stored as given.
   *
   * The computed entity resolves to NULL.
   */
  public function testMissingTargetIdIsStored(): void {
    $field_name = $this->createCaseField([], 'missing');
    $node = Node::create(['type' => 'page', 'title' => 'missing target']);
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
      'integer zero is stored' => [0, '0'],
      'string zero is stored' => ['0', '0'],
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
   * CHARACTERIZATION: a deleted target's id is kept, and loaded items keep it.
   *
   * Compared with a core entity reference field on the same host. Both keep
   * the dangling id in storage, both keep returning the deleted entity from
   * an item that had already loaded it, and both resolve to NULL on a fresh
   * load.
   */
  public function testDeletedTargetIsKeptAndStillServedToAnAlreadyLoadedItem(): void {
    $field_name = $this->createCaseField([], 'deleted');
    $this->createCoreReferenceField('field_core_deleted');
    $target_id = $this->article->id();

    $node = Node::create(['type' => 'page', 'title' => 'deleted target']);
    $node->set($field_name, [['sub' => $target_id]]);
    $node->set('field_core_deleted', [['target_id' => $target_id]]);
    $node->save();

    $loaded = $this->reloadedNode($node);
    $this->assertSame($target_id, $this->referencedNode($this->itemAt($loaded->get($field_name)))->id());
    $this->assertNotNull($loaded->get('field_core_deleted')->entity);

    $this->article->delete();

    $loaded_item = $this->itemAt($loaded->get($field_name));
    $this->assertSame($target_id, $this->referencedNode($loaded_item)->id(), 'An already-loaded item still returns the deleted entity.');
    $this->assertNotNull($loaded->get('field_core_deleted')->entity, 'So does core.');

    $fresh = $this->reloadedNode($node);
    $fresh_item = $this->itemAt($fresh->get($field_name));
    $this->assertSame((string) $target_id, $fresh_item->get('sub')->getValue());
    $this->assertNull($this->rawValue($fresh_item, 'sub__entity'));
    $this->assertNull($fresh->get('field_core_deleted')->entity);

    $fresh->save();
    $stored = $this->storedItem($node, $field_name);
    $this->assertNotNull($stored);
    $this->assertSame((string) $target_id, $stored->get('sub')->getValue());
  }

  /**
   * The cast value: an integer for integer targets, and 0 for no value.
   */
  public function testCastedValue(): void {
    $field_name = 'field_sub_cast';
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

    $node = Node::create(['type' => 'page', 'title' => 'cast']);
    $node->set($field_name, [
      ['sub' => $this->article->id(), 'keep' => 'populated'],
      ['sub' => NULL, 'keep' => 'populated'],
    ]);
    $node->save();
    $items = $this->reloadedNode($node)->get($field_name);

    $this->assertSame((int) $this->article->id(), $this->mainProperty($this->itemAt($items, 0))->getCastedValue());
    // NULL casts to 0, which is not a valid id.
    $this->assertNull($this->itemAt($items, 1)->get('sub')->getValue());
    $this->assertSame(0, $this->mainProperty($this->itemAt($items, 1))->getCastedValue());
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
   *   The stored id, or NULL when the item is expected to be dropped.
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
    ];
  }

  /**
   * A generated sample references an existing entity of an allowed bundle.
   */
  public function testSampleValueReferencesAnExistingTarget(): void {
    $field_name = $this->createCaseField([], 'sample_existing');
    $node = Node::create(['type' => 'page', 'title' => 'sample existing']);
    $node->get($field_name)->generateSampleItems();

    $id = $this->itemAt($node->get($field_name))->get('sub')->getValue();
    $this->assertContains((string) $id, [(string) $this->article->id(), (string) $this->secondArticle->id()]);
    $this->assertNoViolations($node);
  }

  /**
   * Without handler settings, the sample is NULL.
   *
   * This is what the inherited ::testSampleValueGeneration() would see with
   * the type's own defaults, which is why this class restates the handler
   * settings in ::defaultFieldSettings().
   */
  public function testSampleValueIsNullWithoutHandlerSettings(): void {
    $field_name = $this->createCaseField(['settings' => ['handler_settings' => []]], 'sample_none');
    $node = Node::create(['type' => 'page', 'title' => 'sample none']);
    $node->get($field_name)->generateSampleItems();

    $this->assertSame([['sub' => NULL]], $node->get($field_name)->getValue());
  }

  /**
   * With nothing referenceable, the sample creates a new allowed target.
   *
   * The new entity is saved on the first read of the property, see
   * ::testNewTargetIsSavedWhenThePropertyIsFirstRead().
   */
  public function testSampleValueCreatesTargetWhenNoneIsReferenceable(): void {
    $this->article->delete();
    $this->secondArticle->delete();
    $field_name = $this->createCaseField([], 'sample_create');

    $node = Node::create(['type' => 'page', 'title' => 'sample create']);
    $node->get($field_name)->generateSampleItems();
    $id = $this->itemAt($node->get($field_name))->get('sub')->getValue();

    $this->assertNotNull($id);
    $created = Node::load($id);
    $this->assertInstanceOf(NodeInterface::class, $created);
    $this->assertSame('article', $created->bundle());
  }

  /**
   * CHARACTERIZATION: reading the entity first on such a sample throws.
   *
   * The sample is the raw ['entity' => $entity] array. The computed property
   * reads its id through the item's magic getter, which returns that raw
   * array until something instantiates the id property, and hands it to the
   * storage's load(): PHP warnings, then a \TypeError. Reading the id
   * property first, as ::testSampleValueCreatesTargetWhenNoneIsReferenceable()
   * does, avoids it. A future fix changes these expectations.
   */
  public function testReadingTheEntityFirstOnCreatedSampleThrows(): void {
    $this->article->delete();
    $this->secondArticle->delete();
    $field_name = $this->createCaseField([], 'sample_raw');
    $node = Node::create(['type' => 'page', 'title' => 'sample raw']);
    $node->get($field_name)->generateSampleItems();
    $item = $this->itemAt($node->get($field_name));

    $warnings = [];
    set_error_handler(static function (int $errno, string $message) use (&$warnings): bool {
      $warnings[] = $message;
      return TRUE;
    }, E_WARNING);
    try {
      $unexpected = $this->rawValue($item, 'sub__entity');
      $this->fail(sprintf('Reading the computed entity on a raw sample value returned %s instead of throwing.', get_debug_type($unexpected)));
    }
    catch (\TypeError $e) {
      // The wording depends on the PHP version: "Illegal offset type" before
      // PHP 8.3, "Cannot access offset of type array on array" from 8.3.
      $this->assertMatchesRegularExpression('/^(Illegal offset type|Cannot access offset of type array on array)$/', $e->getMessage());
    }
    finally {
      restore_error_handler();
    }
    $this->assertContains('Array to string conversion', $warnings);
  }

  /**
   * A self-referencing sample stops after one level.
   *
   * Sampling a page whose reference targets pages creates one sample page;
   * that page's own reference gets the recursion guard's empty value and is
   * dropped.
   */
  public function testSelfReferencingSampleStopsAfterOneLevel(): void {
    $field_name = $this->createCaseField([
      'settings' => ['handler_settings' => ['target_bundles' => ['page' => 'page']]],
    ], 'sample_self');

    $sample = $this->container->get('entity_type.manager')->getStorage('node')->createWithSampleValues('page');
    $this->assertInstanceOf(NodeInterface::class, $sample);
    $inner_id = $this->itemAt($sample->get($field_name))->get('sub')->getValue();
    $this->assertNotNull($inner_id);
    $inner = Node::load($inner_id);
    $this->assertInstanceOf(NodeInterface::class, $inner);
    $this->assertCount(0, $inner->get($field_name));
  }

  /**
   * The field depends on the target bundles in its handler settings.
   */
  public function testCalculateDependencies(): void {
    $field_name = $this->createCaseField([], 'deps');

    $field = FieldConfig::loadByName('node', 'page', $field_name);
    $this->assertNotNull($field);
    $this->assertContains('node.type.article', $field->getDependencies()['config']);

    $storage = FieldStorageConfig::loadByName('node', $field_name);
    $this->assertNotNull($storage);
    $this->assertContains('node', $storage->getDependencies()['module']);
  }

  /**
   * Deleting a target bundle keeps the field and prunes the bundle.
   *
   * Like a core entity reference field restricted to the same bundle, the
   * field, its storage and its stored values survive, and the bundle is
   * removed from the sub-field's handler settings and from the field's
   * dependencies.
   */
  public function testDeletingTargetBundleKeepsTheFieldAndPrunesTheBundle(): void {
    $field_name = $this->createCaseField([], 'bundle_delete');
    $this->createCoreReferenceField('field_core_bundle');
    $node = Node::create(['type' => 'page', 'title' => 'bundle delete host']);
    $node->set($field_name, [['sub' => $this->article->id()]]);
    $node->save();

    $this->article->delete();
    $this->secondArticle->delete();
    $this->articleType()->delete();

    $field = FieldConfig::loadByName('node', 'page', $field_name);
    $this->assertNotNull($field);
    $this->assertNotNull(FieldStorageConfig::loadByName('node', $field_name));
    $host = $this->reloadedNode($node);
    $this->assertTrue($host->hasField($field_name));
    $this->assertSame((string) $this->article->id(), (string) $this->itemAt($host->get($field_name))->get('sub')->getValue());

    $core_field = FieldConfig::loadByName('node', 'page', 'field_core_bundle');
    $this->assertNotNull($core_field);
    $this->assertSame(['target_bundles' => []], $core_field->getSetting('handler_settings'));
    $this->assertSame($core_field->getSetting('handler_settings'), $field->getSetting('field_settings')['sub']['handler_settings']);
    $this->assertNotContains('node.type.article', $field->calculateDependencies()->getDependencies()['config'] ?? []);
  }

  /**
   * Dependency removal prunes the bundle from the sub-field's settings.
   *
   * The deleted bundle is removed from the sub-field's handler settings, the
   * field type reports the field as changed, the pruned settings are not
   * nested under any other key, and the field no longer depends on the
   * bundle.
   */
  public function testDependencyRemovalPrunesTheBundleFromTheSubFieldSettings(): void {
    $field_name = $this->createCaseField([], 'dep_removal');
    $field = FieldConfig::loadByName('node', 'page', $field_name);
    $this->assertNotNull($field);

    $changed = CustomItem::onDependencyRemoval($field, [
      'config' => ['node.type.article' => $this->articleType()],
    ]);

    $this->assertTrue($changed);
    $settings = $field->getSetting('field_settings')['sub'];
    $this->assertSame(['target_bundles' => []], $settings['handler_settings']);
    $this->assertNotContains('node.type.article', $field->calculateDependencies()->getDependencies()['config'] ?? []);
  }

  /**
   * Creates and saves a node.
   */
  private function createNode(string $bundle, string $title): NodeInterface {
    $node = Node::create(['type' => $bundle, 'title' => $title]);
    $node->save();

    return $node;
  }

  /**
   * Creates and saves a page host referencing one target.
   */
  private function createHost(string $field_name, NodeInterface $target): Node {
    $node = Node::create(['type' => 'page', 'title' => 'host for ' . $field_name]);
    $node->set($field_name, [['sub' => $target->id()]]);
    $node->save();

    return $node;
  }

  /**
   * Creates a core entity reference field on page, restricted to articles.
   */
  private function createCoreReferenceField(string $field_name): void {
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'settings' => ['target_type' => 'node'],
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();
    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'bundle' => 'page',
      'settings' => [
        'handler' => 'default:node',
        'handler_settings' => ['target_bundles' => ['article' => 'article']],
      ],
    ])->save();
    $this->container->get('entity_field.manager')->clearCachedFieldDefinitions();
  }

  /**
   * Returns the article node type.
   */
  private function articleType(): NodeTypeInterface {
    $type = NodeType::load('article');
    $this->assertInstanceOf(NodeTypeInterface::class, $type);

    return $type;
  }

  /**
   * Returns the nodes with a given title.
   *
   * @return array<int|string, \Drupal\Core\Entity\EntityInterface>
   *   The matching nodes.
   */
  private function nodesTitled(string $title): array {
    return $this->container->get('entity_type.manager')->getStorage('node')->loadByProperties(['title' => $title]);
  }

  /**
   * Returns the computed entity of an item, asserted to be a node.
   */
  private function referencedNode(FieldItemInterface $item): NodeInterface {
    $entity = $this->rawValue($item, 'sub__entity');
    $this->assertInstanceOf(NodeInterface::class, $entity);

    return $entity;
  }

  /**
   * Returns the id property narrowed to this type's data type.
   *
   * FieldItemInterface::get() is typed to return TypedDataInterface, which
   * declares neither getEntity() nor getCastedValue().
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
   */
  private function typeInstance(): CustomFieldTypeInterface {
    $column = $this->defaultColumn($this->columnName());
    $options = $this->typeManager->createOptionsForInstance($this->defaultFieldSettings(), $column);
    $instance = $this->typeManager->createInstance(static::typeId(), $options['configuration']);
    $this->assertInstanceOf(CustomFieldTypeInterface::class, $instance);

    return $instance;
  }

}
