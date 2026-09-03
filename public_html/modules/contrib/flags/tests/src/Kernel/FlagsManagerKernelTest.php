<?php

namespace Drupal\Tests\flags\Kernel;

use Drupal\flags\Flags\FlagsManager;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests FlagsManager service integration.
 *
 * @group flags
 */
#[RunTestsInSeparateProcesses]
class FlagsManagerKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['flags'];

  /**
   * Tests that the flags manager service can be loaded.
   */
  public function testServiceExists(): void {
    $service = $this->container->get('flags.manager');
    $this->assertInstanceOf(FlagsManager::class, $service);
  }

  /**
   * Tests that getList() returns the standard flag list.
   */
  public function testGetListReturnsFlags(): void {
    $service = $this->container->get('flags.manager');
    $list = $service->getList();

    $this->assertNotEmpty($list);
    $this->assertArrayHasKey('nl', $list);
    $this->assertArrayHasKey('us', $list);
    $this->assertArrayHasKey('de', $list);
  }

}
