<?php

declare(strict_types=1);

namespace Drupal\Tests\entity_embed\Functional;

use Drupal\entity_embed\Controller\EditEmbeddedEntity;
use Drupal\file\Entity\File;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the redirect from an embedded entity to its edit form.
 */
#[CoversClass(EditEmbeddedEntity::class)]
#[Group('entity_embed')]
#[RunTestsInSeparateProcesses]
class EditEmbeddedEntityTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_embed', 'node', 'file'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the redirect and the 404s for entities that can't be edited.
   */
  public function testEdit(): void {
    $this->drupalCreateContentType(['type' => 'page']);
    $node = $this->drupalCreateNode(['type' => 'page']);
    $this->drupalLogin($this->drupalCreateUser(['bypass node access']));

    $this->drupalGet("entity-embed/edit-embedded/node/{$node->uuid()}");
    $this->assertSession()->addressEquals("node/{$node->id()}/edit");

    // A deleted entity.
    $uuid = $node->uuid();
    $node->delete();
    $this->drupalGet("entity-embed/edit-embedded/node/$uuid");
    $this->assertSession()->statusCodeEquals(404);

    // An entity type that doesn't exist.
    $this->drupalGet("entity-embed/edit-embedded/does_not_exist/$uuid");
    $this->assertSession()->statusCodeEquals(404);

    // An entity type without an edit form.
    file_put_contents('public://example.txt', 'example');
    $file = File::create(['uri' => 'public://example.txt']);
    $file->save();
    $this->drupalGet("entity-embed/edit-embedded/file/{$file->uuid()}");
    $this->assertSession()->statusCodeEquals(404);
  }

}
