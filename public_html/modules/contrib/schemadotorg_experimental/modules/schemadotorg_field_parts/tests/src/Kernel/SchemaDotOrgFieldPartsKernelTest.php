<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg_field_parts\Kernel;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgEntityKernelTestBase;

/**
 * Tests the functionality of the Schema.org field parts.
 *
 * @group schemadotorg
 */
class SchemaDotOrgFieldPartsKernelTest extends SchemaDotOrgEntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
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
    $this->installConfig(['schemadotorg_field_parts']);

    $this->entityDisplayRepository = $this->container->get('entity_display.repository');
  }

  /**
   * Test Schema.org field parts.
   */
  public function testFieldParts(): void {
    $this->createSchemaEntity('node', 'WebContent');

    // Check that the name's field prefix was created.
    /** @var \Drupal\Core\Field\FieldConfigInterface $title_prefix */
    $title_prefix = FieldConfig::loadByName('node', 'web_content', 'title_prefix');
    $this->assertEquals('Title prefix', $title_prefix->label());
    $this->assertEquals('The text which appears before the title.', $title_prefix->getDescription());

    // Check that the name's field suffix was created.
    $title_suffix = FieldConfig::loadByName('node', 'web_content', 'title_suffix');
    $this->assertEquals('Title suffix', $title_suffix->label());
    $this->assertEquals('The text which appears after the title.', $title_suffix->getDescription());

    // Check that the name's field prefix is placed in the expected field group.
    /** @var \Drupal\Core\Entity\Entity\EntityFormDisplay $form_display */
    $form_display = $this->entityDisplayRepository->getFormDisplay('node', 'web_content');
    $this->assertEquals(2, $form_display->getComponent('title_prefix')['weight']);
    $this->assertEquals(2, $form_display->getComponent('title_suffix')['weight']);

    // Check that the name's field prefix is placed in the expected field group.
    /** @var \Drupal\Core\Entity\Entity\EntityViewDisplay $view_display */
    $view_display = $this->entityDisplayRepository->getViewDisplay('node', 'web_content');
    $this->assertEquals(2, $view_display->getComponent('title_prefix')['weight']);
    $this->assertEquals(2, $view_display->getComponent('title_suffix')['weight']);
  }

}
