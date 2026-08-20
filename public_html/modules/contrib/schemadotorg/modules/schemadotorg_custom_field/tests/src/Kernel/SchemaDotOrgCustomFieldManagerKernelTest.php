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
 * @covers \Drupal\schemadotorg_custom_field\SchemaDotOrgCustomFieldManager
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
      'label' => 'Serving size',
      'check_empty' => FALSE,
      'required' => FALSE,
      'translatable' => FALSE,
      'description' => 'The serving size, in terms of the number of volume or mass.',
      'description_display' => 'after',
      'prefix' => '',
      'suffix' => '',
      'allowed_values' => [],
    ];
    $this->assertEquals($expected_settings_serving_size, $settings['field_settings']['serving_size']);
    $expected_settings_calories = [
      'label' => 'Calories',
      'check_empty' => FALSE,
      'required' => FALSE,
      'translatable' => FALSE,
      'description' => 'The number of calories.',
      'description_display' => 'after',
      'prefix' => '',
      'suffix' => ' calories',
      'allowed_values' => [],
      'min' => 0,
      'max' => 1000,
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
          'wrapper' => 'fieldset',
          'label_value' => '',
          'label_limit' => 60,
          'label_prefix' => 'Item',
          'auto_collapse' => FALSE,
          'open' => TRUE,
          'fields' => [
              'serving_size' => [
                  'weight' => 0,
                  'label' => 'Serving size',
                  'size' => 60,
                  'placeholder' => '',
                  'maxlength' => NULL,
                  'maxlength_js' => FALSE,
                  'type' => 'text',
              ],
              'calories' => [
                  'weight' => 1,
                  'label' => 'Calories',
                  'placeholder' => '',
                  'type' => 'integer',
              ],
              'carbohydrate_content' => [
                  'weight' => 2,
                  'label' => 'Carbohydrate content',
                  'placeholder' => '',
                  'type' => 'integer',
              ],
              'cholesterol_content' => [
                  'weight' => 3,
                  'label' => 'Cholesterol',
                  'placeholder' => '',
                  'type' => 'integer',
              ],
              'fat_content' => [
                  'weight' => 4,
                  'label' => 'Fat',
                  'placeholder' => '',
                  'type' => 'integer',
              ],
              'fiber_content' => [
                  'weight' => 5,
                  'label' => 'Fiber',
                  'placeholder' => '',
                  'type' => 'integer',
              ],
              'protein_content' => [
                  'weight' => 6,
                  'label' => 'Protein',
                  'placeholder' => '',
                  'type' => 'integer',
              ],
              'saturated_fat_content' => [
                  'weight' => 7,
                  'label' => 'Saturated Fat',
                  'placeholder' => '',
                  'type' => 'integer',
              ],
              'sodium_content' => [
                  'weight' => 8,
                  'label' => 'Sodium',
                  'placeholder' => '',
                  'type' => 'integer',
              ],
              'sugar_content' => [
                  'weight' => 9,
                  'label' => 'Sugar',
                  'placeholder' => '',
                  'type' => 'integer',
              ],
              'trans_fat_content' => [
                  'weight' => 10,
                  'label' => 'Trans fat',
                  'placeholder' => '',
                  'type' => 'integer',
              ],
              'unsaturated_fat_content' => [
                  'weight' => 11,
                  'label' => 'Unsaturated fat',
                  'placeholder' => '',
                  'type' => 'integer',
              ],
          ],
      ],
      'third_party_settings' => [],
    ];
    $this->assertEquals($expected_component, $components['schema_nutrition']);

    // Check custom field view display.
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $entity_form_display */
    $entity_view_display = EntityViewDisplay::load('node.recipe.default');
    $components = $entity_view_display->getComponents();
    $expected_component = [
      'type' => 'custom_formatter',
      'label' => 'above',
      'settings' => [
        'fields' => [
          'serving_size' => [
            'weight' => 0,
            'format_type' => 'string',
            'wrappers' => [
              'field_wrapper_tag' => '',
              'field_wrapper_classes' => '',
              'field_tag' => '',
              'field_classes' => '',
              'label_tag' => '',
              'label_classes' => '',
            ],
            'formatter_settings' => [
              'field_label' => 'Serving size',
            ],
            'prefix_suffix' => FALSE,
            'key_label' => 'label',
          ],
          'calories' => [
            'weight' => 1,
            'format_type' => 'number_integer',
            'wrappers' => [
              'field_wrapper_tag' => '',
              'field_wrapper_classes' => '',
              'field_tag' => '',
              'field_classes' => '',
              'label_tag' => '',
              'label_classes' => '',
            ],
            'formatter_settings' => [
              'field_label' => 'Calories',
              'prefix_suffix' => TRUE,
            ],
            'scale' => 0,
            'thousand_separator' => ',',
            'prefix_suffix' => FALSE,
            'decimal_separator' => '.',
            'key_label' => 'label',
          ],
          'carbohydrate_content' => [
            'weight' => 2,
            'format_type' => 'number_integer',
            'wrappers' => [
              'field_wrapper_tag' => '',
              'field_wrapper_classes' => '',
              'field_tag' => '',
              'field_classes' => '',
              'label_tag' => '',
              'label_classes' => '',
            ],
            'formatter_settings' => [
              'field_label' => 'Carbohydrate content',
              'prefix_suffix' => TRUE,
            ],
            'scale' => 0,
            'thousand_separator' => ',',
            'prefix_suffix' => FALSE,
            'decimal_separator' => '.',
            'key_label' => 'label',
          ],
          'cholesterol_content' => [
            'weight' => 3,
            'format_type' => 'number_integer',
            'wrappers' => [
              'field_wrapper_tag' => '',
              'field_wrapper_classes' => '',
              'field_tag' => '',
              'field_classes' => '',
              'label_tag' => '',
              'label_classes' => '',
            ],
            'formatter_settings' => [
              'field_label' => 'Cholesterol',
              'prefix_suffix' => TRUE,
            ],
            'scale' => 0,
            'thousand_separator' => ',',
            'prefix_suffix' => FALSE,
            'decimal_separator' => '.',
            'key_label' => 'label',
          ],
          'fat_content' => [
            'weight' => 4,
            'format_type' => 'number_integer',
            'wrappers' => [
              'field_wrapper_tag' => '',
              'field_wrapper_classes' => '',
              'field_tag' => '',
              'field_classes' => '',
              'label_tag' => '',
              'label_classes' => '',
            ],
            'formatter_settings' => [
              'field_label' => 'Fat',
              'prefix_suffix' => TRUE,
            ],
            'scale' => 0,
            'thousand_separator' => ',',
            'prefix_suffix' => FALSE,
            'decimal_separator' => '.',
            'key_label' => 'label',
          ],
          'fiber_content' => [
            'weight' => 5,
            'format_type' => 'number_integer',
            'wrappers' => [
              'field_wrapper_tag' => '',
              'field_wrapper_classes' => '',
              'field_tag' => '',
              'field_classes' => '',
              'label_tag' => '',
              'label_classes' => '',
            ],
            'formatter_settings' => [
              'field_label' => 'Fiber',
              'prefix_suffix' => TRUE,
            ],
            'scale' => 0,
            'thousand_separator' => ',',
            'prefix_suffix' => FALSE,
            'decimal_separator' => '.',
            'key_label' => 'label',
          ],
          'protein_content' => [
            'weight' => 6,
            'format_type' => 'number_integer',
            'wrappers' => [
              'field_wrapper_tag' => '',
              'field_wrapper_classes' => '',
              'field_tag' => '',
              'field_classes' => '',
              'label_tag' => '',
              'label_classes' => '',
            ],
            'formatter_settings' => [
              'field_label' => 'Protein',
              'prefix_suffix' => TRUE,
            ],
            'scale' => 0,
            'thousand_separator' => ',',
            'prefix_suffix' => FALSE,
            'decimal_separator' => '.',
            'key_label' => 'label',
          ],
          'saturated_fat_content' => [
            'weight' => 7,
            'format_type' => 'number_integer',
            'wrappers' => [
              'field_wrapper_tag' => '',
              'field_wrapper_classes' => '',
              'field_tag' => '',
              'field_classes' => '',
              'label_tag' => '',
              'label_classes' => '',
            ],
            'formatter_settings' => [
              'field_label' => 'Saturated Fat',
              'prefix_suffix' => TRUE,
            ],
            'scale' => 0,
            'thousand_separator' => ',',
            'prefix_suffix' => FALSE,
            'decimal_separator' => '.',
            'key_label' => 'label',
          ],
          'sodium_content' => [
            'weight' => 8,
            'format_type' => 'number_integer',
            'wrappers' => [
              'field_wrapper_tag' => '',
              'field_wrapper_classes' => '',
              'field_tag' => '',
              'field_classes' => '',
              'label_tag' => '',
              'label_classes' => '',
            ],
            'formatter_settings' => [
              'field_label' => 'Sodium',
              'prefix_suffix' => TRUE,
            ],
            'scale' => 0,
            'thousand_separator' => ',',
            'prefix_suffix' => FALSE,
            'decimal_separator' => '.',
            'key_label' => 'label',
          ],
          'sugar_content' => [
            'weight' => 9,
            'format_type' => 'number_integer',
            'wrappers' => [
              'field_wrapper_tag' => '',
              'field_wrapper_classes' => '',
              'field_tag' => '',
              'field_classes' => '',
              'label_tag' => '',
              'label_classes' => '',
            ],
            'formatter_settings' => [
              'field_label' => 'Sugar',
              'prefix_suffix' => TRUE,
            ],
            'scale' => 0,
            'thousand_separator' => ',',
            'prefix_suffix' => FALSE,
            'decimal_separator' => '.',
            'key_label' => 'label',
          ],
          'trans_fat_content' => [
            'weight' => 10,
            'format_type' => 'number_integer',
            'wrappers' => [
              'field_wrapper_tag' => '',
              'field_wrapper_classes' => '',
              'field_tag' => '',
              'field_classes' => '',
              'label_tag' => '',
              'label_classes' => '',
            ],
            'formatter_settings' => [
              'field_label' => 'Trans fat',
              'prefix_suffix' => TRUE,
            ],
            'scale' => 0,
            'thousand_separator' => ',',
            'prefix_suffix' => FALSE,
            'decimal_separator' => '.',
            'key_label' => 'label',
          ],
          'unsaturated_fat_content' => [
            'weight' => 11,
            'format_type' => 'number_integer',
            'wrappers' => [
              'field_wrapper_tag' => '',
              'field_wrapper_classes' => '',
              'field_tag' => '',
              'field_classes' => '',
              'label_tag' => '',
              'label_classes' => '',
            ],
            'formatter_settings' => [
              'field_label' => 'Unsaturated fat',
              'prefix_suffix' => TRUE,
            ],
            'scale' => 0,
            'thousand_separator' => ',',
            'prefix_suffix' => FALSE,
            'decimal_separator' => '.',
            'key_label' => 'label',
          ],
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
      'label' => 'Question',
      'check_empty' => FALSE,
      'required' => FALSE,
      'translatable' => FALSE,
      'description' => 'The name of the item.',
      'description_display' => 'after',
      'formatted' => TRUE,
      'default_format' => 'basic_html',
      'format' => [
        'guidelines' => FALSE,
        'help' => FALSE,
      ],
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
      'label' => 'Frequency',
      'check_empty' => FALSE,
      'required' => FALSE,
      'translatable' => FALSE,
      'description' => "How often the dose is taken, e.g. 'daily'.",
      'description_display' => 'after',
      'prefix' => '',
      'suffix' => '',
      'allowed_values' => [
        [
          'key' => 'daily',
          'label' => 'Daily',
        ],
        [
          'key' => '2_times_a_day',
          'label' => '2 times a day',
        ],
        [
          'key' => '3_times_a_day',
          'label' => '3 times a day',
        ],
        [
          'key' => '4_times_a_day',
          'label' => '4 times a day',
        ],
        [
          'key' => '5_times_a_day',
          'label' => '5 times a day',
        ],
        [
          'key' => 'every_3_hours',
          'label' => 'Every 3 hours',
        ],
        [
          'key' => 'every_6_hours',
          'label' => 'Every 6 hours',
        ],
        [
          'key' => 'every_8_hours',
          'label' => 'Every 8 hours',
        ],
        [
          'key' => 'every_12_hours',
          'label' => 'Every 12 hours',
        ],
        [
          'key' => 'every_24_hours',
          'label' => 'Every 24 hours',
        ],
        [
          'key' => 'bedtime',
          'label' => 'Bedtime',
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
        'label' => 'Integer',
        'check_empty' => FALSE,
        'required' => FALSE,
        'translatable' => FALSE,
        'description' => '',
        'description_display' => 'after',
        'prefix' => '',
        'suffix' => '',
        'allowed_values' => [],
        'min' => 99,
        'max' => 999,
      ],
      'custom_string' => [
        'label' => 'Custom string',
        'check_empty' => FALSE,
        'required' => TRUE,
        'translatable' => FALSE,
        'description' => 'Custom description',
        'description_display' => 'after',
        'prefix' => 'Custom prefix',
        'suffix' => 'Custom suffix',
        'allowed_values' => [],
      ],
      'allowed_values' => [
        'label' => 'Allowed_values',
        'check_empty' => FALSE,
        'required' => FALSE,
        'translatable' => FALSE,
        'description' => '',
        'description_display' => 'after',
        'prefix' => '',
        'suffix' => '',
        'allowed_values' => [
          [
            'key' => 'one',
            'label' => 'One',
          ],
          [
            'key' => 'two',
            'label' => 'Two',
          ],
          [
            'key' => 'three',
            'label' => 'Three',
          ],
        ],
      ],
      'entity_reference' => [
        'label' => 'Entity_reference',
        'check_empty' => FALSE,
        'required' => FALSE,
        'translatable' => FALSE,
        'description' => '',
        'description_display' => 'after',
        'handler' => 'default:media',
        'handler_settings' => [
          'target_bundles' => [
            'image' => 'image',
          ],
        ],
      ],
      'link' => [
        'label' => 'Link',
        'check_empty' => FALSE,
        'required' => FALSE,
        'translatable' => FALSE,
        'description' => '',
        'description_display' => 'after',
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
