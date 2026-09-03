<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Kernel;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;

/**
 * Tests formatting custom fields with numeric subfield names.
 *
 * @group custom_field
 */
class NumericSubfieldFormatterTest extends FieldKernelTestBase {

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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['field', 'node', 'system']);

    NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ])->save();

    FieldStorageConfig::create([
      'field_name' => 'field_numeric_cols',
      'entity_type' => 'node',
      'type' => 'custom',
      'settings' => [
        'columns' => [
          ['name' => '0', 'type' => 'string', 'length' => 255],
          ['name' => '1', 'type' => 'string', 'length' => 255],
        ],
      ],
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_numeric_cols',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Numeric columns',
      'settings' => [
        'field_settings' => [
          '0' => [],
          '1' => [],
        ],
      ],
    ])->save();

    EntityViewDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'article',
      'mode' => 'default',
      'status' => TRUE,
      'content' => [
        'field_numeric_cols' => [
          'type' => 'custom_formatter',
          'weight' => 0,
          'region' => 'content',
          'settings' => [
            'fields' => [],
          ],
          'label' => 'above',
          'third_party_settings' => [],
        ],
      ],
    ])->save();
  }

  /**
   * Tests custom_formatter renders fields with numeric subfield names.
   */
  public function testCustomFormatterRendersNumericSubfieldNames(): void {
    $node = Node::create([
      'type' => 'article',
      'title' => 'Test node',
      'field_numeric_cols' => [
        [
          '0' => 'Cell A',
          '1' => 'Cell B',
        ],
      ],
    ]);
    $node->save();
    $node = Node::load($node->id());

    $this->assertSame('Cell A', $node->field_numeric_cols[0]->{'0'});
    $this->assertSame('Cell B', $node->field_numeric_cols[0]->{'1'});

    $view_builder = $this->container->get('entity_type.manager')->getViewBuilder('node');
    $build = $view_builder->view($node, 'default');
    $output = (string) $this->container->get('renderer')->renderRoot($build);

    $this->assertStringContainsString('Cell A', $output);
    $this->assertStringContainsString('Cell B', $output);
  }

}
