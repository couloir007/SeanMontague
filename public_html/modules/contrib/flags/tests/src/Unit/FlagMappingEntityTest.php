<?php

namespace Drupal\Tests\flags\Unit;

use Drupal\flags\Entity\CountryFlagMapping;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for the FlagMapping entity.
 *
 * @coversDefaultClass \Drupal\flags\Entity\FlagMapping
 * @group flags
 */
class FlagMappingEntityTest extends UnitTestCase {

  /**
   * Tests that id() returns the source value.
   *
   * @covers ::id
   */
  public function testIdReturnsSource(): void {
    $entity = new CountryFlagMapping(['source' => 'nl', 'flag' => 'nl'], 'country_flag_mapping');
    $this->assertSame('nl', $entity->id());
  }

  /**
   * Tests setId() and id() round-trip.
   *
   * @covers ::setId
   * @covers ::id
   */
  public function testSetIdRoundTrip(): void {
    $entity = new CountryFlagMapping([], 'country_flag_mapping');
    $result = $entity->setId('de');
    $this->assertSame($entity, $result);
    $this->assertSame('de', $entity->id());
  }

  /**
   * Tests setSource() and getSource() round-trip.
   *
   * @covers ::setSource
   * @covers ::getSource
   */
  public function testSetSourceRoundTrip(): void {
    $entity = new CountryFlagMapping([], 'country_flag_mapping');
    $result = $entity->setSource('fr');
    $this->assertSame($entity, $result);
    $this->assertSame('fr', $entity->getSource());
  }

  /**
   * Tests that setFlag() stores the value in lowercase.
   *
   * @covers ::setFlag
   * @covers ::getFlag
   */
  public function testSetFlagStoresLowercase(): void {
    $entity = new CountryFlagMapping([], 'country_flag_mapping');
    $entity->setFlag('NL');
    $this->assertSame('nl', $entity->getFlag());
  }

  /**
   * Tests that setFlag() returns the entity for chaining.
   *
   * @covers ::setFlag
   */
  public function testSetFlagReturnsSelf(): void {
    $entity = new CountryFlagMapping([], 'country_flag_mapping');
    $result = $entity->setFlag('gb');
    $this->assertSame($entity, $result);
  }

  /**
   * Tests getFlag() returns stored value.
   *
   * @covers ::getFlag
   */
  public function testGetFlagReturnsStoredValue(): void {
    $entity = new CountryFlagMapping(['source' => 'en', 'flag' => 'gb'], 'country_flag_mapping');
    $this->assertSame('gb', $entity->getFlag());
  }

}
