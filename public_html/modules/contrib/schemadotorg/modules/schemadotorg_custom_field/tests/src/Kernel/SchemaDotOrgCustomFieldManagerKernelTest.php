<?php

declare(strict_types=1);

namespace Drupal\Tests\schemadotorg_custom_field\Kernel;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgEntityKernelTestBase;

/**
 * Tests the functionality of the Schema.org custom field manager.
 *
 * @covers \Drupal\schemadotorg_custom_field\SchemaDotOrgCustomFieldDefaultVocabularyManager
 * @group schemadotorg
 */
class SchemaDotOrgCustomFieldManagerKernelTest extends SchemaDotOrgEntityKernelTestBase {

  // phpcs:disable
  /**
   * Disabled config schema checking until the custom field module has a schema.
   */
  protected $strictConfigSchema = FALSE;
  // phpcs:enabled

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'cer',
    'custom_field',
    'schemadotorg_options',
    'schemadotorg_cer',
    'schemadotorg_custom_field',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(static::$modules);

    \Drupal::moduleHandler()->loadInclude('schemadotorg_cer', 'install');
    schemadotorg_cer_install(FALSE);
  }

  /**
   * Test Schema.org custom field manager.
   */
  public function testManager(): void {
    /* ********************************************************************** */
    // Recipe.
    /* ********************************************************************** */

    $this->createSchemaEntity('node', 'Recipe');

    // Check recipe nutrition custom field storage columns.
    /** @var \Drupal\field\FieldStorageConfigInterface|null $field_storage_config */
    $field_storage_config = FieldStorageConfig::loadByName('node', 'schema_nutrition');
    $expected_settings = [
      'columns' => [
        'serving_size' => [
          'name' => 'serving_size',
          'type' => 'string',
          'length' => 255,
        ],
        'calories' => [
          'name' => 'calories',
          'type' => 'integer',
          'unsigned' => FALSE,
          'size' => 'normal',
        ],
        'carbohydrate_content' => [
          'name' => 'carbohydrate_content',
          'type' => 'integer',
          'unsigned' => FALSE,
          'size' => 'normal',
        ],
        'cholesterol_content' => [
          'name' => 'cholesterol_content',
          'type' => 'integer',
          'unsigned' => FALSE,
          'size' => 'normal',
        ],
        'fat_content' => [
          'name' => 'fat_content',
          'type' => 'integer',
          'unsigned' => FALSE,
          'size' => 'normal',
        ],
        'fiber_content' => [
          'name' => 'fiber_content',
          'type' => 'integer',
          'unsigned' => FALSE,
          'size' => 'normal',
        ],
        'protein_content' => [
          'name' => 'protein_content',
          'type' => 'integer',
          'unsigned' => FALSE,
          'size' => 'normal',
        ],
        'saturated_fat_content' => [
          'name' => 'saturated_fat_content',
          'type' => 'integer',
          'unsigned' => FALSE,
          'size' => 'normal',
        ],
        'sodium_content' => [
          'name' => 'sodium_content',
          'type' => 'integer',
          'unsigned' => FALSE,
          'size' => 'normal',
        ],
        'sugar_content' => [
          'name' => 'sugar_content',
          'type' => 'integer',
          'unsigned' => FALSE,
          'size' => 'normal',
        ],
        'trans_fat_content' => [
          'name' => 'trans_fat_content',
          'type' => 'integer',
          'unsigned' => FALSE,
          'size' => 'normal',
        ],
        'unsaturated_fat_content' => [
          'name' => 'unsaturated_fat_content',
          'type' => 'integer',
          'unsigned' => FALSE,
          'size' => 'normal',
        ],
      ],
    ];
    $this->assertEquals($expected_settings, $field_storage_config->getSettings());

    // Check recipe nutrition custom field column widget settings.
    /** @var \Drupal\Core\Field\FieldConfigInterface $field_config */
    $field_config = FieldConfig::loadByName('node', 'recipe', 'schema_nutrition');
    $settings = $field_config->getSettings();
    $expected_settings_serving_size = [
      'type' => 'text',
      'widget_settings' => [
        'label' => 'Serving size',
        'settings' => [
          'description' => 'The serving size, in terms of the number of volume or mass.',
          'size' => 60,
          'placeholder' => '',
          'maxlength' => 255,
          'maxlength_js' => FALSE,
          'description_display' => 'after',
          'required' => FALSE,
          'prefix' => '',
          'suffix' => '',
        ],
      ],
      'check_empty' => FALSE,
      'weight' => 0,
    ];
    $this->assertEquals($expected_settings_serving_size, $settings['field_settings']['serving_size']);
    $expected_settings_calories = [
      'type' => 'integer',
      'widget_settings' => [
        'label' => 'Calories',
        'settings' => [
          'description' => 'The number of calories.',
          'description_display' => 'after',
          'placeholder' => '',
          'min' => 0,
          'max' => 1000,
          'prefix' => '',
          'suffix' => ' calories',
          'required' => FALSE,
        ],
      ],
      'check_empty' => FALSE,
      'weight' => 1,
    ];
    $this->assertEquals($expected_settings_calories, $settings['field_settings']['calories']);

    // Check custom field form display.
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $entity_form_display */
    $entity_form_display = EntityFormDisplay::load('node.recipe.default');
    $components = $entity_form_display->getComponents();
    $expected_component = [
      'type' => 'custom_stacked',
      'weight' => 150,
      'region' => 'content',
      'settings' => [
        'label' => TRUE,
        'wrapper' => 'fieldset',
        'open' => TRUE,
      ],
      'third_party_settings' => [],
    ];
    $this->assertEquals($expected_component, $components['schema_nutrition']);

    // Check custom field view display.
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $entity_form_display */
    $entity_view_display = EntityViewDisplay::load('node.recipe.default');
    $components = $entity_view_display->getComponents();
    $component_defaults = [
      'wrappers' => [
        'field_wrapper_tag' => '',
        'field_wrapper_classes' => '',
        'field_tag' => '',
        'field_classes' => '',
        'label_tag' => '',
        'label_classes' => '',
      ],
      'formatter_settings' => [
        'prefix_suffix' => TRUE,
      ],
    ];
    $expected_component = [
      'type' => 'custom_formatter',
      'label' => 'above',
      'settings' => [
        'fields' => [
          'calories' => [
            'format_type' => 'number_integer',
          ] + $component_defaults,
          'carbohydrate_content' => [
            'format_type' => 'number_integer',
          ] + $component_defaults,
          'cholesterol_content' => [
            'format_type' => 'number_integer',
          ] + $component_defaults,
          'fat_content' => [
            'format_type' => 'number_integer',
          ] + $component_defaults,
          'fiber_content' => [
            'format_type' => 'number_integer',
          ] + $component_defaults,
          'protein_content' => [
            'format_type' => 'number_integer',
          ] + $component_defaults,
          'saturated_fat_content' => [
            'format_type' => 'number_integer',
          ] + $component_defaults,
          'sodium_content' => [
            'format_type' => 'number_integer',
          ] + $component_defaults,
          'sugar_content' => [
            'format_type' => 'number_integer',
          ] + $component_defaults,
          'trans_fat_content' => [
            'format_type' => 'number_integer',
          ] + $component_defaults,
          'unsaturated_fat_content' => [
            'format_type' => 'number_integer',
          ] + $component_defaults,
        ],
      ],
      'third_party_settings' => [],
      'weight' => 150,
      'region' => 'content',
    ];
    $this->assertEquals($expected_component, $components['schema_nutrition']);

    /* ********************************************************************** */
    // FAQPage.
    /* ********************************************************************** */

    $this->createSchemaEntity('node', 'FAQPage');

    // Check FAQ page main entity custom field storage columns.
    /** @var \Drupal\field\FieldStorageConfigInterface|null $field_storage_config */
    $field_storage_config = FieldStorageConfig::loadByName('node', 'schema_faq_main_entity');
    $expected_settings = [
      'columns' => [
        'name' => [
          'name' => 'name',
          'type' => 'string_long',
        ],
        'accepted_answer' => [
          'name' => 'accepted_answer',
          'type' => 'string_long',
        ],
      ],
    ];
    $this->assertEquals($expected_settings, $field_storage_config->getSettings());

    // Check faq page main entity custom field column widget settings.
    /** @var \Drupal\Core\Field\FieldConfigInterface $field_config */
    $field_config = FieldConfig::loadByName('node', 'faq', 'schema_faq_main_entity');
    $settings = $field_config->getSettings();
    $expected_settings_serving_size = [
      'type' => 'textarea',
      'widget_settings' => [
        'label' => 'Question',
        'settings' => [
          'description' => 'The name of the item.',
          'rows' => 5,
          'placeholder' => '',
          'maxlength' => '',
          'maxlength_js' => FALSE,
          'formatted' => TRUE,
          'default_format' => 'basic_html',
          'format' => [
            'guidelines' => FALSE,
            'help' => FALSE,
          ],
          'description_display' => 'after',
          'required' => FALSE,
        ],
      ],
      'check_empty' => FALSE,
      'weight' => 0,
    ];
    $this->assertEquals($expected_settings_serving_size, $settings['field_settings']['name']);

    /* ********************************************************************** */
    // DietarySupplement.
    /* ********************************************************************** */

    $this->createSchemaEntity('node', 'DietarySupplement');

    // Check dietary supplement maximum intake custom field storage columns.
    /** @var \Drupal\field\FieldStorageConfigInterface|null $field_storage_config */
    $field_storage_config = FieldStorageConfig::loadByName('node', 'schema_max_intake');
    $expected_settings = [
      'columns' => [
        'target_population' => [
          'name' => 'target_population',
          'type' => 'string',
          'length' => 255,
        ],
        'dose_value' => [
          'name' => 'dose_value',
          'type' => 'integer',
          'unsigned' => FALSE,
          'size' => 'normal',
        ],
        'dose_unit' => [
          'name' => 'dose_unit',
          'type' => 'string',
          'length' => 255,
        ],
        'frequency' => [
          'name' => 'frequency',
          'type' => 'string',
          'length' => 255,
        ],
      ],
    ];
    $this->assertEquals($expected_settings, $field_storage_config->getSettings());

    // Check dietary supplement maximum intake custom field column widget settings.
    /** @var \Drupal\Core\Field\FieldConfigInterface $field_config */
    $field_config = FieldConfig::loadByName('node', 'dietary_supplement', 'schema_max_intake');
    $settings = $field_config->getSettings();
    $expected_settings_frequency = [
      'type' => 'select',
      'weight' => 3,
      'check_empty' => FALSE,
      'widget_settings' => [
        'label' => 'Frequency',
        'settings' => [
          'description' => 'How often the dose is taken, e.g. \'daily\'.',
          'description_display' => 'after',
          'required' => FALSE,
          'empty_option' => '- Select -',
          'allowed_values' => [
            ['key' => 'daily', 'value' => 'Daily'],
            ['key' => '2_times_a_day', 'value' => '2 times a day'],
            ['key' => '3_times_a_day', 'value' => '3 times a day'],
            ['key' => '4_times_a_day', 'value' => '4 times a day'],
            ['key' => '5_times_a_day', 'value' => '5 times a day'],
            ['key' => 'every_3_hours', 'value' => 'Every 3 hours'],
            ['key' => 'every_6_hours', 'value' => 'Every 6 hours'],
            ['key' => 'every_8_hours', 'value' => 'Every 8 hours'],
            ['key' => 'every_12_hours', 'value' => 'Every 12 hours'],
            ['key' => 'every_24_hours', 'value' => 'Every 24 hours'],
            ['key' => 'bedtime', 'value' => 'Bedtime'],
          ],
        ],
      ],
    ];
    $this->assertEquals($expected_settings_frequency, $settings['field_settings']['frequency']);

    /* ********************************************************************** */

    // Check Quiz mapping defaults hasPart to custom.
    $mapping_default = $this->mappingManager->getMappingDefaults(
      entity_type_id: 'node',
      schema_type: 'Quiz',
    );
    $this->assertEquals('custom', $mapping_default['properties']['hasPart']['type']);
  }

  /**
   * Test Schema.org custom field settings.
   */
  public function testCustomSettings(): void {
    // Check default_schema_properties custom field settings.
    $this->config('schemadotorg_custom_field.settings')
      ->set('default_schema_properties.Thing--alternateName', [
        'schema_type' => 'Thing',
        'schema_properties' => [
          'integer' => [
            'data_type' => 'integer',
            'max_length' => '999',
            'unsigned' => 0,
            'precision' => '99',
            'scale' => '9',
            'min' => '99',
            'max' => '999',
          ],
          'string' => [
            'data_type' => 'string',
            'widget_type' => 'select',
            'name' => 'custom_string',
            'label' => 'Custom string',
            'description' => 'Custom description',
            'placeholder' => 'Custom placeholder',
            'maxlength' => 999,
            'prefix' => 'Custom prefix',
            'suffix' => 'Custom suffix',
            'required' => TRUE,
          ],
          'allowed_values' => [
            'data_type' => 'string',
            'empty_option' => 'Custom empty option',
            'allowed_values' => [
              'one' => 'One',
              'two' => 'Two',
              'three' => 'Three',
            ],
          ],
          'entity_reference' => [
            'data_type' => 'entity_reference',
            'empty_option' => 'Custom entity reference',
            'target_type' => 'media',
            'handler_settings' => [
              'target_bundles' => ['image' => 'image'],
            ],
          ],
          'link' => [
            'data_type' => 'link',
          ],
        ],
        'widget_id' => 'custom_stacked',
        'widget_settings' => ['wrapper' => 'details', 'open' => FALSE],
      ])
      ->save();
    $this->appendSchemaTypeDefaultProperties('Thing', 'alternateName');
    $this->createSchemaEntity('node', 'Thing');

    // Check alternate name custom field storage columns.
    /** @var \Drupal\field\FieldStorageConfigInterface|null $field_storage_config */
    $field_storage_config = FieldStorageConfig::loadByName('node', 'schema_alternate_name');
    $expected_settings = [
      'columns' => [
        'integer' => [
          'name' => 'integer',
          'type' => 'integer',
          'unsigned' => FALSE,
          'size' => 'normal',
        ],
        'custom_string' => [
          'name' => 'custom_string',
          'type' => 'string',
          'length' => 255,
        ],
        'allowed_values' => [
          'name' => 'allowed_values',
          'type' => 'string',
          'length' => 255,
        ],
        'entity_reference' => [
          'name' => 'entity_reference',
          'type' => 'entity_reference',
          'target_type' => 'media',
        ],
        'link' => [
          'name' => 'link',
          'type' => 'link',
        ],
      ],
    ];
    $this->assertEquals($expected_settings, $field_storage_config->getSettings());

    // Check schema_alternate_name custom field column widget settings.
    /** @var \Drupal\Core\Field\FieldConfigInterface $field_config */
    $field_config = FieldConfig::loadByName('node', 'thing', 'schema_alternate_name');
    $settings = $field_config->getSettings();
    $expected_settings = [
      'integer' => [
        'type' => 'integer',
        'weight' => 0,
        'check_empty' => FALSE,
        'widget_settings' => [
          'label' => 'Integer',
          'settings' => [
            'description' => '',
            'description_display' => 'after',
            'placeholder' => '',
            'min' => 99,
            'max' => 999,
            'prefix' => '',
            'suffix' => '',
            'required' => FALSE,
          ],
        ],
      ],
      'custom_string' => [
        'type' => 'select',
        'weight' => 1,
        'check_empty' => FALSE,
        'widget_settings' => [
          'label' => 'Custom string',
          'settings' => [
            'description' => 'Custom description',
            'description_display' => 'after',
            'required' => TRUE,
            'empty_option' => '- Select -',
            'allowed_values' => [],
          ],
        ],
      ],
      'allowed_values' => [
        'type' => 'select',
        'weight' => 2,
        'check_empty' => FALSE,
        'widget_settings' => [
          'label' => 'Allowed_values',
          'settings' => [
            'description' => '',
            'description_display' => 'after',
            'required' => FALSE,
            'empty_option' => 'Custom empty option',
            'allowed_values' => [
              ['value' => 'One', 'key' => 'one'],
              ['value' => 'Two', 'key' => 'two'],
              ['value' => 'Three', 'key' => 'three'],
            ],
          ],
        ],
      ],
      'entity_reference' => [
        'type' => 'entity_reference_autocomplete',
        'weight' => 3,
        'check_empty' => FALSE,
        'widget_settings' => [
          'label' => 'Entity_reference',
          'settings' => [
            'description' => '',
            'description_display' => 'after',
            'size' => 60,
            'placeholder' => '',
            'required' => FALSE,
            'match_operator' => 'CONTAINS',
            'match_limit' => 10,
            'handler' => 'default:media',
            'handler_settings' => [
              'target_bundles' => [
                'image' => 'image',
              ],
            ],
          ],
        ],
      ],
      'link' => [
        'type' => 'link_default',
        'weight' => 4,
        'check_empty' => FALSE,
        'widget_settings' => [
          'label' => 'Link',
          'settings' => [
            'description' => '',
            'description_display' => 'after',
            'required' => FALSE,
            'link_type' => 17,
            'field_prefix' => 'default',
            'field_prefix_custom' => '',
            'title' => 1,
            'enabled_attributes' => [
              'id' => FALSE,
              'name' => FALSE,
              'target' => TRUE,
              'rel' => TRUE,
              'class' => TRUE,
              'accesskey' => FALSE,
            ],
            'widget_default_open' => 'expandIfValuesSet',
            'placeholder_url' => '',
            'placeholder_title' => '',
          ],
        ],
      ],
    ];
    $this->assertEquals($expected_settings, $settings['field_settings']);

    // Check entity form display settings.
    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository */
    $entity_display_repository = \Drupal::service('entity_display.repository');
    $form_display = $entity_display_repository->getFormDisplay('node', 'thing', 'default');
    $component = $form_display->getComponent('schema_alternate_name');
    $this->assertEquals('custom_stacked', $component['type']);
    $this->assertEquals('details', $component['settings']['wrapper']);
    $this->assertFalse($component['settings']['open']);

    /* ********************************************************************** */
    // Custom.
    /* ********************************************************************** */

    $this->config('schemadotorg_custom_field.settings')->set('default_schema_properties.field_custom', [
      'schema_properties' => [
        'name' => ['data_type' => 'string'],
        'value' => ['data_type' => 'string'],
      ],
    ])->save();

    $this->createSchemaEntity('node', 'Thing', [
      'properties' => [
        'field_custom' => [
          'type' => 'custom',
          'name' => 'field_custom',
          'label' => 'Custom',
        ],
      ],
    ]);

    // Check custom field storage columns.
    /** @var \Drupal\field\FieldStorageConfigInterface|null $field_storage_config */
    $field_storage_config = FieldStorageConfig::loadByName('node', 'field_custom');
    $expected_settings = [
      'columns' => [
        'name' => [
          'name' => 'name',
          'type' => 'string',
          'length' => 255,
        ],
        'value' => [
          'name' => 'value',
          'type' => 'string',
          'length' => 255,
        ],
      ],
    ];
    $this->assertEquals($expected_settings, $field_storage_config->getSettings());
  }

}
