<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Kernel\CustomField\Type;

use Drupal\custom_field\Plugin\CustomField\FieldType\LinkTypeInterface;
use Drupal\custom_field\Plugin\CustomField\FieldType\UriType;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\node\Entity\Node;
use Drupal\Tests\custom_field\Kernel\CustomField\CustomFieldTypeKernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the 'uri' sub-field type.
 *
 * The obvious core counterpart, core/modules/field/tests/src/Kernel/Uri/
 * UriItemTest.php, is the model for this class's SHAPE only. Core's own uri
 * field type applies no URI validation whatsoever — UriItem extends StringItem
 * without overriding getConstraints(), so the only constraint in that chain is
 * a Length check, and its widget is a bare HTML5 url input — which is why
 * core's test does nothing but generate a sample, validate and save.
 *
 * This module's UriType is modelled on core's LINK module instead: it declares
 * four constraints that are one-for-one counterparts of core link's LinkType,
 * LinkAccess, LinkExternalProtocols and LinkNotExistingInternal. The coverage
 * model is therefore core/modules/link/tests/src/Kernel/
 * LinkItemUrlValidationTest.php, which drives a table of uri => expected
 * message pairs through validate(), exactly the shape validationCases() has.
 *
 * Two things worth knowing before reading the cases:
 *
 * Everything hinges on \Drupal\Core\Url::fromUri(), which requires an explicit
 * scheme: a schemeless value such as 'node/1' or 'Not a URL' throws
 * \InvalidArgumentException. All four validators catch that exception; three
 * return silently and only the link-type one turns it into a violation. So a
 * malformed value yields exactly ONE violation here, where core link reports
 * two — see ::testSchemelessValueProducesOnlyThePathViolation() for why the
 * second one is missing.
 *
 * Routed internal URIs are kept out of validationCases() deliberately. The
 * access constraint is live in a kernel test, so an inaccessible route would
 * raise an access violation that reads exactly like a scheme failure; core's
 * own validation test sidesteps this by testing external URLs only. Access is
 * covered on its own terms in the two dedicated tests below.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 * @covers \Drupal\custom_field\Plugin\CustomField\FieldType\UriType
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
#[CoversClass(UriType::class)]
class UriTypeTest extends CustomFieldTypeKernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['link'];

  /**
   * A URI padded to exactly the 2048 characters the column allows.
   */
  private const MAX_LENGTH_URI = 'https://example.com/';

  /**
   * {@inheritdoc}
   */
  protected static function typeId(): string {
    return 'uri';
  }

  /**
   * {@inheritdoc}
   *
   * Stated explicitly rather than relying on the backfill in
   * CustomFieldTypeBase::getFieldSettings(), so that the cases below do not
   * depend on it. That backfill is itself pinned, in
   * ::testOmittedLinkTypeIsBackfilledFromTheTypeDefault().
   */
  protected function defaultFieldSettings(): array {
    return [
      'link_type' => LinkTypeInterface::LINK_GENERIC,
      'field_prefix' => 'default',
      'field_prefix_custom' => '',
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  protected function roundTripCases(): array {
    return [
      'external https URL' => [
        'value' => ['sub' => 'https://www.drupal.com'],
        'expected' => ['sub' => 'https://www.drupal.com'],
      ],
      // 'base:' is what generateSampleValue() emits for an internal-only
      // column: internal, but unrouted, so no route needs to exist for it.
      'base scheme, unrouted internal' => [
        'value' => ['sub' => 'base:some-path'],
        'expected' => ['sub' => 'base:some-path'],
      ],
      'query string and fragment are preserved' => [
        'value' => ['sub' => 'https://example.com/search?q=drupal&page=2#results'],
        'expected' => ['sub' => 'https://example.com/search?q=drupal&page=2#results'],
      ],
      // The column is varchar(2048); a value at exactly that width proves the
      // declared width is the width that is really available.
      // See https://www.drupal.org/project/custom_field/issues/3521826.
      'a URI of exactly 2048 characters' => [
        'value' => ['sub' => self::maxLengthUri()],
        'expected' => ['sub' => self::maxLengthUri()],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function validationCases(): array {
    $invalid = "The path '@uri' is invalid.";

    return [
      // Url::fromUri() rejects anything without a scheme, which is the one
      // case the deleted test plugin covered.
      'a string with no URI scheme' => [
        'value' => ['sub' => 'Not a URL'],
        'property' => 'sub',
        'message' => str_replace('@uri', 'Not a URL', $invalid),
      ],
      'a root-relative path, which is also schemeless' => [
        'value' => ['sub' => '/node/1'],
        'property' => 'sub',
        'message' => str_replace('@uri', '/node/1', $invalid),
      ],
      'an unknown external protocol' => [
        'value' => ['sub' => 'invalid://example.com'],
        'property' => 'sub',
        'message' => str_replace('@uri', 'invalid://example.com', $invalid),
      ],
      'a protocol outside the allowed list' => [
        'value' => ['sub' => 'ftps://example.com/file'],
        'property' => 'sub',
        'message' => str_replace('@uri', 'ftps://example.com/file', $invalid),
      ],
      'a javascript URI' => [
        'value' => ['sub' => 'javascript:alert(1)'],
        'property' => 'sub',
        'message' => str_replace('@uri', 'javascript:alert(1)', $invalid),
      ],
      // 'internal:' demands a leading slash on the path component.
      'an internal URI with no leading slash' => [
        'value' => ['sub' => 'internal:node/1'],
        'property' => 'sub',
        'message' => str_replace('@uri', 'internal:node/1', $invalid),
      ],
      'a syntactically broken URI' => [
        'value' => ['sub' => 'http://'],
        'property' => 'sub',
        'message' => str_replace('@uri', 'http://', $invalid),
      ],
    ];
  }

  /**
   * The schema is a single varchar column 2048 characters wide.
   *
   * The absence of __title and __options is the boundary against LinkType,
   * which extends this type and adds both.
   */
  public function testSchema(): void {
    $this->assertSame(
      ['sub' => ['type' => 'varchar', 'length' => 2048]],
      UriType::schema(['name' => 'sub']),
    );
  }

  /**
   * The property definition is a single, optional link-typed property.
   */
  public function testPropertyDefinitions(): void {
    $properties = UriType::propertyDefinitions(['name' => 'sub']);

    $this->assertSame(['sub'], array_keys($properties));
    $this->assertSame('custom_field_link', $properties['sub']->getDataType());
    $this->assertFalse($properties['sub']->isRequired());
    // LinkType sets a 'field_type' setting on its own property; uri does not.
    $this->assertNull($properties['sub']->getSetting('field_type'));
  }

  /**
   * The declared default field settings, including uri's own three.
   */
  public function testDefaultFieldSettings(): void {
    $this->assertSame([
      'link_type' => LinkTypeInterface::LINK_GENERIC,
      'field_prefix' => 'default',
      'field_prefix_custom' => '',
      'label' => '',
      'required' => FALSE,
      'translatable' => FALSE,
      'description' => '',
      'description_display' => 'after',
      'check_empty' => FALSE,
    ], UriType::defaultFieldSettings());
  }

  /**
   * All four constraints are declared, whatever the configured settings.
   */
  public function testConstraintsAreDeclaredUnconditionally(): void {
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
   * A schemeless value raises the path violation and nothing else.
   *
   * Core's link field reports two violations for this input: the path message
   * plus "This value should be of the correct primitive type." The second one
   * comes from the PrimitiveType constraint that typed data attaches to any
   * data type whose class implements PrimitiveInterface — its schemeless-URI
   * branch is gated on the value object also implementing UriInterface, which
   * core's uri data type does and this module's custom_field_link data type
   * does not. The constraint is therefore attached here but can never fire,
   * so link-type validation is the only thing standing between a malformed
   * URI and storage. A future fix would make this two violations.
   */
  public function testSchemelessValueProducesOnlyThePathViolation(): void {
    $field_name = $this->createCaseField([], 'primitive');

    $node = Node::create(['type' => 'page', 'title' => 'schemeless']);
    $node->set($field_name, [['sub' => 'Not a URL']]);

    $violations = $node->validate();
    $this->assertCount(1, $violations, 'Only one violation: ' . (string) $violations);

    $violation = $violations[0];
    $this->assertNotNull($violation);
    $this->assertSame(
      "The path 'Not a URL' is invalid.",
      strip_tags((string) $violation->getMessage()),
    );
  }

  /**
   * A value longer than the column raises no violation at all.
   *
   * This type declares no Length constraint: getConstraints() returns the four
   * link constraints and nothing else, and the property's data type adds none,
   * so nothing compares the value against the 2048 characters the column
   * actually holds. The widget caps its input at 2048, so the form path is
   * safe, but any other write is unchecked — the same shape as a numeric
   * column whose range guard ignores the real column width.
   *
   * Asserted at the validation level only, deliberately. What a save does with
   * an over-length value depends on the database — MySQL in strict mode
   * errors, MySQL otherwise truncates, PostgreSQL errors, SQLite stores the
   * whole string — so a save-level assertion would be fragile in CI without
   * proving anything more about this type.
   */
  public function testOverLengthValueProducesNoViolation(): void {
    $field_name = $this->createCaseField([], 'over_length');

    $node = Node::create(['type' => 'page', 'title' => 'over length']);
    $node->set($field_name, [['sub' => self::MAX_LENGTH_URI . str_repeat('a', 2049 - strlen(self::MAX_LENGTH_URI))]]);

    $this->assertNoViolations(
      $node,
      'A 2049-character value is accepted by validation although the column holds 2048.',
    );
  }

  /**
   * An omitted link_type is backfilled from the type's declared default.
   *
   * CustomFieldTypeManager::createOptionsForInstance() passes the stored
   * field_settings through verbatim, but the base class's getFieldSettings()
   * unions them over static::defaultFieldSettings(), so a field whose settings
   * never mention link_type still validates as LINK_GENERIC. This is what
   * keeps link-type validation working on fields created programmatically,
   * which never go through the settings form.
   */
  public function testOmittedLinkTypeIsBackfilledFromTheTypeDefault(): void {
    $field_name = 'field_uri_no_link_type';
    $this->createCustomField(
      $field_name,
      ['sub' => ['name' => 'sub', 'type' => 'uri']],
      // Deliberately no link_type, field_prefix or field_prefix_custom.
      ['sub' => ['label' => 'No link_type', 'check_empty' => FALSE]],
    );

    $node = Node::create(['type' => 'page', 'title' => 'no link_type']);
    $node->set($field_name, [['sub' => 'Not a URL']]);

    $this->assertViolation(
      $node,
      'sub',
      "The path 'Not a URL' is invalid.",
      1,
      'An omitted link_type still validates, via the default backfill.',
    );
  }

  /**
   * A falsy link_type switches link-type validation off entirely.
   *
   * The validator reads the setting and returns without a violation when it is
   * falsy. Combined with the inert PrimitiveType constraint described in
   * ::testSchemelessValueProducesOnlyThePathViolation(), and with the other
   * three validators catching the malformed-URI exception and returning, this
   * leaves a column that accepts any garbage with no violation at all. A
   * stored 0 is not reachable through the settings form, which offers only
   * the three documented bit combinations, so this records a programmatic
   * write. A future fix would keep the format check independent of the
   * setting.
   */
  public function testFalsyLinkTypeDisablesValidationEntirely(): void {
    $field_name = 'field_uri_zero_link_type';
    $this->createCustomField(
      $field_name,
      ['sub' => ['name' => 'sub', 'type' => 'uri']],
      ['sub' => ['label' => 'Falsy link_type', 'check_empty' => FALSE, 'link_type' => 0]],
    );

    $node = Node::create(['type' => 'page', 'title' => 'falsy link_type']);
    $node->set($field_name, [['sub' => 'Not a URL']]);

    $this->assertNoViolations(
      $node,
      'With a falsy link_type nothing validates the URI at all.',
    );
  }

  /**
   * The link_type setting restricts which kind of URI the column accepts.
   *
   * @param int $link_type
   *   The link_type setting under test.
   * @param string $uri
   *   The URI to write.
   * @param bool $valid
   *   Whether the value is expected to validate.
   *
   * @dataProvider providerLinkType
   */
  #[DataProvider('providerLinkType')]
  public function testLinkTypeRestriction(int $link_type, string $uri, bool $valid): void {
    $field_name = $this->createCaseField(
      ['settings' => ['link_type' => $link_type]],
      'lt_' . $link_type . '_' . (int) $valid,
    );

    $node = Node::create(['type' => 'page', 'title' => 'link_type']);
    $node->set($field_name, [['sub' => $uri]]);

    $context = sprintf('[link_type %d / %s]', $link_type, $uri);
    $valid
      ? $this->assertNoViolations($node, $context)
      : $this->assertViolation(
          $node,
          'sub',
          str_replace('@uri', $uri, "The path '@uri' is invalid."),
          1,
          $context,
        );
  }

  /**
   * Data provider for ::testLinkTypeRestriction().
   *
   * @return array<string, array{int, string, bool}>
   *   Test cases.
   */
  public static function providerLinkType(): array {
    return [
      'external only, external URL' => [LinkTypeInterface::LINK_EXTERNAL, 'https://example.com', TRUE],
      'external only, internal URI' => [LinkTypeInterface::LINK_EXTERNAL, 'base:some-path', FALSE],
      'internal only, internal URI' => [LinkTypeInterface::LINK_INTERNAL, 'base:some-path', TRUE],
      'internal only, external URL' => [LinkTypeInterface::LINK_INTERNAL, 'https://example.com', FALSE],
      'generic, external URL' => [LinkTypeInterface::LINK_GENERIC, 'https://example.com', TRUE],
      'generic, internal URI' => [LinkTypeInterface::LINK_GENERIC, 'base:some-path', TRUE],
    ];
  }

  /**
   * A routed URI the user cannot reach raises the access violation.
   */
  public function testInaccessibleRoutedUriIsRejected(): void {
    $this->container->get('router.builder')->rebuild();
    $this->setUpCurrentUser(['name' => 'limited'], ['access content']);

    $field_name = $this->createCaseField([], 'access_denied');

    $node = Node::create(['type' => 'page', 'title' => 'inaccessible']);
    $node->set($field_name, [['sub' => 'internal:/admin']]);

    $this->assertViolation(
      $node,
      'sub',
      "The path 'internal:/admin' is inaccessible.",
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
    $node->set($field_name, [['sub' => 'internal:/admin']]);

    $this->assertNoViolations(
      $node,
      'The permission allows linking to a route the user cannot otherwise reach.',
    );
  }

  /**
   * External URLs and internal URIs are told apart by isExternal().
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
    $node->set($field_name, [['sub' => $uri]]);
    $node->save();

    $item = $this->storedItem($node, $field_name);
    $this->assertNotNull($item);

    $instance = $this->linkTypeInstance();
    $this->assertSame($expected, $instance->isExternal($item), $uri);
    $this->assertSame($uri, $instance->getUrl($item)->getUri());
  }

  /**
   * Data provider for ::testIsExternal().
   *
   * @return array<string, array{string, bool}>
   *   Test cases.
   */
  public static function providerIsExternal(): array {
    return [
      'external https URL' => ['https://www.drupal.com', TRUE],
      'base scheme is internal' => ['base:some-path', FALSE],
    ];
  }

  /**
   * A stored value that was never normalized makes getUrl() throw.
   *
   * There is no uri branch in CustomItem::preSave(), and the only
   * normalization this type has lives in the widget's massageFormValue(), so
   * any write that does not come through the form stores the raw string.
   * Saving does not validate, so a schemeless value reaches storage intact and
   * then throws when anything asks it for a Url. The shipped formatter and SDC
   * prop widget both catch this; token replacement and the Entity Usage
   * tracker do not. A future fix would normalize on write instead.
   */
  public function testGetUrlThrowsOnAnUnnormalizedStoredValue(): void {
    $field_name = 'field_uri_unnormalized';
    $this->createCustomField(
      $field_name,
      [
        'sub' => ['name' => 'sub', 'type' => 'uri'],
        'keep' => ['name' => 'keep', 'type' => 'string'],
      ],
      [
        'sub' => ['label' => 'Under test', 'check_empty' => FALSE],
        'keep' => ['label' => 'Populated sibling', 'check_empty' => FALSE],
      ],
    );

    $node = Node::create(['type' => 'page', 'title' => 'unnormalized']);
    $node->set($field_name, [['sub' => 'www.example.com', 'keep' => 'populated']]);
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
   * Sample generation follows the configured link_type.
   *
   * @param int $link_type
   *   The link_type setting under test.
   * @param bool $external
   *   Whether the generated sample is expected to be an external URL.
   *
   * @dataProvider providerSampleShape
   */
  #[DataProvider('providerSampleShape')]
  public function testSampleValueShapeFollowsLinkType(int $link_type, bool $external): void {
    $field_name = $this->createCaseField(
      ['settings' => ['link_type' => $link_type]],
      'sample_' . $link_type,
    );

    $instance = $this->typeInstance(['link_type' => $link_type]);
    $value = $instance::generateSampleValue($instance, 'node');

    $this->assertArrayHasKey('uri', $value);
    $external
      ? $this->assertStringStartsWith('https://www.', $value['uri'])
      : $this->assertStringStartsWith('base:', $value['uri']);

    // The generated sample must also survive validation and a save.
    $this->assertGeneratedSampleSaves($field_name);
  }

  /**
   * Data provider for ::testSampleValueShapeFollowsLinkType().
   *
   * @return array<string, array{int, bool}>
   *   Test cases.
   */
  public static function providerSampleShape(): array {
    return [
      'generic generates an external URL' => [LinkTypeInterface::LINK_GENERIC, TRUE],
      'external only generates an external URL' => [LinkTypeInterface::LINK_EXTERNAL, TRUE],
      'internal only generates a base: URI' => [LinkTypeInterface::LINK_INTERNAL, FALSE],
    ];
  }

  /**
   * The reported maxlength is 255, not the 2048 the column actually allows.
   *
   * The inherited accessor returns the column's own 'length' setting or the
   * base class constant, and uri has no length setting and declares no
   * constant of its own — so it reports a width that matches neither its
   * schema() (2048) nor the widget's maxlength (also 2048). Nothing in the
   * module calls this accessor today, so the disagreement is currently
   * invisible; a future fix would make all three agree.
   */
  public function testGetMaxlengthDisagreesWithTheSchemaWidth(): void {
    $this->assertSame(255, $this->typeInstance()->getMaxlength());
    $this->assertSame(
      2048,
      UriType::schema(['name' => 'sub'])['sub']['length'],
      'The column really is 2048 wide.',
    );
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
        'sub' => ['name' => 'sub', 'type' => 'uri'],
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
   * The getUrl() and isExternal() methods are declared on LinkTypeInterface
   * rather than on CustomFieldTypeInterface, which is also how the constraint
   * validators reach them.
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
   * Returns a valid external URI of exactly 2048 characters.
   *
   * @return string
   *   The URI.
   */
  private static function maxLengthUri(): string {
    return self::MAX_LENGTH_URI . str_repeat('a', 2048 - strlen(self::MAX_LENGTH_URI));
  }

}
