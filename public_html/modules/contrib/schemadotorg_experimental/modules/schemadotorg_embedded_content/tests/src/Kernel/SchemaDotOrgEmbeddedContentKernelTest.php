<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg_embedded_content\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\schemadotorg\Utility\SchemaDotOrgStringHelper;
use Drupal\schemadotorg_embedded_content_test\Plugin\EmbeddedContent\SchemaDotOrgThing;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgEntityKernelTestBase;

/**
 * Tests the functionality of the Schema.org Embedded Content module.
 *
 * @covers \Drupal\schemadotorg_embedded_content\Plugin\SchemaDotOrgEmbeddedContentBase
 *
 * @group schemadotorg
 */
class SchemaDotOrgEmbeddedContentKernelTest extends SchemaDotOrgEntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'sdc',
    'embedded_content',
    'schemadotorg_jsonld',
    'schemadotorg_embedded_content',
    'schemadotorg_embedded_content_test',
  ];

  /**
   * Test Schema.org Blueprints embedded content.
   */
  public function testEmbeddedContent(): void {
    /** @var \Drupal\embedded_content\EmbeddedContentPluginManager $embedded_content_manager */
    $embedded_content_manager = $this->container->get('plugin.manager.embedded_content');

    /** @var \Drupal\schemadotorg_embedded_content\Plugin\SchemaDotOrgEmbeddedContentInterface $embedded_content */
    $embedded_content = SchemaDotOrgThing::create(
      $this->container,
      [
        'name' => 'Drupal.org',
        'description' => 'Drupal is an open source platform for building amazing digital experiences. It\'s made by a dedicated community. Anyone can use it, and it will always be free.',
        'url' => 'https://drupal.org',
        'align' => 'left',
      ],
      'schemadotorg_thing',
      $embedded_content_manager->getDefinition('schemadotorg_thing')
    );

    /* ********************************************************************** */

    // Check default configuration.
    // @see \Drupal\schemadotorg_embedded_content\Plugin\SchemaDotOrgEmbeddedContentBase::defaultConfiguration()
    $expected_result = [
      'subtype' => '',
      'name' => '',
      'description' => '',
      'url' => '',
      'align' => 'center',
    ];
    $this->assertEquals($expected_result, $embedded_content->defaultConfiguration());

    // Check the embedded content configuration form.
    // @see \Drupal\schemadotorg_embedded_content\Plugin\SchemaDotOrgEmbeddedContentBase::buildConfigurationForm()
    // phpcs:disable DrupalPractice.General.DescriptionT.DescriptionT
    // phpcs:disable DrupalPractice.General.OptionsT.TforValue
    $form = [];
    $form_state = new FormState();
    $form = $embedded_content->buildConfigurationForm($form, $form_state);
    SchemaDotOrgStringHelper::convertRenderMarkupToStrings($form);
    $expected_result = [
      'subtype' => [
        '#type' => 'select',
        '#options' => [
          '' => '',
          'Event' => 'Event',
          'Organization' => 'Organization',
          'Place' => 'Place',
          'Person' => 'Person',
        ],
        '#title' => 'Subtype',
        '#description' => 'A more specific subtype for the item.',
        '#required' => FALSE,
        '#default_value' => '',
      ],
      'name' => [
        '#type' => 'textfield',
        '#title' => 'Name',
        '#description' => 'The name of the item.',
        '#required' => TRUE,
        '#default_value' => 'Drupal.org',
      ],
      'description' => [
        '#type' => 'textarea',
        '#title' => 'Description',
        '#description' => 'A description of the item.',
        '#required' => FALSE,
        '#default_value' => 'Drupal is an open source platform for building amazing digital experiences. It\'s made by a dedicated community. Anyone can use it, and it will always be free.',
      ],
      'url' => [
        '#type' => 'textfield',
        '#title' => 'URL',
        '#description' => 'URL of the item.',
        '#required' => FALSE,
        '#default_value' => 'https://drupal.org',
      ],
      'align' => [
        '#type' => 'select',
        '#options' => [
          'center' => 'center',
          'left' => 'left',
          'right' => 'right',
        ],
        '#title' => 'Align',
        '#description' => 'Set the alignment for the quotation.',
        '#required' => FALSE,
        '#default_value' => 'left',
      ],
    ];
    // phpcs:enable DrupalPractice.General.DescriptionT.DescriptionT
    $this->assertEquals($expected_result, $form);

    // Check rendering the embedded content.
    // @see \Drupal\schemadotorg_embedded_content\Plugin\SchemaDotOrgEmbeddedContentBase::build()
    $expected_result = [
      '#type' => 'component',
      '#component' => 'schemadotorg_embedded_content_test:thing',
      '#props' => [
        'subtype' => '',
        'name' => 'Drupal.org',
        'description' => 'Drupal is an open source platform for building amazing digital experiences. It\'s made by a dedicated community. Anyone can use it, and it will always be free.',
        'url' => 'https://drupal.org',
        'align' => 'left',
      ],
    ];
    $this->assertEquals($expected_result, $embedded_content->build());

    // Check getting embedded content's JSON-LD.
    // @see \Drupal\schemadotorg_embedded_content\Plugin\SchemaDotOrgEmbeddedContentBase::getJsonId
    $expected_result = [
      '@type' => 'Thing',
      'name' => 'Drupal.org',
      'description' => 'Drupal is an open source platform for building amazing digital experiences. It\'s made by a dedicated community. Anyone can use it, and it will always be free.',
      'url' => 'https://drupal.org',
    ];
    $this->assertEquals($expected_result, $embedded_content->getJsonId());
  }

}
