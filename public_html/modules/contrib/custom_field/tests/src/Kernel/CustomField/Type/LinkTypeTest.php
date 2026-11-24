<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Kernel\CustomField\Type;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\custom_field\Plugin\CustomField\FieldType\LinkType;
use Drupal\custom_field\Plugin\CustomField\FieldType\LinkTypeInterface;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\node\Entity\Node;
use Drupal\Tests\custom_field\Kernel\CustomField\CustomFieldTypeKernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the 'link' sub-field type.
 *
 * LinkType extends UriType and adds two sibling properties, '{name}__title'
 * and '{name}__options', plus its own extractExtraPropertyValues() override
 * so that a title and an options map survive a REPEATED, nested setValue()
 * call the same way every other extra-property type in this module does
 * (see TimeRangeTypeTest for the precedent).
 *
 * The malformed-URI message table and the link_type restriction matrix are
 * deliberately NOT repeated here: link inherits both validators from
 * UriType unchanged, and UriTypeTest already pins every row of each. Adding
 * a second, parallel copy of the same table here would double the
 * maintenance cost for zero extra protection.
 *
 * Two live validation assertions remain on this class in spite of that: one
 * schemeless-value case (in validationCases(), below) and one inaccessible
 * routed URI (::testInaccessibleRoutedUriIsRejected()). Both exist because
 * PHP attributes on a child class are NOT merged with the parent's -- each
 * class's #[CustomFieldType(...)] attribute is read on its own, independent
 * of what the class it extends declares. A sibling sub-field type in this
 * module (time_range, which extends time) was bitten by exactly this: its
 * own attribute carries no 'constraints' key, so its getConstraints() is
 * silently empty even though its parent declares a real constraint. LinkType
 * re-declares all four constraints in its own attribute, and
 * ::testConstraintsAreDeclaredOnTheChildClass() asserts that re-declaration
 * directly -- the two validation cases above are what proves the
 * re-declared constraints are not just present but actually wired up and
 * live, the same way UriTypeTest proves it for UriType.
 *
 * Several additional cases below (the NULL-options, unrelated-resave,
 * scalar-assignment and multiple-link_type-samples tests, and the title
 * accessor) are modelled directly on core's own
 * \Drupal\Tests\link\Kernel\LinkItemTest, which exercises the same
 * uri/title/options shape for core's own link field. Divergences from core's
 * behavior are called out explicitly where they occur, and are never
 * "fixed" toward core in this characterization-first test class.
 *
 * One case from that core test is deliberately NOT ported: assigning NULL over
 * a whole field item. That exercises the item and its list rather than this
 * sub-field type, so it belongs with the other item-level coverage, not here.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 * @covers \Drupal\custom_field\Plugin\CustomField\FieldType\LinkType
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
#[CoversClass(LinkType::class)]
class LinkTypeTest extends CustomFieldTypeKernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   *
   * 'link' supplies the 'link to any page' permission the access tests need.
   * 'file' is needed only by ::testFieldDataSurvivesAnUnrelatedResave(): on an
   * UPDATE, CustomItemList::postSave() reaches the file-usage bookkeeping and
   * asks for the file storage even when the field has no file or image column,
   * so the file entity type has to exist. The module declares file as a hard
   * dependency, so production always has it; the shared kernel base does not
   * enable it, which is why no earlier per-type test needed this.
   */
  protected static $modules = ['link', 'file'];

  /**
   * Mirrors core link's LinkTitleVisibility::Disabled->value.
   *
   * This module's own src/ still reads the deprecated DRUPAL_DISABLED
   * constant (a separate finding, not fixed here); this test class declares
   * its own integer constant instead of using it, since DRUPAL_DISABLED is
   * deprecated in drupal:11.3.0 and removed in drupal:13.0.0.
   *
   * @see https://www.drupal.org/node/3448089
   */
  private const TITLE_DISABLED = 0;

  /**
   * Mirrors core link's LinkTitleVisibility::Optional->value.
   */
  private const TITLE_OPTIONAL = 1;

  /**
   * Mirrors core link's LinkTitleVisibility::Required->value.
   */
  private const TITLE_REQUIRED = 2;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Saving the same node twice makes node delete and rewrite its access
    // grants, so that table has to exist for
    // ::testFieldDataSurvivesAnUnrelatedResave(). The shared base does not
    // install it because no other per-type test saves one node twice.
    $this->installSchema('node', ['node_access']);
    $this->installEntitySchema('file');
  }

  /**
   * {@inheritdoc}
   */
  protected static function typeId(): string {
    return 'link';
  }

  /**
   * {@inheritdoc}
   *
   * Stated explicitly rather than relying on the backfill in
   * CustomFieldTypeBase::getFieldSettings(), so that the cases below do not
   * depend on it. That backfill is itself pinned, in
   * ::testOmittedLinkTypeIsBackfilledFromTheTypeDefault().
   *
   * 'enabled_attributes' and 'widget_default_open' are deliberately left out:
   * they are widget/form-only settings with no bearing on validation,
   * storage or sample generation, and their config-schema shape is out of
   * scope for this class. Their declared defaults are still pinned on their
   * own terms in ::testDefaultFieldSettings().
   */
  protected function defaultFieldSettings(): array {
    return [
      'link_type' => LinkTypeInterface::LINK_GENERIC,
      'field_prefix' => 'default',
      'field_prefix_custom' => '',
      'title' => self::TITLE_OPTIONAL,
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   *
   * Every expectation below reads the stored item through
   * FieldItemInterface::get(), which INSTANTIATES the property object. For the
   * options map that matters: an absent options column and one explicitly
   * stored as an empty array both read back as an empty array through that
   * path, because a column with no options holds serialize(NULL) and
   * instantiating the Map turns that NULL into []. The two are only
   * distinguishable before the property is instantiated, which
   * ::testAbsentAndEmptyOptionsDifferOnlyOnTheRawValue() pins separately. The
   * rows that supply no options, a NULL options value and an empty-string
   * options value are therefore all expected to read back as [] here, and are
   * kept distinct because the INPUTS differ and each must be accepted without
   * error.
   */
  protected function roundTripCases(): array {
    $uri = 'https://example.com';

    return [
      'uri alone, no title or options' => [
        'value' => ['sub' => ['uri' => $uri]],
        'expected' => ['sub' => $uri, 'sub__title' => NULL, 'sub__options' => []],
      ],
      'a NULL title is not extracted' => [
        'value' => ['sub' => ['uri' => $uri, 'title' => NULL]],
        'expected' => ['sub' => $uri, 'sub__title' => NULL, 'sub__options' => []],
      ],
      'an empty string title is extracted and stored' => [
        'value' => ['sub' => ['uri' => $uri, 'title' => '']],
        'expected' => ['sub' => $uri, 'sub__title' => '', 'sub__options' => []],
      ],
      'title and no options' => [
        'value' => ['sub' => ['uri' => $uri, 'title' => 'Example']],
        'expected' => ['sub' => $uri, 'sub__title' => 'Example', 'sub__options' => []],
      ],
      'a NULL options value is not extracted' => [
        'value' => ['sub' => ['uri' => $uri, 'title' => 'Example', 'options' => NULL]],
        'expected' => ['sub' => $uri, 'sub__title' => 'Example', 'sub__options' => []],
      ],
      'an empty string options value is not an array' => [
        'value' => ['sub' => ['uri' => $uri, 'title' => 'Example', 'options' => '']],
        'expected' => ['sub' => $uri, 'sub__title' => 'Example', 'sub__options' => []],
      ],
      'an empty options array is extracted and stored' => [
        'value' => ['sub' => ['uri' => $uri, 'title' => 'Example', 'options' => []]],
        'expected' => ['sub' => $uri, 'sub__title' => 'Example', 'sub__options' => []],
      ],
      'populated attributes round-trip' => [
        'value' => [
          'sub' => [
            'uri' => $uri,
            'title' => 'Example',
            'options' => ['attributes' => ['target' => '_blank']],
          ],
        ],
        'expected' => [
          'sub' => $uri,
          'sub__title' => 'Example',
          'sub__options' => ['attributes' => ['target' => '_blank']],
        ],
      ],
      'a query and class attribute round-trip together' => [
        'value' => [
          'sub' => [
            'uri' => 'https://www.drupal.org',
            'title' => 'Drupal',
            'options' => [
              'query' => ['test_param' => 'test_value'],
              'attributes' => ['class' => 'test-class'],
            ],
          ],
        ],
        'expected' => [
          'sub' => 'https://www.drupal.org',
          'sub__title' => 'Drupal',
          'sub__options' => [
            'query' => ['test_param' => 'test_value'],
            'attributes' => ['class' => 'test-class'],
          ],
        ],
      ],
      'a NULL inside the options map survives serialization' => [
        'value' => [
          'sub' => [
            'uri' => 'https://www.drupal.org',
            'title' => 'Drupal',
            'options' => [
              'query' => NULL,
              'attributes' => ['class' => 'test-class'],
            ],
          ],
        ],
        'expected' => [
          'sub' => 'https://www.drupal.org',
          'sub__title' => 'Drupal',
          'sub__options' => [
            'query' => NULL,
            'attributes' => ['class' => 'test-class'],
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   *
   * Exactly one violation, not two: the typed-data primitive backstop that
   * would add a second ("correct primitive type") violation on core's own
   * link field does not apply here, for the same reason explained in
   * UriTypeTest::testSchemelessValueProducesOnlyThePathViolation() -- the
   * custom_field_link data type does not implement UriInterface. A future
   * fix would make this two violations. The full malformed-input table lives
   * in UriTypeTest, since link inherits the validator unchanged.
   */
  protected function validationCases(): array {
    return [
      'a string with no URI scheme' => [
        'value' => ['sub' => ['uri' => 'Not a URL']],
        'property' => 'sub',
        'message' => "The path 'Not a URL' is invalid.",
        'count' => 1,
      ],
    ];
  }

  /**
   * The schema is three columns: the uri, a title and a serialized options.
   *
   * The two extra columns are the boundary against UriType, which declares
   * only the first.
   */
  public function testSchema(): void {
    $this->assertSame([
      'sub' => [
        'description' => 'The URI of the link.',
        'type' => 'varchar',
        'length' => 2048,
      ],
      'sub__title' => [
        'description' => 'The link text.',
        'type' => 'varchar',
        'length' => 255,
      ],
      'sub__options' => [
        'description' => 'Serialized array of options for the link.',
        'type' => 'blob',
        'size' => 'big',
        'serialize' => TRUE,
      ],
    ], LinkType::schema(['name' => 'sub']));
  }

  /**
   * The property definitions: the link-typed main property, plus two extras.
   */
  public function testPropertyDefinitions(): void {
    $properties = LinkType::propertyDefinitions(['name' => 'sub']);

    $this->assertSame(['sub', 'sub__title', 'sub__options'], array_keys($properties));

    $this->assertSame('custom_field_link', $properties['sub']->getDataType());
    $this->assertFalse($properties['sub']->isRequired());
    // LinkType sets a 'field_type' setting on its own property; uri does not
    // (see UriTypeTest::testPropertyDefinitions()).
    $this->assertSame('link', $properties['sub']->getSetting('field_type'));

    $this->assertTrue($properties['sub__title']->isInternal());
    $this->assertSame('string', $properties['sub__title']->getDataType());

    $this->assertTrue($properties['sub__options']->isInternal());
    $this->assertSame('map', $properties['sub__options']->getDataType());
  }

  /**
   * The two extra property names, derived from the schema, in schema order.
   */
  public function testExtraPropertyNamesAreExact(): void {
    $this->assertSame(
      ['sub__title', 'sub__options'],
      LinkType::getExtraPropertyNames(['name' => 'sub']),
    );
  }

  /**
   * The declared default field settings, including link's own three.
   */
  public function testDefaultFieldSettings(): void {
    $this->assertSame([
      'enabled_attributes' => [
        'id' => FALSE,
        'name' => FALSE,
        'target' => TRUE,
        'rel' => TRUE,
        'class' => TRUE,
        'accesskey' => FALSE,
      ],
      'widget_default_open' => LinkType::WIDGET_OPEN_EXPAND_IF_VALUES_SET,
      'title' => self::TITLE_OPTIONAL,
      'link_type' => LinkTypeInterface::LINK_GENERIC,
      'field_prefix' => 'default',
      'field_prefix_custom' => '',
      'label' => '',
      'required' => FALSE,
      'translatable' => FALSE,
      'description' => '',
      'description_display' => 'after',
      'check_empty' => FALSE,
    ], LinkType::defaultFieldSettings());
  }

  /**
   * All four constraints are re-declared on LinkType's own attribute.
   *
   * See the class docblock for why this matters: a child class's PHP
   * attribute is read independently of its parent's, so this re-declaration
   * is asserted here rather than assumed from UriType's own copy.
   */
  public function testConstraintsAreDeclaredOnTheChildClass(): void {
    $expected = [
      'CustomFieldLinkAccess' => [],
      'CustomFieldLinkExternalProtocols' => [],
      'CustomFieldLinkType' => [],
      'CustomFieldLinkNotExistingInternal' => [],
    ];

    $this->assertSame($expected, $this->typeInstance()->getConstraints());
    $this->assertSame(
      $expected,
      $this->typeInstance(['link_type' => LinkTypeInterface::LINK_INTERNAL])->getConstraints(),
      'The constraint set does not vary with the link_type setting.',
    );
  }

  /**
   * LinkType::getUrl() passes the stored options through to the Url object.
   *
   * This is link's own override: UriType::getUrl() never passes an options
   * array at all. Asserts the WHOLE options array the way core's own
   * LinkItemTest does, rather than individual keys -- core's expected array
   * includes an 'external' => TRUE key that Url::fromUri() itself adds for
   * any non-'base:' scheme, which is worth pinning here too since it is
   * easy to assume the options array is passed through verbatim.
   */
  public function testStoredOptionsReachTheUrl(): void {
    $field_name = $this->createCaseField([], 'options_reach_url');

    $node = Node::create(['type' => 'page', 'title' => 'options reach url']);
    $node->set($field_name, [[
      'sub' => [
        'uri' => 'https://www.drupal.org',
        'options' => [
          'query' => ['test_param' => 'test_value'],
          'fragment' => 'results',
        ],
      ],
    ],
    ]);
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item);

    $url = $this->linkTypeInstance()->getUrl($item);
    $this->assertSame([
      'query' => ['test_param' => 'test_value'],
      'fragment' => 'results',
      'external' => TRUE,
    ], $url->getOptions());
  }

  /**
   * NULL stored options do not break getUrl().
   *
   * Mirrors core's own explicit regression guard in LinkItemTest. LinkType's
   * getUrl() casts the options property to an array before passing it to
   * Url::fromUri(), so a NULL value -- the default for an unset options
   * column -- is accepted rather than raising a type error.
   */
  public function testNullOptionsDoNotBreakGetUrl(): void {
    $field_name = $this->createCaseField([], 'null_options_get_url');

    $node = Node::create(['type' => 'page', 'title' => 'null options']);
    $node->set($field_name, [['sub' => ['uri' => 'https://example.com']]]);
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item);
    // Read the RAW value, before anything instantiates the Map property: an
    // unset options column really is NULL in storage. Reading it through
    // get() instead would instantiate the Map and return [], which is what
    // getUrl() then casts; both layers are pinned in
    // ::testAbsentAndEmptyOptionsDifferOnlyOnTheRawValue().
    $this->assertNull($this->rawValue($item, 'sub__options'), 'The raw options value is NULL.');

    // The guarantee is that this call does not raise a type error; the URI is
    // asserted rather than the return type, which the interface already pins.
    $this->assertSame('https://example.com', $this->linkTypeInstance()->getUrl($item)->getUri());
  }

  /**
   * An absent options map and an empty one differ only on the raw value.
   *
   * The distinction is BEFORE versus AFTER instantiation, not raw versus typed.
   * FieldItemBase::__get() returns the property object's value if that object
   * already exists, and only falls back to the raw stored value if it does not
   * -- so reading the magic property FIRST yields the raw value, while reading
   * it after any get() on the same property yields what the instantiated Map
   * says. Verified by probing both orders: magic-then-get gives NULL then [],
   * and get-then-magic gives [] then [] on the very same stored row.
   *
   * What the Map normalizes: a column with no options stores serialize(NULL)
   * (the column is a serialized blob, so this is not an SQL NULL), which reads
   * back as NULL at the property level, and instantiating the Map turns that
   * NULL into an empty array. So at the instantiated layer "no options were
   * ever stored" and "an empty options array was stored" are indistinguishable;
   * only the pre-instantiation value tells them apart.
   *
   * The assertions below therefore read raw first and get() second, in that
   * order deliberately -- reversing them would make the first two assertions
   * pass for the wrong reason.
   */
  public function testAbsentAndEmptyOptionsDifferOnlyOnTheRawValue(): void {
    $absent = $this->createCaseField([], 'options_absent');
    $empty = $this->createCaseField([], 'options_empty');

    $absent_node = Node::create(['type' => 'page', 'title' => 'options absent']);
    $absent_node->set($absent, [['sub' => ['uri' => 'https://example.com']]]);
    $absent_node->save();

    $empty_node = Node::create(['type' => 'page', 'title' => 'options empty']);
    $empty_node->set($empty, [['sub' => ['uri' => 'https://example.com', 'options' => []]]]);
    $empty_node->save();

    $absent_item = $this->storedItem($absent_node, $absent);
    $empty_item = $this->storedItem($empty_node, $empty);
    $this->assertNotNull($absent_item);
    $this->assertNotNull($empty_item);

    // Raw first: the two differ.
    $this->assertNull($this->rawValue($absent_item, 'sub__options'), 'No options stored: the raw value is NULL.');
    $this->assertSame([], $this->rawValue($empty_item, 'sub__options'), 'An empty options array stored: the raw value is [].');

    // Through get(): the two are the same.
    $this->assertSame([], $absent_item->get('sub__options')->getValue());
    $this->assertSame([], $empty_item->get('sub__options')->getValue());
  }

  /**
   * CHARACTERIZATION: a scalar assignment sets only the uri, unlike core.
   *
   * Core's own LinkItem::setValue() always backfills 'options' to an empty
   * array for any assignment, so a plain string assignment there yields uri
   * set, title NULL and options [] -- and core asserts that through the magic
   * property, i.e. on the raw value. This module's CustomItem::setValue() only
   * calls LinkType::extractExtraPropertyValues() when the incoming value for
   * the main property is itself an array, and CustomFieldLink::setValue() only
   * writes the title/options siblings when they are present in that array -- a
   * scalar value satisfies neither, so nothing is ever written to the options
   * column and its raw value stays NULL where core's would be []. The
   * difference is confined to that raw layer: read through get(), both are []
   * (see ::testAbsentAndEmptyOptionsDifferOnlyOnTheRawValue()). A future fix
   * that aligned this module with core's backfill would flip the raw
   * expectation below and leave the get() one unchanged.
   */
  public function testScalarAssignmentSetsOnlyTheUri(): void {
    $field_name = $this->createCaseField([], 'scalar_assignment');

    $node = Node::create(['type' => 'page', 'title' => 'scalar assignment']);
    $node->set($field_name, ['https://example.com']);
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item);
    // Raw values first, before anything instantiates the Map property.
    $this->assertNull(
      $this->rawValue($item, 'sub__options'),
      'Unlike core link, which backfills options to an empty array for any assignment, the raw value stays NULL.',
    );
    $this->assertNull($this->rawValue($item, 'sub__title'));

    $this->assertSame('https://example.com', $item->get('sub')->getValue());
    $this->assertNull($item->get('sub__title')->getValue());
    $this->assertSame(
      [],
      $item->get('sub__options')->getValue(),
      'Through get() the difference from core disappears.',
    );
  }

  /**
   * Link field data survives an unrelated re-save of the same entity.
   *
   * Distinct from ::testProgrammaticNestedValueSurvivesRepeatedSetValue():
   * that test repeats setValue() on the SAME field within one request; this
   * one saves, changes an unrelated field, and saves again, proving the
   * stored values round-trip through storage intact rather than merely
   * surviving in memory.
   */
  public function testFieldDataSurvivesAnUnrelatedResave(): void {
    $field_name = $this->createCaseField([], 'unrelated_resave');

    $node = Node::create(['type' => 'page', 'title' => 'first save']);
    $node->set($field_name, [[
      'sub' => [
        'uri' => 'https://example.com',
        'title' => 'Example',
        'options' => ['attributes' => ['target' => '_blank']],
      ],
    ],
    ]);
    $node->save();

    $node->setTitle('second save');
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item);
    $this->assertSame('https://example.com', $item->get('sub')->getValue());
    $this->assertSame('Example', $item->get('sub__title')->getValue());
    $this->assertSame(['attributes' => ['target' => '_blank']], $item->get('sub__options')->getValue());
  }

  /**
   * The link data type's own title and options accessors.
   *
   * CustomFieldLink::getTitle() and ::getOptions() are gated on the
   * property's 'field_type' setting being 'link' (see
   * ::testPropertyDefinitions() for where that setting is declared) --
   * UriType's own 'sub' property has no such setting, so the same accessor
   * there always returns NULL / [] regardless of storage. This pins the
   * gate as actually open for link.
   */
  public function testTitleAccessor(): void {
    $field_name = $this->createCaseField([], 'title_accessor');

    $node = Node::create(['type' => 'page', 'title' => 'title accessor']);
    $node->set($field_name, [[
      'sub' => [
        'uri' => 'https://example.com',
        'title' => 'Example',
        'options' => ['attributes' => ['target' => '_blank']],
      ],
    ],
    ]);
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item);

    /** @var \Drupal\custom_field\Plugin\DataType\CustomFieldLink $link */
    $link = $item->get('sub');
    $this->assertSame('Example', $link->getTitle());
    $this->assertSame(['attributes' => ['target' => '_blank']], $link->getOptions());
  }

  /**
   * External URLs and internal URIs are told apart by isExternal().
   *
   * The isExternal() method is inherited unchanged from UriType, but it
   * calls $this->getUrl(), which LinkType DOES override -- so this
   * re-asserts the behavior through link's own getUrl() implementation.
   *
   * @param string $uri
   *   The URI to inspect.
   * @param bool $expected
   *   The expected isExternal() verdict.
   *
   * @dataProvider providerIsExternal
   */
  #[DataProvider('providerIsExternal')]
  public function testIsExternal(string $uri, bool $expected): void {
    $field_name = $this->createCaseField([], 'ext_' . (int) $expected);

    $node = Node::create(['type' => 'page', 'title' => 'isExternal']);
    $node->set($field_name, [['sub' => ['uri' => $uri]]]);
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item);

    $instance = $this->linkTypeInstance();
    $this->assertSame($expected, $instance->isExternal($item), $uri);
  }

  /**
   * Data provider for ::testIsExternal().
   *
   * @return array<string, array{string, bool}>
   *   Test cases.
   */
  public static function providerIsExternal(): array {
    return [
      'external https URL' => ['https://www.drupal.org', TRUE],
      'base scheme is internal' => ['base:some-path', FALSE],
    ];
  }

  /**
   * A routed, internal front-page URI round-trips once access is granted.
   *
   * The retired test plugin's third case: a routed internal URI only
   * validates cleanly once the access constraint is satisfied, which is why
   * this is a dedicated test rather than a roundTripCases() row -- that
   * runner never configures a user or rebuilds the router.
   */
  public function testInternalFrontPageUriRoundTrips(): void {
    $this->container->get('router.builder')->rebuild();
    $this->setUpCurrentUser(['name' => 'front_page_linker'], ['link to any page']);

    $field_name = $this->createCaseField([], 'internal_front_page');

    $node = Node::create(['type' => 'page', 'title' => 'internal front page']);
    $node->set($field_name, [['sub' => ['uri' => 'internal:/', 'options' => ['query' => NULL]]]]);
    $this->assertNoViolations($node);
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item);
    $this->assertSame('internal:/', $item->get('sub')->getValue());
    $this->assertNull($item->get('sub__title')->getValue());
    $this->assertSame(['query' => NULL], $item->get('sub__options')->getValue());
  }

  /**
   * A routed URI the user cannot reach raises the access violation.
   *
   * The retired test plugin's only violation case.
   */
  public function testInaccessibleRoutedUriIsRejected(): void {
    $this->container->get('router.builder')->rebuild();
    $this->setUpCurrentUser(['name' => 'limited'], ['access content']);

    $field_name = $this->createCaseField([], 'access_denied');

    $node = Node::create(['type' => 'page', 'title' => 'inaccessible']);
    $node->set($field_name, [['sub' => ['uri' => 'internal:/node/add']]]);

    $this->assertViolation(
      $node,
      'sub',
      "The path 'internal:/node/add' is inaccessible.",
      1,
      'A user without access to the route cannot link to it.',
    );
  }

  /**
   * The 'link to any page' permission bypasses the access check.
   */
  public function testLinkToAnyPagePermissionAllowsAnInaccessibleRoute(): void {
    $this->container->get('router.builder')->rebuild();
    $this->setUpCurrentUser(['name' => 'linker'], ['access content', 'link to any page']);

    $field_name = $this->createCaseField([], 'access_granted');

    $node = Node::create(['type' => 'page', 'title' => 'accessible']);
    $node->set($field_name, [['sub' => ['uri' => 'internal:/node/add']]]);

    $this->assertNoViolations(
      $node,
      'The permission allows linking to a route the user cannot otherwise reach.',
    );
  }

  /**
   * An omitted link_type is backfilled from the type's declared default.
   *
   * See UriTypeTest::testOmittedLinkTypeIsBackfilledFromTheTypeDefault() for
   * the full explanation of the backfill mechanism; this re-asserts it for
   * link's own column, since link stores its own field_settings separately
   * from uri's.
   */
  public function testOmittedLinkTypeIsBackfilledFromTheTypeDefault(): void {
    $field_name = 'field_link_no_link_type';
    $this->createCustomField(
      $field_name,
      ['sub' => ['name' => 'sub', 'type' => 'link']],
      // Deliberately no link_type, field_prefix or field_prefix_custom.
      ['sub' => ['label' => 'No link_type', 'check_empty' => FALSE]],
    );

    $node = Node::create(['type' => 'page', 'title' => 'no link_type']);
    $node->set($field_name, [['sub' => ['uri' => 'Not a URL']]]);

    $this->assertViolation(
      $node,
      'sub',
      "The path 'Not a URL' is invalid.",
      1,
      'An omitted link_type still validates, via the default backfill.',
    );
  }

  /**
   * Sample generation follows both the title and link_type settings.
   *
   * LinkType::generateSampleValue() returns a two-key ['uri', 'title']
   * array, layering its own title behavior on top of the uri shape
   * UriType::generateSampleValue() already covers (see
   * UriTypeTest::testSampleValueShapeFollowsLinkType()).
   *
   * @param int $title_setting
   *   The 'title' field setting under test.
   * @param int $link_type
   *   The 'link_type' field setting under test.
   * @param bool|null $title_non_empty
   *   TRUE if the generated title must be non-empty, FALSE if it must be
   *   empty, NULL if either is acceptable.
   * @param bool $external
   *   Whether the generated uri is expected to be an external URL.
   *
   * @dataProvider providerSampleValueShape
   */
  #[DataProvider('providerSampleValueShape')]
  public function testSampleValueShapeFollowsTitleSetting(int $title_setting, int $link_type, ?bool $title_non_empty, bool $external): void {
    $field_name = $this->createCaseField(
      ['settings' => ['title' => $title_setting, 'link_type' => $link_type]],
      'sample_' . $title_setting . '_' . $link_type,
    );

    $instance = $this->typeInstance(['title' => $title_setting, 'link_type' => $link_type]);
    $value = $instance::generateSampleValue($instance, 'node');

    $this->assertSame(['uri', 'title'], array_keys($value));
    $external
      ? $this->assertStringStartsWith('https://www.', $value['uri'])
      : $this->assertStringStartsWith('base:', $value['uri']);

    match ($title_non_empty) {
      TRUE => $this->assertNotSame('', $value['title']),
      FALSE => $this->assertSame('', $value['title']),
      NULL => $this->assertIsString($value['title']),
    };

    // The generated sample must also survive validation and a save.
    $this->assertGeneratedSampleSaves($field_name);
  }

  /**
   * Data provider for ::testSampleValueShapeFollowsTitleSetting().
   *
   * @return array<string, array{int, int, bool|null, bool}>
   *   Test cases.
   */
  public static function providerSampleValueShape(): array {
    return [
      'disabled title, generic link_type' => [self::TITLE_DISABLED, LinkTypeInterface::LINK_GENERIC, FALSE, TRUE],
      'required title, external only' => [self::TITLE_REQUIRED, LinkTypeInterface::LINK_EXTERNAL, TRUE, TRUE],
      'optional title, internal only' => [self::TITLE_OPTIONAL, LinkTypeInterface::LINK_INTERNAL, NULL, FALSE],
    ];
  }

  /**
   * Samples for multiple link_type settings validate and save together.
   *
   * Mirrors core's own LinkItemTest, which generates samples for a generic,
   * an external-only and an internal-only link field on the SAME entity and
   * saves them in a single call -- catching any interaction between sibling
   * fields that the per-field assertions in
   * ::testSampleValueShapeFollowsTitleSetting() would not.
   */
  public function testSampleValuesForMultipleLinkTypesSaveTogether(): void {
    $generic = $this->createCaseField(['settings' => ['link_type' => LinkTypeInterface::LINK_GENERIC]], 'multi_generic');
    $external = $this->createCaseField(['settings' => ['link_type' => LinkTypeInterface::LINK_EXTERNAL]], 'multi_external');
    $internal = $this->createCaseField(['settings' => ['link_type' => LinkTypeInterface::LINK_INTERNAL]], 'multi_internal');

    $node = Node::create(['type' => 'page', 'title' => 'multiple link types']);
    $node->get($generic)->generateSampleItems();
    $node->get($external)->generateSampleItems();
    $node->get($internal)->generateSampleItems();

    $this->entityValidateAndSave($node);

    $this->assertNotNull($node->id());
  }

  /**
   * The check_empty setting decides whether a blank uri drops the item.
   *
   * @param bool $check_empty
   *   The check_empty setting on the column under test.
   * @param string|null $value
   *   The value to store in that column.
   * @param bool $expected
   *   Whether the item is expected to survive the save.
   *
   * @dataProvider providerCheckEmpty
   */
  #[DataProvider('providerCheckEmpty')]
  public function testCheckEmpty(bool $check_empty, ?string $value, bool $expected): void {
    $field_name = 'field_ce_' . (int) $check_empty . '_' . ($value === NULL ? 'null' : ($value === '' ? 'empty' : 'set'));
    $this->createCustomField(
      $field_name,
      [
        'sub' => ['name' => 'sub', 'type' => 'link'],
        'keep' => ['name' => 'keep', 'type' => 'string'],
      ],
      [
        'sub' => ['label' => 'Under test', 'check_empty' => $check_empty],
        'keep' => ['label' => 'Populated sibling', 'check_empty' => FALSE],
      ],
    );

    $node = Node::create(['type' => 'page', 'title' => 'check_empty']);
    $node->set($field_name, [['sub' => $value, 'keep' => 'populated']]);
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $expected
      ? $this->assertNotNull($item, 'The item was kept.')
      : $this->assertNull($item, 'The item was dropped as empty.');
  }

  /**
   * Data provider for ::testCheckEmpty().
   *
   * @return array<string, array{bool, string|null, bool}>
   *   Test cases.
   */
  public static function providerCheckEmpty(): array {
    return [
      'check_empty on, blank value' => [TRUE, NULL, FALSE],
      'check_empty on, empty string' => [TRUE, '', FALSE],
      'check_empty on, populated value' => [TRUE, 'https://example.com', TRUE],
      'check_empty off, blank value' => [FALSE, NULL, TRUE],
      'check_empty off, empty string' => [FALSE, '', TRUE],
    ];
  }

  /**
   * CONTRACT: a title with no uri is wiped when a sibling keeps the item.
   *
   * Core's own link field decides emptiness from the uri alone --
   * LinkItem::isEmpty() only ever inspects 'uri' -- and
   * LinkTitleRequiredConstraintValidator only raises a violation when the
   * uri is NOT empty, so a title stored with no uri is treated as "nothing
   * here" throughout core's own link field too; this is not a quirk of this
   * module. The sibling-survives situation itself has no counterpart in
   * core, because core's link item IS the whole field item -- there is no
   * separate column that could keep a bare link item alive the way an
   * unrelated sibling column does for a multi-column custom_field row.
   * CustomItem::preSave()'s 'link' case applies the same uri-alone rule at
   * save time: whenever the uri is empty, both extra properties are forced
   * back to their empty state regardless of what was assigned to them,
   * which is what keeps this row internally consistent with an item that
   * was never given a title at all. Deliberate code, and the widget blocks
   * a user from ever reaching this state through the form; the programmatic
   * path has no such guard, so a title and options set alongside an empty
   * uri are silently discarded at save time rather than rejected.
   *
   * @see https://www.drupal.org/project/custom_field/issues/3558244
   */
  public function testTitleWithoutUriIsWipedWhenSiblingKeepsTheItem(): void {
    $field_name = 'field_link_title_wiped';
    $this->createCustomField(
      $field_name,
      [
        'sub' => ['name' => 'sub', 'type' => 'link'],
        'keep' => ['name' => 'keep', 'type' => 'string'],
      ],
      [
        'sub' => ['label' => 'Under test', 'check_empty' => FALSE],
        'keep' => ['label' => 'Populated sibling', 'check_empty' => FALSE],
      ],
    );

    $node = Node::create(['type' => 'page', 'title' => 'title wiped']);
    $node->set($field_name, [[
      'sub' => [
        'uri' => '',
        'title' => 'Example',
        'options' => ['attributes' => ['target' => '_blank']],
      ],
      'keep' => 'populated',
    ],
    ]);
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item, 'A populated sibling column keeps the item.');
    $this->assertNull($item->get('sub__title')->getValue(), 'The title is wiped when the uri is empty.');
    $this->assertSame([], $item->get('sub__options')->getValue(), 'The options are wiped to an empty array.');
  }

  /**
   * CONTRACT: a title with no uri drops the whole item when link is sole.
   *
   * Same uri-alone emptiness rule as
   * ::testTitleWithoutUriIsWipedWhenSiblingKeepsTheItem(), this time with
   * nothing else in the row to keep it alive: CustomItem::isEmpty() drops
   * the item entirely, so the title and options vanish along with it.
   * Core's own link field reaches the identical outcome for the identical
   * input, for the identical reason (LinkItem::isEmpty() and the uri-gated
   * LinkTitleRequired constraint both key off the uri alone).
   *
   * @param string|null $uri
   *   The uri value to store alongside the title.
   * @param string $suffix
   *   A short, unique field-name suffix for this case.
   *
   * @dataProvider providerTitleWithoutUriDropsSoleLinkItem
   */
  #[DataProvider('providerTitleWithoutUriDropsSoleLinkItem')]
  public function testTitleWithoutUriDropsSoleLinkItem(?string $uri, string $suffix): void {
    $field_name = $this->createCaseField([], $suffix);

    $node = Node::create(['type' => 'page', 'title' => 'title alone']);
    $node->set($field_name, [[
      'sub' => [
        'uri' => $uri,
        'title' => 'Example',
        'options' => ['attributes' => ['target' => '_blank']],
      ],
    ],
    ]);
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNull($item, 'The whole item is dropped: title and options vanish with it.');
  }

  /**
   * Data provider for ::testTitleWithoutUriDropsSoleLinkItem().
   *
   * @return array<string, array{string|null, string}>
   *   Test cases.
   */
  public static function providerTitleWithoutUriDropsSoleLinkItem(): array {
    return [
      'empty string uri' => ['', 'title_alone_empty'],
      'NULL uri' => [NULL, 'title_alone_null'],
    ];
  }

  /**
   * CHARACTERIZATION: a required title is not enforced outside the widget.
   *
   * Core's own link field enforces this with an item-level constraint,
   * LinkTitleRequired, declared directly on LinkItem's field type attribute
   * and checked whenever the title visibility is Required, the uri is not
   * empty and the title is empty (see
   * \Drupal\link\Plugin\Validation\Constraint\LinkTitleRequiredConstraintValidator).
   * Core moved this check out of the widget and into validation in Drupal
   * 11.4 (see https://www.drupal.org/node/3554139), a version already
   * within this module's own core_version_requirement. This module declares
   * no equivalent constraint, so the 'title' => self::TITLE_REQUIRED field
   * setting is enforced only by the widget's own form validation: any write
   * that does not go through the form -- JSON:API, migrate, a programmatic
   * save -- can store a "required title" link with no title at all. A
   * future fix that added an equivalent item-level constraint would flip
   * this test's expectation.
   */
  public function testRequiredTitleIsNotEnforcedOutsideTheForm(): void {
    $field_name = $this->createCaseField(['settings' => ['title' => self::TITLE_REQUIRED]], 'required_title');

    $node = Node::create(['type' => 'page', 'title' => 'required title']);
    $node->set($field_name, [['sub' => ['uri' => 'https://example.com']]]);

    $this->assertNoViolations(
      $node,
      'A required title is never enforced by validation; only the widget enforces it.',
    );
  }

  /**
   * CONTRACT: a Disabled title setting does not reject a stored title.
   *
   * Unlike the Required case above, this is not a gap relative to core:
   * core does not enforce its Disabled setting at validation time either --
   * on both core's link field and this module's, Disabled is purely a
   * widget affordance that hides the title field from the form. Kept as a
   * separate, unambiguously-contract test so the two kinds are never
   * confused with each other.
   */
  public function testDisabledTitleDoesNotRejectStoredTitle(): void {
    $field_name = $this->createCaseField(['settings' => ['title' => self::TITLE_DISABLED]], 'disabled_title_stored');

    $node = Node::create(['type' => 'page', 'title' => 'disabled title stored']);
    $node->set($field_name, [['sub' => ['uri' => 'https://example.com', 'title' => 'Example']]]);

    $this->assertNoViolations(
      $node,
      'A Disabled title setting does not reject a title that is stored anyway.',
    );
  }

  /**
   * CHARACTERIZATION: a uri longer than its column raises no violation.
   *
   * Link declares the same four link constraints as uri and nothing else
   * (see ::testConstraintsAreDeclaredOnTheChildClass()), so just like
   * UriType, nothing compares the uri value against the 2048 characters the
   * column actually holds. Asserted at the validation level only,
   * deliberately -- see UriTypeTest::testOverLengthValueProducesNoViolation()
   * for why a save-level assertion would be fragile in CI.
   */
  public function testOverLengthUriProducesNoViolation(): void {
    $field_name = $this->createCaseField([], 'over_length_uri');

    $node = Node::create(['type' => 'page', 'title' => 'over length uri']);
    $node->set($field_name, [['sub' => ['uri' => self::overLengthUri()]]]);

    $this->assertNoViolations(
      $node,
      'A 2049-character uri is accepted by validation although the column holds 2048.',
    );
  }

  /**
   * CHARACTERIZATION: a title longer than its column raises no violation.
   *
   * The length guard that exists for the title lives only in the widget's
   * own JavaScript/form validation, not as a Length constraint on the
   * sub__title property -- so every write that bypasses the widget is
   * unchecked. Asserted at the validation level only, for the same reason as
   * ::testOverLengthUriProducesNoViolation().
   *
   * @see https://www.drupal.org/project/custom_field/issues/3585045
   */
  public function testOverLengthTitleProducesNoViolation(): void {
    $field_name = $this->createCaseField([], 'over_length_title');

    $node = Node::create(['type' => 'page', 'title' => 'over length title']);
    $node->set($field_name, [[
      'sub' => [
        'uri' => 'https://example.com',
        'title' => str_repeat('a', 256),
      ],
    ],
    ]);

    $this->assertNoViolations(
      $node,
      'A 256-character title is accepted by validation although the column holds 255.',
    );
  }

  /**
   * CHARACTERIZATION: getMaxlength() agrees with neither column it describes.
   *
   * The getMaxlength() accessor returns 255 -- the base class's generic
   * constant, since neither UriType nor LinkType declares a 'length' setting
   * of its own -- while the uri column is actually 2048 wide. The title
   * column happens to also be 255, but that is a coincidence: nothing in the
   * module calls getMaxlength() for the title, so the agreement there is not
   * evidence of anything.
   */
  public function testGetMaxlengthDisagreesWithTheSchemaWidths(): void {
    $this->assertSame(255, $this->typeInstance()->getMaxlength());

    $schema = LinkType::schema(['name' => 'sub']);
    $this->assertSame(2048, $schema['sub']['length'], 'The uri column really is 2048 wide.');
    $this->assertSame(255, $schema['sub__title']['length'], 'The title column is also 255, coincidentally.');
  }

  /**
   * CHARACTERIZATION: a falsy link_type switches validation off entirely.
   *
   * See UriTypeTest::testFalsyLinkTypeDisablesValidationEntirely() for the
   * full mechanism; this re-asserts it for link's own column. A stored 0 is
   * not reachable through the settings form, which offers only the three
   * documented bit combinations, so this records a programmatic write. A
   * future fix would keep the format check independent of the setting.
   */
  public function testFalsyLinkTypeDisablesValidationEntirely(): void {
    $field_name = 'field_link_zero_link_type';
    $this->createCustomField(
      $field_name,
      ['sub' => ['name' => 'sub', 'type' => 'link']],
      ['sub' => ['label' => 'Falsy link_type', 'check_empty' => FALSE, 'link_type' => 0]],
    );

    $node = Node::create(['type' => 'page', 'title' => 'falsy link_type']);
    $node->set($field_name, [['sub' => ['uri' => 'Not a URL']]]);

    $this->assertNoViolations(
      $node,
      'With a falsy link_type nothing validates the URI at all.',
    );
  }

  /**
   * CHARACTERIZATION: an unnormalized stored value makes getUrl() throw.
   *
   * There is no 'link' branch in CustomItem::preSave() that normalizes the
   * uri itself (the existing 'link' branch only clears the title/options
   * when the uri is empty), and the only normalization this type has lives
   * in the widget's own massageFormValue(), so any write that does not come
   * through the form stores the raw string. Saving does not validate, so a
   * schemeless value reaches storage intact and then throws when anything
   * asks it for a Url.
   */
  public function testGetUrlThrowsOnAnUnnormalizedStoredValue(): void {
    $field_name = 'field_link_unnormalized';
    $this->createCustomField(
      $field_name,
      [
        'sub' => ['name' => 'sub', 'type' => 'link'],
        'keep' => ['name' => 'keep', 'type' => 'string'],
      ],
      [
        'sub' => ['label' => 'Under test', 'check_empty' => FALSE],
        'keep' => ['label' => 'Populated sibling', 'check_empty' => FALSE],
      ],
    );

    $node = Node::create(['type' => 'page', 'title' => 'unnormalized']);
    $node->set($field_name, [['sub' => ['uri' => 'www.example.com'], 'keep' => 'populated']]);
    // No validate() call: save() alone never validates.
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item);
    $this->assertSame(
      'www.example.com',
      $item->get('sub')->getValue(),
      'The schemeless value was stored exactly as assigned.',
    );

    $this->expectException(\InvalidArgumentException::class);
    $this->linkTypeInstance()->getUrl($item);
  }

  /**
   * CHARACTERIZATION: a malformed options blob makes getUrl() throw \TypeError.
   *
   * Not \InvalidArgumentException: nothing validates the structure of the
   * options map on the way in -- it is stored as a serialized blob with no
   * shape checking at all. Core's own
   * \Drupal\Core\Url::fromUri() calls strlen() directly on the 'fragment'
   * option, and passing an array where a string is expected raises a
   * \TypeError in PHP 8's strict parameter typing for internal functions.
   * That is a \Error, not an \Exception, so it is NOT caught by the
   * \InvalidArgumentException handling this module's own constraint
   * validators use elsewhere (see
   * \Drupal\custom_field\Plugin\Validation\Constraint\LinkAccessConstraintValidator)
   * -- an unnormalized options blob can crash a caller that merely asks for
   * the Url, uncaught.
   */
  public function testGetUrlThrowsTypeErrorOnMalformedOptionsBlob(): void {
    $field_name = $this->createCaseField([], 'malformed_options');

    $node = Node::create(['type' => 'page', 'title' => 'malformed options']);
    $node->set($field_name, [[
      'sub' => [
        'uri' => 'https://example.com',
        'options' => ['fragment' => ['not-a-string']],
      ],
    ],
    ]);
    // No validate() call: nothing validates the structure of the options map.
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item);

    $this->expectException(\TypeError::class);
    $this->linkTypeInstance()->getUrl($item);
  }

  /**
   * A repeated programmatic setValue() must keep the title and options.
   *
   * This is the JSON:API / REST / migrate / custom-code shape: the nested
   * value array with no widget promotion involved. Core's Map::setValue()
   * re-syncs every ALREADY INSTANTIATED property object from the incoming
   * values array, so any sibling missing from that array is reset to NULL on
   * a second pass -- LinkType::extractExtraPropertyValues() exists to
   * pre-fill the siblings back into that array before Map::setValue() runs,
   * which is the regression this test guards against.
   *
   * @see https://www.drupal.org/project/custom_field/issues/3622588
   */
  public function testProgrammaticNestedValueSurvivesRepeatedSetValue(): void {
    $field_name = 'field_link_repeated_set';
    $this->createCustomField(
      $field_name,
      ['sub' => ['name' => 'sub', 'type' => 'link']],
      ['sub' => ['label' => 'Link', 'check_empty' => FALSE]],
    );

    $values = [
      'sub' => [
        'uri' => 'https://example.com',
        'title' => 'Example',
        'options' => ['attributes' => ['target' => '_blank']],
      ],
    ];

    $node = Node::create(['type' => 'page', 'title' => 'Programmatic']);
    $items = $node->get($field_name);

    $items->setValue([$values]);
    $this->assertNoViolations($node, 'Set on first pass.');
    $first = $items[0];
    $this->assertNotNull($first);
    $this->assertSame('Example', $first->get('sub__title')->getValue(), 'Set on first pass.');

    $items->setValue([$values]);
    $second = $items[0];
    $this->assertNotNull($second);
    $this->assertSame('Example', $second->get('sub__title')->getValue(), 'Survives second pass.');

    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item);
    $this->assertSame('https://example.com', $item->get('sub')->getValue());
    $this->assertSame('Example', $item->get('sub__title')->getValue());
    $this->assertSame(['attributes' => ['target' => '_blank']], $item->get('sub__options')->getValue());
  }

  /**
   * The type plugin maps a nested value onto its extra property names.
   *
   * Drives LinkType::extractExtraPropertyValues() directly, with no entity
   * or save involved, so a break here points squarely at the extraction
   * mapping itself. The type class is resolved from the plugin manager's own
   * definitions, rather than hardcoded, so this still exercises the real
   * class if it is ever moved or aliased.
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

    $this->assertSame($expected, $class::extractExtraPropertyValues(['name' => 'sub'], $value));
  }

  /**
   * Data provider for ::testExtraPropertyMapping().
   *
   * @return array<string, array{mixed, array<string, mixed>}>
   *   Test cases.
   */
  public static function providerMapping(): array {
    $base = ['uri' => 'https://example.com'];

    return [
      'title missing' => [$base, []],
      'title NULL' => [$base + ['title' => NULL], []],
      'title empty string' => [$base + ['title' => ''], ['sub__title' => '']],
      'title valid' => [$base + ['title' => 'Example'], ['sub__title' => 'Example']],
      // Replaces an 'options missing' row whose input was byte-identical to
      // 'title valid' above, and so tested nothing that row did not. Both
      // siblings extracted together, with a non-empty options map, is the one
      // shape the other rows leave uncovered: 'options populated' supplies no
      // title, and 'options empty array' supplies an empty map.
      'title and populated options extract together' => [
        $base + ['title' => 'Example', 'options' => ['attributes' => ['target' => '_blank']]],
        ['sub__title' => 'Example', 'sub__options' => ['attributes' => ['target' => '_blank']]],
      ],
      'options NULL' => [
        $base + ['title' => 'Example', 'options' => NULL],
        ['sub__title' => 'Example'],
      ],
      'options empty string is not an array' => [
        $base + ['title' => 'Example', 'options' => ''],
        ['sub__title' => 'Example'],
      ],
      'options empty array' => [
        $base + ['title' => 'Example', 'options' => []],
        ['sub__title' => 'Example', 'sub__options' => []],
      ],
      'options populated' => [
        $base + ['options' => ['attributes' => ['target' => '_blank']]],
        ['sub__options' => ['attributes' => ['target' => '_blank']]],
      ],
      'uri empty string still extracts' => [
        ['uri' => '', 'title' => 'Example'],
        ['sub__title' => 'Example'],
      ],
      'not an array' => ['https://example.com', []],
      'empty array' => [[], []],
    ];
  }

  /**
   * Returns a sibling property's raw value, without instantiating it.
   *
   * Reading the magic property, rather than get()->getValue(), is the point
   * wherever this is used: FieldItemBase::__get() returns the raw stored
   * value as long as nothing has instantiated the property object yet, and
   * for the options map the raw value is the only layer on which an absent
   * map and an empty one still differ. The property name is passed as a
   * variable so this stays a single, documented place where that read
   * happens.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The stored item.
   * @param string $property
   *   The property name, e.g. 'sub__options'.
   *
   * @return mixed
   *   The raw value, or NULL when nothing was stored for that property.
   */
  private function rawValue(FieldItemInterface $item, string $property): mixed {
    return $item->{$property};
  }

  /**
   * Builds a configured plugin instance the way production code does.
   *
   * @param array<string, mixed> $settings
   *   Field settings to merge over the defaults.
   *
   * @return \Drupal\custom_field\Plugin\CustomFieldTypeInterface
   *   The configured instance.
   */
  private function typeInstance(array $settings = []): CustomFieldTypeInterface {
    $column = $this->defaultColumn($this->columnName());
    $options = $this->typeManager->createOptionsForInstance(
      [...$this->defaultFieldSettings(), ...$settings],
      $column,
    );
    $instance = $this->typeManager->createInstance(static::typeId(), $options['configuration']);
    $this->assertInstanceOf(CustomFieldTypeInterface::class, $instance);

    return $instance;
  }

  /**
   * Returns the instance narrowed to the link-type contract.
   *
   * @return \Drupal\custom_field\Plugin\CustomField\FieldType\LinkTypeInterface
   *   The configured instance.
   */
  private function linkTypeInstance(): LinkTypeInterface {
    $instance = $this->typeInstance();
    $this->assertInstanceOf(LinkTypeInterface::class, $instance);

    return $instance;
  }

  /**
   * Returns a valid external URI of exactly 2049 characters.
   *
   * One character past the 2048-character width of the uri column.
   *
   * @return string
   *   The URI.
   */
  private static function overLengthUri(): string {
    $base = 'https://example.com/';
    return $base . str_repeat('a', 2049 - strlen($base));
  }

}
