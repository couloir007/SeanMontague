Table of contents
-----------------

* Introduction
* Features
* Requirements
* Configuration


Introduction
------------

The **Schema.org Blueprints Devel module** provides development tools for the
Schema.org Blueprints module.


Features
--------

- Adds the ability to generate test content when adding a node.
- Defines test values used by an entity's Schema.org properties when generating
  example content using the devel_generate.module.
- Provides `drush schemadotorg:generate-features` command which generates
  MODULE.features.yml for schemadotorg* modules.


Requirements
------------

**[Devel](https://www.drupal.org/project/devel)**      
Various blocks, pages, and functions for developers.


Configuration
-------------

- Configure 'Schema.org Blueprints Devel' permission.  
  (/admin/people/permissions/module/schemadotorg_devel)
- Go to the Schema.org general configuration page.  
  (/admin/config/schemadotorg/settings/general#edit-schemadotorg-devel)
- Go to the 'Devel settings' details.
- Enter Schema.org property values to be used when generating content using
  the Devel Generate module.
