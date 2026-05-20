<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg_field_parts\Kernel;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgEntityKernelTestBase;

/**
 * Tests the functionality of the Schema.org field parts with field group.
 *
 * @group schemadotorg
 */
class SchemaDotOrgFieldPartsFieldGroupKernelTest extends SchemaDotOrgEntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field_group',
    'schemadotorg_field_group',
    'schemadotorg_field_parts',
  ];

  /**
   * The entity display repository.
   */
  protected EntityDisplayRepositoryInterface $entityDisplayRepository;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['schemadotorg_field_parts', 'schemadotorg_field_group']);

    $this->entityDisplayRepository = $this->container->get('entity_display.repository');
  }

  /**
   * Test Schema.org field parts.
   */
  public function testFieldParts(): void {
    $this->createSchemaEntity('node', 'WebContent');

    // Check that the name's field prefix is placed in the expected field group.
    /** @var \Drupal\Core\Entity\Entity\EntityFormDisplay $form_display */
    $form_display = $this->entityDisplayRepository->getFormDisplay('node', 'web_content');
    $field_group_settings = $form_display->getThirdPartySettings('field_group');
    $expected_children = [
      'schema_image',
      'title',
      'body',
      'title_prefix',
      'title_suffix',
    ];
    $this->assertEquals(
      $expected_children,
      NestedArray::getValue($field_group_settings, ['group_general', 'children'])
    );

    // Check hiding the prefix and suffix component from the view display.
    /** @var \Drupal\Core\Entity\Entity\EntityViewDisplay $view_display */
    $view_display = $this->entityDisplayRepository->getViewDisplay('node', 'web_content');
    $expected_hidden = [
      'langcode' => TRUE,
    ];
    $this->assertEquals($expected_hidden, $view_display->get('hidden'));
  }

}
