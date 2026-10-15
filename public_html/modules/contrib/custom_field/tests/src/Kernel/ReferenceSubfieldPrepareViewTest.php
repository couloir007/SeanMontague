<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Kernel;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Field\FormatterInterface;
use Drupal\Core\TypedData\DataReferenceInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests prepareView() on a reference subfield of a not-yet-instantiated item.
 *
 * BaseFormatter::prepareView() attaches loaded entities by assigning
 * ['entity' => ...] onto the item. That shape is only meaningful to the
 * typed-data property, but FieldItemBase::writePropertyValue() deliberately
 * skips creating property objects when none exists yet, so on a cold item list
 * the array was written raw into $this->values and every later read returned
 * it — including EntityReferenceComputed::getTarget(), which passed it to
 * load() and raised "Cannot access offset of type array on array".
 *
 * Callers reaching formatters through EntityViewDisplay::buildMultiple() never
 * saw this, because filterEmptyItems() runs immediately before prepareView()
 * and instantiates the properties as a side effect of isEmpty(). These tests
 * exercise prepareView() directly on a cold list, which is what any caller
 * that renders a field outside that pipeline does.
 *
 * @group custom_field
 */
#[Group('custom_field')]
#[RunTestsInSeparateProcesses]
class ReferenceSubfieldPrepareViewTest extends FieldKernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'custom_field',
    'field',
    'node',
    'system',
    'user',
  ];

  /**
   * The node referenced by the custom field's subfield.
   */
  protected Node $target;

  /**
   * The node holding the custom field.
   */
  protected Node $host;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['field', 'node', 'system']);

    NodeType::create(['type' => 'article', 'name' => 'Article'])->save();

    FieldStorageConfig::create([
      'field_name' => 'field_ref_holder',
      'entity_type' => 'node',
      'type' => 'custom',
      'settings' => [
        'columns' => [
          'ref' => ['name' => 'ref', 'type' => 'entity_reference', 'target_type' => 'node'],
        ],
      ],
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_ref_holder',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Reference holder',
      'settings' => [
        'field_settings' => [
          'ref' => [
            'label' => 'Reference',
            'check_empty' => FALSE,
            'required' => FALSE,
            'description' => '',
            'handler' => 'default:node',
            'handler_settings' => [
              'target_bundles' => ['article' => 'article'],
              'sort' => ['field' => '_none', 'direction' => 'ASC'],
              'auto_create' => FALSE,
              'auto_create_bundle' => '',
            ],
          ],
        ],
      ],
    ])->save();

    EntityViewDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'article',
      'mode' => 'default',
      'status' => TRUE,
      'content' => [
        'field_ref_holder' => [
          'type' => 'custom_formatter',
          'weight' => 0,
          'region' => 'content',
          'settings' => [
            'fields' => [
              // Give the subfield a real formatter, so the rendering
              // assertions below observe the referenced entity.
              'ref' => [
                'format_type' => 'entity_reference_label',
                'formatter_settings' => ['link' => FALSE],
              ],
            ],
          ],
          'label' => 'above',
          'third_party_settings' => [],
        ],
      ],
    ])->save();

    // The reference formatters check view access on the target, which an
    // account with no permissions fails — that would render empty output and
    // hide the very thing these tests assert on.
    $this->setUpCurrentUser([], ['access content']);

    $this->target = Node::create([
      'type' => 'article',
      'title' => 'Referenced target',
      'status' => NodeInterface::PUBLISHED,
    ]);
    $this->target->save();

    $this->host = Node::create([
      'type' => 'article',
      'title' => 'Reference holder host',
      'field_ref_holder' => [['ref' => $this->target->id()]],
    ]);
    $this->host->save();
  }

  /**
   * Returns the custom_formatter instance for the reference-holding field.
   */
  protected function formatter(): FormatterInterface {
    $host = Node::load($this->host->id());
    $renderer = EntityViewDisplay::collectRenderDisplay($host, 'default')
      ->getRenderer('field_ref_holder');
    $this->assertInstanceOf(FormatterInterface::class, $renderer);

    return $renderer;
  }

  /**
   * Tests prepareView() leaves a cold item's reference value scalar.
   */
  public function testPrepareViewKeepsReferenceValueScalarOnColdItemList(): void {
    $host = Node::load($this->host->id());
    // Deliberately NOT calling filterEmptyItems(): this is the cold list that
    // core's display pipeline never hands to a formatter.
    $items = $host->get('field_ref_holder');

    $this->formatter()->prepareView([$host->id() => $items]);

    $this->assertIsNotArray(
      $items[0]->ref,
      'prepareView() must not replace the stored target ID with an array.'
    );
    $this->assertEquals($this->target->id(), $items[0]->ref);
  }

  /**
   * Tests viewElements() renders after prepareView() on a cold item list.
   */
  public function testViewElementsRendersAfterPrepareViewOnColdItemList(): void {
    $host = Node::load($this->host->id());
    $items = $host->get('field_ref_holder');
    $formatter = $this->formatter();

    $formatter->prepareView([$host->id() => $items]);
    $elements = $formatter->viewElements($items, 'en');
    $output = (string) $this->container->get('renderer')->renderRoot($elements);

    $this->assertStringContainsString('Referenced target', $output);
  }

  /**
   * Tests prepareView() is idempotent on the same item list.
   *
   * A single caller can legitimately reach the same field twice — an ordering
   * field that lists the same component field more than once does exactly
   * that — so the second call must read back the scalar it left rather than
   * the array it wrote.
   */
  public function testPrepareViewIsIdempotentOnTheSameItemList(): void {
    $host = Node::load($this->host->id());
    $items = $host->get('field_ref_holder');
    $formatter = $this->formatter();

    $formatter->prepareView([$host->id() => $items]);
    $formatter->prepareView([$host->id() => $items]);
    $formatter->prepareView([$host->id() => $items]);

    $this->assertIsNotArray($items[0]->ref);
    $this->assertEquals($this->target->id(), $items[0]->ref);

    $elements = $formatter->viewElements($items, 'en');
    $output = (string) $this->container->get('renderer')->renderRoot($elements);
    $this->assertStringContainsString('Referenced target', $output);
  }

  /**
   * Tests the computed reference companion resolves after prepareView().
   *
   * The computed property is the direct consumer of the value prepareView()
   * writes: EntityReferenceComputed::getTarget() reads the raw target ID off
   * the parent item and loads it. It is therefore the thing that breaks first
   * if that value's shape changes, so it is asserted here rather than
   * inferred from a green render.
   *
   * The property is read only AFTER prepareView() has run, because
   * EntityReferenceComputed caches its resolved entity — reading it earlier
   * would prove nothing about the state prepareView() left behind.
   *
   * @testWith [0]
   *           [1]
   *           [2]
   */
  public function testComputedCompanionResolvesAfterPrepareView(int $calls): void {
    $host = Node::load($this->host->id());
    $items = $host->get('field_ref_holder');
    $formatter = $this->formatter();

    for ($i = 0; $i < $calls; $i++) {
      $formatter->prepareView([$host->id() => $items]);
    }

    $definitions = $items[0]->getFieldDefinition()
      ->getItemDefinition()
      ->getPropertyDefinitions();
    $computed = [];
    foreach ($definitions as $name => $definition) {
      if ($definition->isComputed()) {
        $computed[$name] = $definition;
      }
    }
    $this->assertNotEmpty(
      $computed,
      'The reference subfield should declare a computed companion property.'
    );

    $resolved = 0;
    foreach (array_keys($computed) as $name) {
      $property = $items[0]->get($name);
      if (!$property instanceof DataReferenceInterface) {
        continue;
      }
      $target = $property->getTarget();
      $this->assertNotNull($target, sprintf('%s resolved to NULL.', $name));
      $this->assertSame((int) $this->target->id(), (int) $target->getValue()->id());
      $resolved++;
    }
    $this->assertGreaterThan(0, $resolved, 'No reference companion was resolved.');
  }

}
