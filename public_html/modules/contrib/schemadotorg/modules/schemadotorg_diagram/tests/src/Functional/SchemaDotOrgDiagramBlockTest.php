<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg_diagram\Functional;

use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org diagram block.
 *
 * @group schemadotorg
 */
class SchemaDotOrgDiagramBlockTest extends SchemaDotOrgBrowserTestBase {

  // phpcs:disable
  /**
   * Disabled config schema checking until the cer.module has fixed its schema.
   */
  protected $strictConfigSchema = FALSE;
  // phpcs:enable

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['schemadotorg_cer', 'schemadotorg_diagram'];

  /**
   * Test Schema.org diagram organization.
   */
  public function testOrganizationDiagram(): void {
    $assert = $this->assertSession();

    $this->drupalPlaceBlock('schemadotorg_diagram');

    $account = $this->createUser(['access content', 'view schemadotorg diagram']);

    $this->appendSchemaTypeDefaultProperties('Organization', ['subOrganization', 'parentOrganization']);
    $this->createSchemaEntity('node', 'Organization');

    $parent_node = $this->drupalCreateNode([
      'type' => 'organization',
      'title' => '{Parent}',
    ]);
    $parent_node->save();

    $child_node = $this->drupalCreateNode([
      'type' => 'organization',
      'title' => '{Child}',
    ]);
    $child_node->save();

    $grandchild_node = $this->drupalCreateNode([
      'type' => 'organization',
      'title' => '{Grandchild}',
    ]);
    $grandchild_node->save();

    $child_node->set('schema_sub_organization', [['target_id' => $grandchild_node->id()]]);
    $child_node->save();

    $current_node = $this->drupalCreateNode([
      'type' => 'organization',
      'title' => '{Current}',
      'schema_parent_organization' => [['target_id' => $parent_node->id()]],
      'schema_sub_organization' => [['target_id' => $child_node->id()]],
    ]);
    $current_node->save();

    /* ********************************************************************** */

    // Check that the diagram details is NOT displayed.
    $this->drupalGet($current_node->toUrl());
    $assert->responseNotContains('Schema.org diagram');

    // Check that the diagram details is NOT displayed.
    $this->drupalLogin($account);
    $this->drupalGet($current_node->toUrl());
    $assert->responseContains('Schema.org diagram');

    // Check the parent - current - child flowchart markdown.
    $expected_text = 'flowchart TB
1-' . $current_node->id() . '(("`**{Current}**
(Organization)`"))
style 1-' . $current_node->id() . ' fill:#ffaacc,stroke:#333,stroke-width:4px;
click 1-' . $current_node->id() . ' "' . $current_node->toUrl()->setAbsolute()->toString() . '"
0-' . $parent_node->id() . '["`**{Parent}**
(Organization)`"]
click 0-' . $parent_node->id() . ' "' . $parent_node->toUrl()->setAbsolute()->toString() . '"
0-' . $parent_node->id() . ' --- 1-' . $current_node->id() . '
1-' . $current_node->id() . ' --- 2-' . $child_node->id() . '
2-' . $child_node->id() . '["`**{Child}**
(Organization)`"]
click 2-' . $child_node->id() . ' "' . $child_node->toUrl()->setAbsolute()->toString() . '"
2-' . $child_node->id() . ' --- 3-' . $grandchild_node->id() . '
3-' . $grandchild_node->id() . '["`**{Grandchild}**
(Organization)`"]
click 3-' . $grandchild_node->id() . ' "' . $grandchild_node->toUrl()->setAbsolute()->toString() . '"';
    $assert->responseContains($expected_text);

    // Check that the max depth setting limits child recursion.
    \Drupal::configFactory()
      ->getEditable('schemadotorg_diagram.settings')
      ->set('max_depth', 2)
      ->save();

    $this->drupalGet($current_node->toUrl());
    $assert->responseContains('{Child}');
    $assert->responseNotContains('{Grandchild}');

    // Check the parent - current - child relationships.
    $assert->responseContains('<p class="schemadotorg-diagram-relationships"><a href="https://schema.org/parentOrganization">parentOrganization</a> → <a href="https://schema.org/Organization">Organization</a> → <a href="https://schema.org/subOrganization">subOrganization</a></p>');

  }

}
