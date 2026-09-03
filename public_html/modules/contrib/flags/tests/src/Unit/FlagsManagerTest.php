<?php

namespace Drupal\Tests\flags\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\flags\Flags\FlagsManager;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for the FlagsManager service.
 *
 * @coversDefaultClass \Drupal\flags\Flags\FlagsManager
 * @group flags
 */
class FlagsManagerTest extends UnitTestCase {

  /**
   * The module handler mock.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $moduleHandler;

  /**
   * The flags manager under test.
   *
   * @var \Drupal\flags\Flags\FlagsManager
   */
  protected $flagsManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // FlagsManager::getStandardList() builds an array of TranslatableMarkup
    // values and sorts them with natcasesort(), which casts each element to
    // a string. From Drupal 11.4 onward, TranslatableMarkup::__toString()
    // resolves the string_translation service eagerly, so a unit test
    // touching this code path needs a container with that service set.
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $this->flagsManager = new FlagsManager($this->moduleHandler);
  }

  /**
   * Tests that getStandardList() returns a non-empty array.
   *
   * @covers ::getStandardList
   */
  public function testGetStandardListReturnsNonEmptyArray(): void {
    $list = $this->flagsManager->getStandardList();
    $this->assertNotEmpty($list);
    $this->assertIsArray($list);
  }

  /**
   * Tests that getStandardList() contains expected country codes.
   *
   * @covers ::getStandardList
   */
  public function testGetStandardListContainsExpectedCodes(): void {
    $list = $this->flagsManager->getStandardList();
    $this->assertArrayHasKey('nl', $list);
    $this->assertArrayHasKey('de', $list);
    $this->assertArrayHasKey('us', $list);
    $this->assertArrayHasKey('gb', $list);
    $this->assertArrayHasKey('fr', $list);
  }

  /**
   * Tests that getStandardList() is sorted alphabetically by name.
   *
   * @covers ::getStandardList
   */
  public function testGetStandardListIsSorted(): void {
    $list = $this->flagsManager->getStandardList();
    $names = array_map('strval', array_values($list));
    $sorted = $names;
    natcasesort($sorted);
    $sorted = array_values($sorted);
    $this->assertSame($sorted, $names);
  }

  /**
   * Tests that getList() invokes the alter hook.
   *
   * @covers ::getList
   */
  public function testGetListInvokesAlterHook(): void {
    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('flags', $this->callback(function ($value) {
        return is_array($value);
      }));

    $this->flagsManager->getList();
  }

  /**
   * Tests that getList() caches the result.
   *
   * @covers ::getList
   */
  public function testGetListCachesResult(): void {
    $this->moduleHandler->expects($this->once())
      ->method('alter');

    $first = $this->flagsManager->getList();
    $second = $this->flagsManager->getList();
    $this->assertSame($first, $second);
  }

}
