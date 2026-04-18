Table of contents
-----------------

* Introduction
* Features
* Installation
* Sub-modules
* Requirement
* Known Issues


Introduction
------------

The **Schema.org Blueprints Demo** module provides an opinionated demo of
Schema.org Blueprints built on top of Drupal’s standard profile.

This module provides a composer.libraries.json file that allows sites to
download and install contributed modules with patches which are used to set up
a full demo of the Schema.org Blueprints module.

**THIS MODULE SHOULD ONLY BE INSTALLED ON A PLAIN VANILLA STANDARD INSTANCE OF DRUPAL.**


Features
--------

For more information about the contributed modules included in the demo see the 
[Schema.org Blueprints Architecture Decisions Records (ADRs)](https://git.drupalcode.org/project/schemadotorg/-/blob/1.0.x/docs/DECISIONS.md#8000-contributed-modules-and-themes)


Installation
------------

Steps

- Create and install Drupal using Composer
- Replace the new project's composer.json with the contents 
  of [install/composer.example.json](install/composer.example.json)
- Run `composer update` 
  - _Ignore initial errors and run it 3 times!!!_
- Run `drush site-install schemadotorg_demo_profile`
- Run `drush install schemadotorg_demo`

Summary of changes included in [install/composer.example.json](install/composer.example.json)
 
- `repositories`: Defines demo profile as a repository 
- `minimum-stability`: Set to dev to allow any module to be required
- `require`: Adds merge and patch plugins with the demo module and profile
- `extra.merge-plugin`: Configure merges composer.libraries
- `extra.installer-paths`: Adjusts demo module installation path

View a diff of the changes in [install/composer.example.json](install/composer.example.json)

- [install/composer.example.json.txt](install/composer.example.json.txt)
- [install/composer.example.json.html](install/composer.example.json.html)

References

- [Use Composer to install Drupal and manage dependencies](https://www.drupal.org/docs/develop/using-composer/manage-dependencies).


Sub-modules
-----------

Below are sub-modules which enable different functionality and feature sets.

- **[Schema.org Blueprints Demo Standard](https://git.drupalcode.org/project/schemadotorg_demo/-/tree/1.0.x/modules/schemadotorg_demo_standard)**  
  Provides an opinionated demo of Schema.org Blueprints built on top of Drupal's standard profile.

- **[Schema.org Blueprints Demo Experimental](https://git.drupalcode.org/project/schemadotorg_demo/-/tree/1.0.x/modules/schemadotorg_demo_experimental)**  
  Enables and configures experimental modules for the Schema.org Blueprints Demo.

- **[Schema.org Blueprints Layout Paragraphs Demo](https://git.drupalcode.org/project/schemadotorg_demo/-/tree/1.0.x/modules/schemadotorg_demo_layout_paragraphs)**  
  Enables and configures the Layout Paragraphs and Mercury Editor module for the Schema.org Blueprints Demo.

- **[Schema.org Blueprints Demo Admin](https://git.drupalcode.org/project/schemadotorg_demo/-/tree/1.0.x/modules/schemadotorg_demo_admin)**  
  Enables and configures the Gin admin theme and provides admin UI enhancements.

- **[Schema.org Blueprints Demo Translation](https://git.drupalcode.org/project/schemadotorg_demo/-/tree/1.0.x/modules/schemadotorg_demo_translation)**  
  Provides translation support for the Schema.org Blueprints demo.

- **[Schema.org Blueprints Demo API](https://git.drupalcode.org/project/schemadotorg_demo/-/tree/1.0.x/modules/schemadotorg_demo_admin)**  
  Enables and configures API support for the Schema.org Blueprints Demo.

- **[Schema.org Blueprints Demo Headless](https://git.drupalcode.org/project/schemadotorg_demo/-/tree/1.0.x/modules/schemadotorg_demo_headless)**  
  Provides a demo of the Schema.org Blueprints module used a headless backend.


Known Issues
------------

- [Issue #3316265: Field description is not displayed for text format (i.e. body)](https://www.drupal.org/project/gin/issues/3316265)
