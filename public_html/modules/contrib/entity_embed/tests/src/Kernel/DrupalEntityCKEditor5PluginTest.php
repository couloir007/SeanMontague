<?php

declare(strict_types=1);

namespace Drupal\Tests\entity_embed\Kernel;

use Drupal\editor\Entity\Editor;
use Drupal\embed\Entity\EmbedButton;
use Drupal\entity_embed\Plugin\CKEditor5Plugin\DrupalEntity;
use Drupal\filter\Entity\FilterFormat;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the Entity Embed CKEditor 5 plugin.
 */
#[Group('entity_embed')]
#[RunTestsInSeparateProcesses]
#[CoversClass(DrupalEntity::class)]
class DrupalEntityCKEditor5PluginTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ckeditor5',
    'editor',
    'embed',
    'entity_embed',
    'filter',
    'system',
    'user',
  ];

  /**
   * Tests that embed button labels are passed to the editor unescaped.
   */
  public function testButtonLabels(): void {
    $label = 'Tom & Jerry\'s "<em>cartoons</em>"';
    EmbedButton::create([
      'id' => 'cartoons',
      'label' => $label,
      'type_id' => 'entity',
      'type_settings' => ['entity_type' => 'user'],
    ])->save();
    FilterFormat::create([
      'format' => 'test_format',
      'name' => 'Test format',
      'filters' => ['entity_embed' => ['status' => TRUE]],
    ])->save();
    $editor = Editor::create([
      'editor' => 'ckeditor5',
      'format' => 'test_format',
      'image_upload' => ['status' => FALSE],
      'settings' => ['toolbar' => ['items' => ['cartoons']]],
    ]);

    $config = $this->container->get('plugin.manager.ckeditor5.plugin')
      ->getPlugin('entity_embed_drupalentity:entity_embed_cartoons', $editor)
      ->getDynamicPluginConfig([], $editor);

    // CKEditor 5 displays the label as text, so escaping it would show the
    // HTML entities.
    $this->assertSame($label, $config['entityEmbed']['buttons']['cartoons']['label']);
    // The name stays escaped for backwards compatibility.
    $this->assertSame('Tom &amp; Jerry&#039;s &quot;&lt;em&gt;cartoons&lt;/em&gt;&quot;', $config['entityEmbed']['buttons']['cartoons']['name']);
  }

}
