<?php

namespace Drupal\Tests\entity_embed\Kernel;

use Drupal\entity_embed\Plugin\Filter\EntityEmbedFilter;
use Drupal\entity_embed\Plugin\entity_embed\EntityEmbedDisplay\EntityReferenceFieldFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that entity embed disables certain integrations.
 */
#[Group('entity_embed')]
#[RunTestsInSeparateProcesses]
#[CoversClass(EntityEmbedFilter::class)]
#[CoversMethod(EntityReferenceFieldFormatter::class, 'disableContextualLinks')]
#[CoversMethod(EntityReferenceFieldFormatter::class, 'disableQuickEdit')]
class EntityEmbedFilterDisabledIntegrationsTest extends EntityEmbedFilterTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'contextual',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig('system');
    $this->container->get('current_user')
      ->getAccount()
      ->addRole($this->drupalCreateRole([
        'access contextual links',
      ]));
  }

  /**
   * Tests that integrations are disabled on embedded entities.
   */
  #[DataProvider('providerDisabledIntegrations')]
  public function testDisabledIntegrations($integration_detection_selector) {
    $text = $this->createEmbedCode([
      'data-entity-type' => 'node',
      'data-entity-uuid' => static::EMBEDDED_ENTITY_UUID,
      'data-view-mode' => 'teaser',
    ]);

    $this->applyFilter($text);
    $this->assertCount(0, $this->cssSelect($integration_detection_selector));
  }

  /**
   * Data provider for testDisabledIntegrations().
   */
  public static function providerDisabledIntegrations(): array {
    return [
      'contextual' => [
        'div.embedded-entity > .contextual-region',
      ],
    ];
  }

}
