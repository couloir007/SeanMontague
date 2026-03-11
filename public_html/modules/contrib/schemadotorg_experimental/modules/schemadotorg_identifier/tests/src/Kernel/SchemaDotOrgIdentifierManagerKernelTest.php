<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg_identifier\Kernel;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\schemadotorg\Entity\SchemaDotOrgMapping;
use Drupal\schemadotorg_identifier\SchemaDotOrgIdentifierManagerInterface;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgEntityKernelTestBase;

/**
 * Tests the functionality of the Schema.org identifier field.
 *
 * @covers \Drupal\schemadotorg_identifier\SchemaDotOrgIdentifierManager
 * @group schemadotorg
 */
class SchemaDotOrgIdentifierManagerKernelTest extends SchemaDotOrgEntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field_group',
    'schemadotorg_field_group',
    'schemadotorg_identifier',
  ];

  /**
   * The entity display repository.
   */
  protected EntityDisplayRepositoryInterface $entityDisplayRepository;

  /**
   * The Schema.org identifier manager.
   */
  protected SchemaDotOrgIdentifierManagerInterface $identifierManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig([
      'schemadotorg_field_group',
      'schemadotorg_identifier',
    ]);

    $this->entityDisplayRepository = $this->container->get('entity_display.repository');

    $this->identifierManager = $this->container->get('schemadotorg_identifier.manager');
  }

  /**
   * Test Schema.org identifier.
   */
  public function testIdentifier(): void {
    $this->createSchemaEntity('node', 'MedicalTrial');

    /* ********************************************************************** */

    // Check that the identifier fields are created when a mapping is inserted.
    $this->assertNotNull(FieldConfig::loadByName('node', 'medical_trial', 'schema_identifier_irb'));
    $this->assertNotNull(FieldConfig::loadByName('node', 'medical_trial', 'schema_identifier_nci'));

    // Check that the identifier field group is created via the form display.
    $form_display = $this->entityDisplayRepository->getFormDisplay('node', 'medical_trial', 'default');
    $component = $form_display->getComponent('schema_identifier_nci');
    $this->assertEquals('string_textfield', $component['type']);
    $field_group = $form_display->getThirdPartySettings('field_group');
    $this->assertEquals(['schema_identifier_irb', 'schema_identifier_nci'], $field_group['group_identifiers']['children']);
    $this->assertEquals('Identifiers', $field_group['group_identifiers']['label']);
    $this->assertEquals('details', $field_group['group_identifiers']['format_type']);

    // Check that the identifier field group is created via the view display.
    $view_display = $this->entityDisplayRepository->getViewDisplay('node', 'medical_trial', 'default');
    $component = $view_display->getComponent('schema_identifier_nci');
    $this->assertEquals('string', $component['type']);
    $field_group = $view_display->getThirdPartySettings('field_group');
    $this->assertEquals(['schema_identifier_irb', 'schema_identifier_nci'], $field_group['group_identifiers']['children']);
    $this->assertEquals('Identifiers', $field_group['group_identifiers']['label']);
    $this->assertEquals('fieldset', $field_group['group_identifiers']['format_type']);

    // Check identifier field definitions for a Schema.org mapping.
    $mapping = SchemaDotOrgMapping::load('node.medical_trial');
    $expected_field_definitions = [
      'irb_number' => [
        'property_id' => 'IRB number',
        'field_name' => 'schema_identifier_irb',
        'label' => 'IRB number',
        'description' => 'An IRB number is a 4–5 digit number assigned to a study by an Institutional Review Board (IRB).',
        'max_length' => 8,
        'base_field' => FALSE,
      ],
      'nct_number' => [
        'property_id' => 'NCT Number',
        'field_name' => 'schema_identifier_nci',
        'label' => 'ClinicalTrials.gov Identifier',
        'description' => 'The National Clinical Trial number is an identification that ClinicalTrials.gov assigns a study when it is registered.',
        'max_length' => 12,
        'base_field' => FALSE,
      ],
    ];
    $this->assertEquals(
      $expected_field_definitions,
      $this->identifierManager->getMappingFieldDefinitions($mapping)
    );
  }

}
