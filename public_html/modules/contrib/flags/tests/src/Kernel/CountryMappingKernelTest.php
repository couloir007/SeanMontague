<?php

namespace Drupal\Tests\flags\Kernel;

use Drupal\flags\Entity\CountryFlagMapping;
use Drupal\flags\Mapping\Country;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests country flag mapping config entity and service integration.
 *
 * @group flags
 */
#[RunTestsInSeparateProcesses]
class CountryMappingKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['flags'];

  /**
   * Tests that the country mapping service can be loaded.
   */
  public function testServiceExists(): void {
    $service = $this->container->get('flags.mapping.country');
    $this->assertInstanceOf(Country::class, $service);
  }

  /**
   * Tests creating a country flag mapping config entity.
   */
  public function testCreateCountryFlagMapping(): void {
    $entity = CountryFlagMapping::create([
      'source' => 'en',
      'flag' => 'gb',
    ]);
    $entity->save();

    $loaded = CountryFlagMapping::load('en');
    $this->assertNotNull($loaded);
    $this->assertSame('en', $loaded->getSource());
    $this->assertSame('gb', $loaded->getFlag());
  }

  /**
   * Tests that the mapping service uses config entities.
   */
  public function testMappingServiceUsesConfigEntities(): void {
    CountryFlagMapping::create([
      'source' => 'en',
      'flag' => 'gb',
    ])->save();

    // Rebuild the container so the service picks up the new config.
    $this->container = $this->container->get('kernel')->rebuildContainer();
    $service = $this->container->get('flags.mapping.country');

    $this->assertSame('gb', $service->map('en'));
  }

  /**
   * Tests that mapping falls back to the code when no entity exists.
   */
  public function testMappingFallbackWithoutEntity(): void {
    $service = $this->container->get('flags.mapping.country');
    $this->assertSame('nl', $service->map('NL'));
  }

  /**
   * Tests deleting a country flag mapping config entity.
   */
  public function testDeleteCountryFlagMapping(): void {
    $entity = CountryFlagMapping::create([
      'source' => 'en',
      'flag' => 'gb',
    ]);
    $entity->save();
    $this->assertNotNull(CountryFlagMapping::load('en'));

    $entity->delete();
    $this->assertNull(CountryFlagMapping::load('en'));
  }

  /**
   * Tests that flags_preprocess_flags() runs and adds the sprite classes.
   *
   * Regression test for a Drupal 12 upgrade issue: the legacy
   * template_preprocess_HOOK() naming convention is no longer auto-discovered
   * by the theme registry, so the 'flag' and 'flag-{code}' classes were never
   * added and the CSS sprite did not render.
   */
  public function testFlagsPreprocessAddsSpriteClasses(): void {
    $build = [
      '#theme' => 'flags',
      '#code' => 'nl',
      '#source' => 'country',
    ];
    $rendered = (string) \Drupal::service('renderer')->renderInIsolation($build);

    $this->assertStringContainsString('class="', $rendered, 'The flag span has a class attribute.');
    $this->assertStringContainsString('flag', $rendered, 'The base "flag" class is added.');
    $this->assertStringContainsString('flag-nl', $rendered, 'The country-specific "flag-nl" class is added.');
  }

  /**
   * Tests that setFlag stores lowercase.
   */
  public function testSetFlagStoresLowercase(): void {
    $entity = CountryFlagMapping::create([
      'source' => 'test',
      'flag' => 'NL',
    ]);
    $entity->setFlag('GB');
    $entity->save();

    $loaded = CountryFlagMapping::load('test');
    $this->assertSame('gb', $loaded->getFlag());
  }

}
