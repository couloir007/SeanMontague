<?php

namespace Drupal\Tests\flags\Kernel;

use Drupal\flags\Entity\LanguageFlagMapping;
use Drupal\flags\Mapping\Language;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests language flag mapping config entity and service integration.
 *
 * @group flags
 */
#[RunTestsInSeparateProcesses]
class LanguageMappingKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['flags'];

  /**
   * Tests that the language mapping service can be loaded.
   */
  public function testServiceExists(): void {
    $service = $this->container->get('flags.mapping.language');
    $this->assertInstanceOf(Language::class, $service);
  }

  /**
   * Tests creating a language flag mapping config entity.
   */
  public function testCreateLanguageFlagMapping(): void {
    $entity = LanguageFlagMapping::create([
      'source' => 'en',
      'flag' => 'gb',
    ]);
    $entity->save();

    $loaded = LanguageFlagMapping::load('en');
    $this->assertNotNull($loaded);
    $this->assertSame('en', $loaded->getSource());
    $this->assertSame('gb', $loaded->getFlag());
  }

  /**
   * Tests that the mapping service uses config entities.
   */
  public function testMappingServiceUsesConfigEntities(): void {
    LanguageFlagMapping::create([
      'source' => 'en',
      'flag' => 'gb',
    ])->save();

    // Rebuild the container so the service picks up the new config.
    $this->container = $this->container->get('kernel')->rebuildContainer();
    $service = $this->container->get('flags.mapping.language');

    $this->assertSame('gb', $service->map('en'));
  }

  /**
   * Tests that mapping falls back to the code when no entity exists.
   */
  public function testMappingFallbackWithoutEntity(): void {
    $service = $this->container->get('flags.mapping.language');
    $this->assertSame('nl', $service->map('NL'));
  }

  /**
   * Tests deleting a language flag mapping config entity.
   */
  public function testDeleteLanguageFlagMapping(): void {
    $entity = LanguageFlagMapping::create([
      'source' => 'en',
      'flag' => 'gb',
    ]);
    $entity->save();
    $this->assertNotNull(LanguageFlagMapping::load('en'));

    $entity->delete();
    $this->assertNull(LanguageFlagMapping::load('en'));
  }

}
