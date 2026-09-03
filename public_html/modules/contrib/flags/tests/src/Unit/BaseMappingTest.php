<?php

namespace Drupal\Tests\flags\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\flags\Mapping\Country;
use Drupal\flags\Mapping\Language;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for the BaseMapping service and its implementations.
 *
 * @coversDefaultClass \Drupal\flags\Mapping\BaseMapping
 * @group flags
 */
class BaseMappingTest extends UnitTestCase {

  /**
   * Creates a Country mapping service with the given config entities.
   *
   * @param array $mappings
   *   Array of source => flag mappings.
   *
   * @return \Drupal\flags\Mapping\Country
   *   The country mapping service.
   */
  protected function createCountryMapping(array $mappings = []): Country {
    $configs = [];
    $config_ids = [];
    foreach ($mappings as $source => $flag) {
      $key = 'flags.country_flag_mapping.' . $source;
      $config_ids[] = $key;
      $config = $this->createMock(ImmutableConfig::class);
      $config->method('get')
        ->with('flag')
        ->willReturn($flag);
      $configs[$key] = $config;
    }

    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->method('listAll')
      ->with('flags.country_flag_mapping')
      ->willReturn($config_ids);
    $configFactory->method('loadMultiple')
      ->with($config_ids)
      ->willReturn($configs);

    return new Country($configFactory);
  }

  /**
   * Creates a Language mapping service with the given config entities.
   *
   * @param array $mappings
   *   Array of source => flag mappings.
   *
   * @return \Drupal\flags\Mapping\Language
   *   The language mapping service.
   */
  protected function createLanguageMapping(array $mappings = []): Language {
    $configs = [];
    $config_ids = [];
    foreach ($mappings as $source => $flag) {
      $key = 'flags.language_flag_mapping.' . $source;
      $config_ids[] = $key;
      $config = $this->createMock(ImmutableConfig::class);
      $config->method('get')
        ->with('flag')
        ->willReturn($flag);
      $configs[$key] = $config;
    }

    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->method('listAll')
      ->with('flags.language_flag_mapping')
      ->willReturn($config_ids);
    $configFactory->method('loadMultiple')
      ->with($config_ids)
      ->willReturn($configs);

    return new Language($configFactory);
  }

  /**
   * Tests that map() returns lowercase code as default passthrough.
   *
   * @covers ::map
   */
  public function testMapReturnsLowercaseCode(): void {
    $mapping = $this->createCountryMapping();
    $this->assertSame('nl', $mapping->map('NL'));
    $this->assertSame('de', $mapping->map('DE'));
  }

  /**
   * Tests that map() trims whitespace.
   *
   * @covers ::map
   */
  public function testMapTrimsWhitespace(): void {
    $mapping = $this->createCountryMapping();
    $this->assertSame('us', $mapping->map('  US  '));
  }

  /**
   * Tests that map() uses config entity mapping when available.
   *
   * @covers ::map
   */
  public function testMapUsesConfigEntityMapping(): void {
    $mapping = $this->createCountryMapping(['en' => 'GB']);
    $this->assertSame('gb', $mapping->map('en'));
  }

  /**
   * Tests that map() falls back to code when no config entity exists.
   *
   * @covers ::map
   */
  public function testMapFallsBackToCodeWithoutConfigEntity(): void {
    $mapping = $this->createCountryMapping(['en' => 'GB']);
    $this->assertSame('fr', $mapping->map('FR'));
  }

  /**
   * Tests that getOptionAttributes() returns correct structure.
   *
   * @covers ::getOptionAttributes
   */
  public function testGetOptionAttributesReturnsCorrectStructure(): void {
    $mapping = $this->createCountryMapping();
    $attributes = $mapping->getOptionAttributes(['NL', 'DE']);

    $this->assertArrayHasKey('NL', $attributes);
    $this->assertArrayHasKey('DE', $attributes);
    $this->assertInstanceOf('Drupal\Core\Template\Attribute', $attributes['NL']);
    $this->assertInstanceOf('Drupal\Core\Template\Attribute', $attributes['DE']);
  }

  /**
   * Tests that getOptionAttributes() includes correct CSS classes.
   *
   * @covers ::getOptionAttributes
   */
  public function testGetOptionAttributesIncludesCorrectClasses(): void {
    $mapping = $this->createCountryMapping();
    $attributes = $mapping->getOptionAttributes(['NL']);

    $rendered = (string) $attributes['NL'];
    $this->assertStringContainsString('flag', $rendered);
    $this->assertStringContainsString('flag-nl', $rendered);
    $this->assertStringContainsString('country-flag', $rendered);
  }

  /**
   * Tests that Country mapping returns correct extra classes.
   *
   * @covers \Drupal\flags\Mapping\Country::getExtraClasses
   */
  public function testCountryExtraClasses(): void {
    $mapping = $this->createCountryMapping();
    $this->assertSame(['country-flag'], $mapping->getExtraClasses());
  }

  /**
   * Tests that Language mapping returns correct extra classes.
   *
   * @covers \Drupal\flags\Mapping\Language::getExtraClasses
   */
  public function testLanguageExtraClasses(): void {
    $mapping = $this->createLanguageMapping();
    $this->assertSame(['flag-lang'], $mapping->getExtraClasses());
  }

}
