<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg_pathauto\Kernel;

use Drupal\node\Entity\Node;
use Drupal\Tests\pathauto\Functional\PathautoTestHelperTrait;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgEntityKernelTestBase;

/**
 * Tests the functionality of the Schema.org pathauto.
 *
 * @covers \schemadotorg_pathauto_schemadotorg_mapping_insert
 * @group schemadotorg
 */
class SchemaDotOrgPathautoKernelTest extends SchemaDotOrgEntityKernelTestBase {
  use PathautoTestHelperTrait;

  /**
   * Modules.
   *
   * @var string[]
   */
  protected static $modules = [
    'path',
    'path_alias',
    'pathauto',
    'token',
    'schemadotorg_pathauto',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setup();

    $this->installEntitySchema('path_alias');
    $this->installConfig([
      'pathauto',
      'system',
      'node',
      'schemadotorg_pathauto',
    ]);
  }

  /**
   * Test Schema.org pathauto installation.
   */
  public function testInstall(): void {
    \Drupal::moduleHandler()->loadInclude('schemadotorg_pathauto', 'install');

    // Check via preinstallation the 'schemadotorg:base-path' token is not
    // a safe_token.
    $this->assertFalse(in_array('schemadotorg:base-path', \Drupal::config('pathauto.settings')->get('safe_tokens')));

    // Install the schemadotorg_pathauto.module.
    schemadotorg_pathauto_install(FALSE);
    \Drupal::configFactory()->reset('pathauto.settings');

    // Check via installation the 'schemadotorg:base-path' token is
    // a safe_token.
    $this->assertTrue(in_array('schemadotorg:base-path', \Drupal::config('pathauto.settings')->get('safe_tokens')));

    // Uninstall the schemadotorg_pathauto.module.
    schemadotorg_pathauto_uninstall(FALSE);
    \Drupal::configFactory()->reset('pathauto.settings');

    // Check via uninstallation the 'schemadotorg:base-path' token is not
    // a safe_token.
    $this->assertFalse(in_array('schemadotorg:base-path', \Drupal::config('pathauto.settings')->get('safe_tokens')));
  }

  /**
   * Test Schema.org pathauto.
   */
  public function testPathauto(): void {
    $pathauto_pattern_storage = \Drupal::entityTypeManager()->getStorage('pathauto_pattern');

    // Configure a bundle-specific pattern for a landing_page bundle using
    // the WebPage schema type, alongside the generic WebPage pattern.
    $this->config('schemadotorg_pathauto.settings')
      ->set('patterns', [
        'node--Thing' => '[node:schemadotorg:base-path]/[node:schemadotorg:alternate-name]',
        'node--WebPage--landing_page' => '[node:title]',
      ])
      ->save();

    // Create node:Thing.
    $this->createSchemaEntity('node', 'Thing');

    // Check that node thing pathauto pattern is created.
    $pathauto_pattern = $pathauto_pattern_storage->load('schema_node_thing');
    $this->assertEquals('schema_node_thing', $pathauto_pattern->id());
    $this->assertEquals('Schema.org: Content - Thing', $pathauto_pattern->label());
    $this->assertEquals('/[node:schemadotorg:base-path]/[node:schemadotorg:alternate-name]', $pathauto_pattern->get('pattern'));

    // Check that node thing pathauto pattern selection condition bundle
    // includes thing.
    $selection_conditions_configuration = $pathauto_pattern->getSelectionConditions()->getConfiguration();
    $selection_condition_id = array_key_first($selection_conditions_configuration);
    $selection_condition = $pathauto_pattern->getSelectionConditions()->get($selection_condition_id);
    $configuration = $selection_condition->getConfiguration();
    $this->assertEquals(['thing' => 'thing'], $configuration['bundles']);

    // Create node:Event.
    $this->createSchemaEntity('node', 'Event');

    // Check that node thing pathauto pattern selection condition bundle
    // includes thing and event.
    $pathauto_pattern_storage->resetCache();
    $pathauto_pattern = $pathauto_pattern_storage->load('schema_node_thing');
    $selection_conditions_configuration = $pathauto_pattern->getSelectionConditions()->getConfiguration();
    $selection_condition_id = array_key_first($selection_conditions_configuration);
    $selection_condition = $pathauto_pattern->getSelectionConditions()->get($selection_condition_id);
    $configuration = $selection_condition->getConfiguration();
    $this->assertEquals(['event' => 'event', 'thing' => 'thing'], $configuration['bundles']);

    // Check event node path alias.
    $this->createSchemaEntity('node', 'Event');
    $node = Node::create(['type' => 'event', 'title' => 'Some event']);
    $node->save();
    $this->assertEntityAlias($node, '/events/some-event');

    // Create node:WebPage:landing_page.
    $this->createSchemaEntity('node', 'WebPage', ['entity' => ['id' => 'landing_page', 'label' => 'Landing Page']]);

    // Check that a separate bundle-specific pathauto pattern is created.
    /** @var \Drupal\pathauto\PathautoPatternInterface $bundle_pattern */
    $bundle_pattern = $pathauto_pattern_storage->load('schema_node_web_page_landing_page');
    $this->assertEquals('Schema.org: Content - Web Page (Landing Page)', $bundle_pattern->label());
    $this->assertEquals('/[node:title]', $bundle_pattern->get('pattern'));
    $configuration = $bundle_pattern->getSelectionConditions()->getConfiguration();
    $configuration = reset($configuration);
    $this->assertEquals(['landing_page' => 'landing_page'], $configuration['bundles']);
  }

}
