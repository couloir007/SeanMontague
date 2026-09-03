<?php

declare(strict_types=1);

namespace Drupal\Tests\trash\Kernel;

use Drupal\Tests\workspaces\Kernel\WorkspaceTestTrait;
use Drupal\trash\EntityQuery\Workspaces\QueryFactory;
use Drupal\trash\Trash;
use Drupal\workspaces\Entity\Workspace;

/**
 * Tests Trash integration with Workspaces.
 *
 * @group trash
 */
class TrashWorkspacesTest extends TrashKernelTestBase {

  use WorkspaceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'workspaces',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = \Drupal::entityTypeManager();
    $this->workspaceManager = \Drupal::service('workspaces.manager');

    $this->installSchema('workspaces', ['workspace_association']);
    if (isset(workspaces_schema()['workspace_association_revision'])) {
      $this->installSchema('workspaces', ['workspace_association_revision']);
    }
    $this->installEntitySchema('workspace');

    $this->workspaces['stage'] = Workspace::create(['id' => 'stage', 'label' => 'Stage']);
    $this->workspaces['stage']->save();

    $this->setCurrentUser($this->createUser([
      'view any workspace',
    ]));
  }

  /**
   * Test decorating the ws service.
   */
  public function testContainer(): void {
    static::assertInstanceOf(QueryFactory::class, $this->container->get('entity.query.sql'));
  }

  /**
   * Test trashing entities in a workspace.
   */
  public function testDeletion(): void {
    $live_node = $this->createNode(['type' => 'article']);
    $live_node->save();

    $edited_node = $this->createNode(['type' => 'article']);
    $edited_node->save();

    // Activate a workspace and delete the node.
    $this->switchToWorkspace('stage');

    $ws_node = $this->createNode(['type' => 'article']);
    $ws_node->save();

    $live_node->delete();
    $ws_node->delete();

    $edited_node->setTitle('Edited in stage');
    $edited_node->save();

    // delete() acts on freshly loaded entities, so the passed objects are
    // left untouched; reload to observe the trashed state.
    $live_node = $this->loadTrashedEntity('node', $live_node->id());
    $ws_node = $this->loadTrashedEntity('node', $ws_node->id());

    $this->assertTrue(Trash::entityIsDeleted($live_node));
    $this->assertTrue(Trash::entityIsDeleted($ws_node));

    // Check loading the deleted nodes in a workspace.
    $storage = $this->entityTypeManager->getStorage('node');

    $this->assertEmpty($storage->load($live_node->id()));
    $this->assertEmpty($storage->loadRevision($live_node->getRevisionId()));

    $this->assertEmpty($storage->load($ws_node->id()));
    $this->assertEmpty($storage->loadRevision($ws_node->getRevisionId()));

    // Switch back to Live and check that the nodes are not marked as deleted.
    $this->switchToLive();

    $live_node = $storage->load($live_node->id());
    $this->assertNotEmpty($live_node);
    $this->assertTrue($live_node->isPublished());
    $this->assertNotEmpty($storage->loadRevision($live_node->getRevisionId()));
    $this->assertFalse(Trash::entityIsDeleted($live_node));

    $ws_node = $storage->load($ws_node->id());
    $this->assertNotEmpty($ws_node);
    $this->assertFalse($ws_node->isPublished());
    $this->assertNotEmpty($storage->loadRevision($ws_node->getRevisionId()));
    $this->assertFalse(Trash::entityIsDeleted($ws_node));

    // Loading in Live puts the default revision back in the persistent entity
    // cache, which is keyed by entity ID only and not varied by workspace.
    $this->assertNotEmpty(\Drupal::cache('entity')->get('values:node:' . $live_node->id()));

    // Switching workspaces clears entity.memory_cache for every supported
    // entity type, so these loads go through the persistent cache. The node
    // trashed in this workspace must not be served from it, while a tracked
    // revision that is not trashed must still resolve.
    $this->switchToWorkspace('stage');
    $this->assertEmpty($storage->load($live_node->id()));
    $this->assertEmpty($storage->loadMultiple([$live_node->id()]));
    $this->assertSame('Edited in stage', $storage->load($edited_node->id())->getTitle());

    $this->switchToLive();

    // Publish the workspace and check that both nodes are now deleted in Live.
    $this->workspaces['stage']->publish();

    $this->assertEmpty($storage->load($live_node->id()));
    $this->assertEmpty($storage->load($ws_node->id()));
  }

  /**
   * Test 'purge' entity access in a workspace.
   */
  public function testPurgeAccess(): void {
    $this->setCurrentUser($this->createUser([
      'access content',
      'view any workspace',
      'purge node entities',
    ]));

    $live_node = $this->createNode(['type' => 'article']);
    $live_node->save();

    // Activate a workspace and delete the node.
    $this->switchToWorkspace('stage');

    $ws_node = $this->createNode(['type' => 'article']);
    $ws_node->save();

    $live_node->delete();
    $ws_node->delete();

    // delete() acts on freshly loaded entities, so the passed objects are
    // left untouched; reload to observe the trashed state.
    $live_node = $this->loadTrashedEntity('node', $live_node->id());
    $ws_node = $this->loadTrashedEntity('node', $ws_node->id());

    $this->assertFalse($live_node->access('purge'));
    $this->assertTrue($ws_node->access('purge'));
  }

}
