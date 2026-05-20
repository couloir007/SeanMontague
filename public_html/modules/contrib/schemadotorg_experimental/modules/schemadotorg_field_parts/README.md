Table of contents
-----------------

* Introduction
* Features
* Configuration


Introduction
------------

The **Schema.org Blueprints Field Parts module** allows Schema.org 
title/name properties to include a prefix and suffix.

**IMPORTANT: The rendering of the field prefix and suffix must be handled 
using a custom module or theme.**


Features
--------

- Adds prefix and suffix fields to Schema.org properties.
- Adds prefix and suffix field values to JSON-LD.
- Exposes prefix and suffix fields to JSON:API.


Configuration
-------------

- Go to the Schema.org properties configuration page.  
  (/admin/config/schemadotorg/settings/properties#edit-schemadotorg-allowed-formats)
- Go to the 'Field parts settings' details.
- Enter the Schema.org properties that should support field prefixes.
- Enter the Schema.org properties that should support field suffixes.
- Enter the field prefix delimiter.
- Enter the field suffix delimiter.


Notes
-----

Hide prefix and suffix from view displays using the below code.

```

/**
 * Implements hook_schemadotorg_property_field_alter().
 */
function schemadotorg_field_parts_schemadotorg_property_field_alter(
  string $schema_type,
  string $schema_property,
  array &$field_storage_values,
  array &$field_values,
  ?string &$widget_id,
  array &$widget_settings,
  ?string &$formatter_id,
  array &$formatter_settings,
): void {
  // Hide the prefix and suffix component from the view display.
  if (str_ends_with($schema_property, ':' . SchemaDotOrgFieldPartsManagerInterface::PREFIX)
    || str_ends_with($schema_property, ':' .SchemaDotOrgFieldPartsManagerInterface::PREFIX)) {
    $formatter_id = SchemaDotOrgEntityDisplayBuilderInterface::COMPONENT_HIDDEN;
  }
}

```
