<?php

declare(strict_types=1);

namespace Drupal\Tests\content_model_documentation\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\content_model_documentation\Traits\CMDocumentTestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Defines a base kernel test for CMDocument tests.
 */
abstract class CMDocumentKernelTestBase extends KernelTestBase {

  use UserCreationTrait;
  use CMDocumentTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_model_documentation',
    'system',
    'user',
    'datetime_range',
    'datetime',
    'options',
    'filter',
    'path_alias',
    'text',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system', 'content_model_documentation', 'filter', 'views']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('cm_document');
    $this->installEntitySchema('path_alias');
    $this->setUpCurrentUser();
  }

}
