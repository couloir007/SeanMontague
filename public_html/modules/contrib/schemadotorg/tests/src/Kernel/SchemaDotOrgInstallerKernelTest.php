<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg\Kernel;

use Drupal\schemadotorg\SchemaDotOrgInstallerInterface;

/**
 * Tests the Schema.org installer service.
 *
 * @coversDefaultClass \Drupal\schemadotorg\SchemaDotOrgInstaller
 * @group schemadotorg
 */
class SchemaDotOrgInstallerKernelTest extends SchemaDotOrgKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['media', 'schemadotorg_schema_data_test'];

  /**
   * The Schema.org installer service.
   */
  protected SchemaDotOrgInstallerInterface $installer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchemaDotOrg();

    $this->installer = $this->container->get('schemadotorg.installer');
  }

  /**
   * Tests SchemaDotOrgInstallerInterface::requirements().
   *
   * @covers ::requirements
   */
  public function testRequirements(): void {
    // Check Schema.org names requirements are not returning an error.
    // @see \Drupal\schemadotorg\SchemaDotOrgInstaller::checkNamesRequirements
    $requirements = $this->installer->requirements('runtime');
    $this->assertArrayNotHasKey('schemadotorg_names', $requirements);

    // Check Schema.org names requirements are returning an error.
    \Drupal::configFactory()
      ->getEditable('schemadotorg.names')
      ->set('custom_names', [])
      ->save();
    $requirements = $this->installer->requirements('runtime');
    $this->assertArrayHasKey('schemadotorg_names', $requirements);

    // Check installation recommended modules requirements.
    // @see \Drupal\schemadotorg\SchemaDotOrgInstaller::checkRecommendedRequirements
    $requirements = $this->installer->requirements('runtime');
    $this->assertNotEmpty($requirements);
    $this->assertArrayHasKey('schemadotorg_recommended_modules', $requirements);
    $this->assertEquals('Schema.org Blueprints: Recommended modules missing', $requirements['schemadotorg_recommended_modules']['title']);

    // Check that installation recommended modules requirements can be disabled.
    $this->config('schemadotorg.settings')
      ->set('requirements.recommended_modules', FALSE)
      ->save();
    $requirements = $this->installer->requirements('runtime');
    $this->assertArrayNotHasKey('schemadotorg_recommended_modules', $requirements);

    // Check installation integration requirements exists.
    $requirements = $this->installer->requirements('runtime');
    $this->assertNotEmpty($requirements);
    $this->assertArrayHasKey('schemadotorg_integration_modules', $requirements);
    $this->assertEquals('Schema.org Blueprints: Integration modules missing', $requirements['schemadotorg_integration_modules']['title']);

    // Check installation recommended requirements does not exist.
    // @see \Drupal\schemadotorg\SchemaDotOrgInstaller::checkIntegrationRequirements
    $this->uninstallModule('media');
    $requirements = $this->installer->requirements('runtime');
    $this->assertArrayNotHasKey('schemadotorg_integration_modules', $requirements);
  }

  /**
   * Tests Schema.org data alteration during import.
   *
   * @covers ::importTable
   */
  public function testSchemaDataAlter(): void {
    // Apply Schema.org type and property alterations.
    \Drupal::state()->set('schemadotorg_schema_data_test.mode', 'valid');
    $this->installer->importTables();

    $schema_type_manager = $this->container->get('schemadotorg.schema_type_manager');

    // Check that Schema.org types can be changed, removed, and added.
    $this->assertSame('An altered Thing.', $schema_type_manager->getType('Thing')['comment']);
    $this->assertFalse($schema_type_manager->getType('LearningResource'));
    $this->assertSame('https://schema.org/CustomLearningResource', $schema_type_manager->getType('CustomLearningResource')['id']);

    // Check that Schema.org properties can be changed, removed, and added.
    $this->assertSame('An altered name.', $schema_type_manager->getProperty('name')['comment']);
    $this->assertFalse($schema_type_manager->getProperty('assesses'));
    $this->assertSame('https://schema.org/customProperty', $schema_type_manager->getProperty('customProperty')['id']);

    // Check that missing references do not prevent schema data from being read.
    $this->assertSame(
      ['customProperty'],
      array_keys($schema_type_manager->getTypeProperties('CustomLearningResource')),
    );
    $this->assertNotEmpty($schema_type_manager->getTypeBreadcrumbs('CustomLearningResource'));
  }

}
