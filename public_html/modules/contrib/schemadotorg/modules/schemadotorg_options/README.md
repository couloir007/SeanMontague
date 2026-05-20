Table of contents
-----------------

* Introduction
* Features
* Configuration
* Notes


Introduction
------------

The **Schema.org Blueprints Options module** manages allowed values 
for option based fields.


Features
--------

- Defines allowed values for Schema.org properties.
- Removes allowed values suffix for Schema.org properties. 
  (i.e., Removes 'Diet' suffix from https://schema.org/RestrictedDiet allowed values)
- Defines range includes https://schema.org/Enumeration for Schema.org properties.
- Convert https://schema.org/Enumeration into allowed values.
- Provide aliases for allowed (enumeration) values. 
  (i.e., MixedEventAttendanceMode is aliased as 'mixed').
- Convert Enumeration value to snake case 
  (i.e., RestrictedDiet converts to restricted_diet).
- Auto assigns allowed value function for Schema.properties range includes.


Configuration
-------------

- Go to the Schema.org properties configuration page.  
  (/admin/config/schemadotorg/settings/properties#edit-schemadotorg-options)
- Go to the 'Options settings' details.
- Enter Schema.org properties with allowed values.
- Enter the Schema.org properties that should return the text for an allowed value in the Schema.org JSON-LD.
- Enter Schema.org Enumeration followed by its aliases for the Schema.org JSON-LD. This allows for more readable values (i.e. snake_case values) for Enumeration values.
- Enter URIs to be used in the Schema.org JSON-LD for an allowed value.
- Use snake case for allowed (enumeration) values


Notes
-----

Allow allowed values in `schemadotorg_options.settings.yml` use snake case for
keys, because this is the default format for Drupal's allowed values UI.

Sources for allowed values in [schemadotorg_options.settings.yml](config%2Finstall%2Fschemadotorg_options.settings.yml)

- [dosageForm](https://schema.org/dosageForm)
  @see https://www.fda.gov/industry/structured-product-labeling-resources/dosage-forms

- [nationality](https://schema.org/nationality)
  @see https://gist.github.com/didats/8154290
