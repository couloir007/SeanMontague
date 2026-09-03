# Creating a Custom field as a BaseFieldDefinition

As with all field types, it's possible to create a custom field as a 
[BaseFieldDefinition](https://api.drupal.org/api/drupal/11.x/search/BaseFieldDefinition){target="_blank"}
on an entity. The following example is for a custom field with two subfields of
type [string](../plugins/type/string.md), **First Name** (`first_name`) and 
**Last Name** (`last_name`):

```php
// The field type is 'custom'.
$fields['target_types'] = BaseFieldDefinition::create('custom')
  ->setLabel(t('Student Name'))
  ->setDescription(t('The name of the student'))
  ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
  ->setDisplayConfigurable('view', TRUE)
  ->setDisplayConfigurable('form', TRUE)
  // Columns define the field type, in this example first_name and last_name,
  // both of which are strings.
  ->setSetting('columns', [
    'first_name' => [
      'name' => 'first_name',
      'type' => 'string',
      'length' => 128,
    ],
    'last_name' => [
      'name' => 'last_name',
      'type' => 'string',
      'length' => 128,
    ],
  ])
  ->setSetting('field_settings', [
    'first_name' => [
      'label' => 'First Name',
      'check_empty' => FALSE,
      'required' => TRUE,
      'translatable' => FALSE,
      'description' => 'Enter the first name of the student',
      'description_display' => 'after',
      'prefix' => '',
      'suffix' => '',
    ],
    'last_name' => [
      'label' => 'Last Name',
      'check_empty' => FALSE,
      'required' => TRUE,
      'translatable' => FALSE,
      'description' => 'Enter the last name of the student',
      'description_display' => 'after',
      'prefix' => '',
      'suffix' => '',
    ],
  ])

  // The form display options define how the custom field, as a whole,
  // with all elements, will be displayed in the form.
  ->setDisplayOptions('form', [
    'type' => 'custom_flex',
    'region' => 'content',
    'settings' => [
      'wrapper' => 'details',
      'label_value' => '',
      'label_limit' => 60,
      'label_prefix' => 'Item',
      'auto_collapse' => FALSE,
      'open' => TRUE,
      'fields' => [
        'first_name' => [
          'weight' => -10
          'type' => 'text'
          'label' => ''
          'size' => 60
          'placeholder' => ''
          'maxlength' => 100
          'maxlength_js' => FALSE,
        ],
        'last_name' => [
          'weight' => -9
          'type' => 'text'
          'label' => ''
          'size' => 60
          'placeholder' => ''
          'maxlength' => 100
          'maxlength_js' => FALSE,
        ],
      ],
     'columns' => [
        'first_name' => '6',
        'last_name' => '6',
      ],
    ],
  ])

  // The view display options define how the custom field, as a whole,
  // with all elements, will be displayed on the front end.
  ->setDisplayOptions('view', [
    'type' => 'custom_formatter',
    'label' => 'above',
    'settings' => [
      'fields' => [],
    ],
    'third_party_settings' => [],
    'region' => 'content',
  ]);
```

The values to use in the settings can be determined as follows:

1. Add the field to your entity in the field ui, on the admin pages. Configure
as necessary.
2. Export the configuration to the file system.
3. Examine the exported configuration for the newly created field, and review:
    - `field.storage.[ENTITY_TYPE].[FIELD_NAME].yml`
    - `field.field.[ENTITY_TYPE]..[BUNDLE_TYPE].[FIELD_NAME].yml` 
    - `core.entity_form_display.[ENTITY_TYPE].[BUNDLE_TYPE].default.yml`
    - `core.entity_view_display.[ENTITY_TYPE].[BUNDLE_TYPE].default.yml`
4. Use the relevant values from these files to populate the **BaseFieldDefinition**
in the example above.
5. The main keys you'll need to focus on are `columns` and `field_settings`.