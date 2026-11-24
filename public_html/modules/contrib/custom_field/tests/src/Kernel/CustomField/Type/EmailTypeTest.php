<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Kernel\CustomField\Type;

use Drupal\custom_field\Plugin\CustomField\FieldType\EmailType;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\node\Entity\Node;
use Drupal\Tests\custom_field\Kernel\CustomField\CustomFieldTypeKernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the 'email' sub-field type.
 *
 * Modelled on core's field type Kernel tests, closest counterpart
 * core/modules/field/tests/src/Kernel/Email/EmailItemTest.php.
 *
 * EmailType extends CustomFieldTypeBase directly (not StringType) and its
 * schema(), propertyDefinitions() and getConstraints() all ignore $settings
 * entirely, so there is no settings-variation matrix to build. Its own
 * getConstraints() adds only a Length constraint sized from its own
 * EMAIL_MAX_LENGTH constant (254); it does not itself add any address-format
 * constraint.
 *
 * Address format IS still enforced, but through a separate mechanism, not
 * EmailType::getConstraints(). Drupal's field-item validation validates each
 * sub-field twice, independently: once through CustomItem's own explicit
 * constraint list (built only from EmailType::getConstraints(), i.e. Length
 * only), and once more through a second pass that validates each property
 * object on its own terms, against whatever constraints its own typed-data
 * type declares. Because propertyDefinitions() uses core's own 'email'
 * primitive (not a plain string), that second pass picks up the strict
 * email-format constraint core already attaches to its 'email' type, with no
 * extra code in this module at all. See ::testEnforcesEmailFormat() below,
 * and contrast with UuidType, which uses the plain 'string' primitive and
 * gets no such format check from either pass.
 *
 * @group custom_field
 * @runTestsInSeparateProcesses
 * @covers \Drupal\custom_field\Plugin\CustomField\FieldType\EmailType
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
#[CoversClass(EmailType::class)]
class EmailTypeTest extends CustomFieldTypeKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static function typeId(): string {
    return 'email';
  }

  /**
   * A domain-heavy address of an exact total length, valid by construction.
   *
   * The strict email-format check (see the class docblock) caps the local
   * part at 64 characters on its own, so a boundary-length address cannot be
   * built by padding the local part the way the Length constraint alone
   * would suggest — that would trip the format check for an unrelated
   * reason. Padding the domain instead, in labels short enough to stay valid
   * on their own, reaches an exact total length without doing that.
   */
  private static function addressOfLength(int $total): string {
    $local = 'a';
    $remaining = $total - strlen($local) - 1;
    $labels = [];
    while ($remaining > 0) {
      $label_length = min(60, $remaining);
      if ($remaining - $label_length > 0 && $remaining - $label_length < 2) {
        // Avoid leaving a 1-character remainder that can't form its own
        // label plus a separating dot.
        $label_length = $remaining;
      }
      $labels[] = str_repeat('a', $label_length);
      $remaining -= $label_length;
      if ($remaining > 0) {
        $remaining--;
      }
    }
    return $local . '@' . implode('.', $labels);
  }

  /**
   * {@inheritdoc}
   */
  protected function roundTripCases(): array {
    return [
      'plain email value' => [
        'value' => ['sub' => 'test@example.com'],
        'expected' => ['sub' => 'test@example.com'],
      ],
      'at the email length boundary (254 characters)' => [
        'value' => ['sub' => self::addressOfLength(254)],
        'expected' => ['sub' => self::addressOfLength(254)],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   *
   * The over-length case is deliberately NOT here — going over
   * EMAIL_MAX_LENGTH also, unavoidably, trips the strict-format check
   * described in the class docblock at the same time (EMAIL_MAX_LENGTH, 254,
   * coincides with the same total-address-length ceiling that check enforces
   * on its own), so it is a two-message case that cannot be expressed as a
   * single property/message pair here. See
   * ::testOverLengthAddressProducesTwoViolations() below, mirroring how
   * DecimalTypeTest handles its own two-violation case.
   */
  protected function validationCases(): array {
    return [
      'a syntactically invalid address' => [
        'value' => ['sub' => 'not-an-email'],
        'property' => 'sub',
        'message' => 'This value is not a valid email address.',
      ],
    ];
  }

  /**
   * CONTRACT: an address one character over the length boundary is invalid.
   *
   * Asserts the set of violation messages rather than trusting violations[0]
   * — going over EMAIL_MAX_LENGTH always trips both the Length constraint
   * EmailType declares and the strict email-format check described in the
   * class docblock, and their relative order is NOT VERIFIED.
   */
  public function testOverLengthAddressProducesTwoViolations(): void {
    $field_name = $this->createCaseField([], 'over_length');

    $node = Node::create(['type' => 'page', 'title' => 'over length address']);
    $node->set($field_name, [['sub' => self::addressOfLength(255)]]);

    $violations = $node->validate();
    $this->assertCount(2, $violations, (string) $violations);

    $messages = [];
    foreach ($violations as $violation) {
      $messages[] = strip_tags((string) $violation->getMessage());
    }

    $this->assertEqualsCanonicalizing([
      'sub: the email address can not be longer than 254 characters.',
      'This value is not a valid email address.',
    ], $messages);
  }

  /**
   * The schema is a fixed varchar(254) column; $settings is otherwise ignored.
   */
  public function testSchema(): void {
    $this->assertSame(
      ['sub' => ['type' => 'varchar', 'length' => EmailType::EMAIL_MAX_LENGTH]],
      EmailType::schema(['name' => 'sub']),
    );
  }

  /**
   * The property definition is a single, optional email.
   */
  public function testPropertyDefinitions(): void {
    $properties = EmailType::propertyDefinitions(['name' => 'sub']);

    $this->assertSame(['sub'], array_keys($properties));
    $this->assertSame('email', $properties['sub']->getDataType());
    $this->assertFalse($properties['sub']->isRequired());
  }

  /**
   * The declared default field settings are the base class's unmodified.
   */
  public function testDefaultFieldSettings(): void {
    $this->assertSame([
      'label' => '',
      'required' => FALSE,
      'translatable' => FALSE,
      'description' => '',
      'description_display' => 'after',
      'check_empty' => FALSE,
    ], EmailType::defaultFieldSettings());
  }

  /**
   * CHARACTERIZATION, not contract: getMaxlength() reports the wrong bound.
   *
   * The base class's getMaxlength() reads `self::MAX_LENGTH`, and `self::`
   * binds to the DECLARING class at compile time, not the runtime subclass —
   * so it resolves to the base class's generic 255, not
   * EmailType::EMAIL_MAX_LENGTH's 254, even though no `length` setting is
   * configured on the instance. The two numbers exist for unrelated reasons
   * (255 is the base class's generic string default; 254 is the email length
   * limit this type itself declares) and currently disagree. Fixing this
   * needs more than switching the base reader to `static::MAX_LENGTH`: since
   * EmailType has no `MAX_LENGTH` constant of its own (only the differently
   * named `EMAIL_MAX_LENGTH`), `static::MAX_LENGTH` would still resolve to
   * the base class's 255 here. The two constants would need reconciling to
   * one source of truth — for example EmailType declaring its own
   * `MAX_LENGTH` alongside (or instead of) `EMAIL_MAX_LENGTH` — before
   * switching the base method to `static::` would actually change this
   * result.
   */
  public function testGetMaxlengthDoesNotMatchEmailMaxLength(): void {
    $instance = $this->typeManager->createInstance('email', [
      'settings' => ['name' => 'sub'],
    ]);
    $this->assertInstanceOf(CustomFieldTypeInterface::class, $instance);

    $this->assertSame(255, $instance->getMaxlength());
    $this->assertNotSame(EmailType::EMAIL_MAX_LENGTH, $instance->getMaxlength());
  }

}
