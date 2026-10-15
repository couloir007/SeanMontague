# Configuration Views

This module allows site builders to create Views that are lists of
configuration entities. For example, a site builder can create lists of Roles, Menus,
Image Styles, Content Types, Views, and so on.

These lists can then be filtered and sorted, and turned into pages, exported
in other formats, used as targets for Entity Reference fields, and so on, using the power of Views.

The module also replaces some administrative list pages for configuration
entities (for example, content types and view modes) with Views that can be
customized.

## Detailed features

- The following default pages are installed as Views ready for customization:
  1. Comment types - admin/structure/comment
  2. Contact forms* - admin/structure/contact
  3. Content types - admin/structure/types
  4. Custom block library - admin/structure/block/block-content/types
  5. Date and time formats - admin/config/regional/date-time
  6. Form modes* - admin/structure/display-modes/form
  7. Menus - admin/structure/menu
  8. Shortcuts - admin/config/user-interface/shortcut
  9. Image styles - admin/config/media/image-styles
  10. Taxonomy - admin/structure/taxonomy
  11. Text formats* - admin/config/content/formats
  12. User roles* - admin/people/roles
  13. View modes* - admin/structure/display-modes/view
  14. Views* - admin/structure/views (WHAT!!!)
- Create additional Views of configuration entities through the Views UI.

* These Views are disabled by default. Enable them via Admin > Structure >
  Views to have them take over the list and start customizing them.

## Requirements and installation

The 2.1.x branch supports Drupal 10.1 or later in Drupal 10, and Drupal 11.
It requires Drupal core's Views module. Enable Views UI to create or edit Views.

1. Install the module with Composer:

   ```sh
   composer require drupal/config_views:^2.1
   ```

2. Enable **Configuration Entity View** at **Extend** (`/admin/modules`).
3. Enable **Views UI** if you need to create or customize a View.
4. Visit **Structure > Views** (`/admin/structure/views`) to inspect the installed
   Views. Enabling the module installs the default Views listed above; some are
   enabled and replace core administration pages immediately.

To restore a core administration listing, disable the corresponding View in
**Structure > Views**, then rebuild caches. Export configuration after making
changes you want to deploy. Module updates do not automatically replace an
existing site's customized Views with newer default configuration.

## Create a configuration View

1. Select **Add view** at **Structure > Views**.
2. Choose a configuration entity type from the **Configuration** group in the
   **Show** selector, such as roles, menus, or image styles.
3. Add the fields, filters, and sort criteria available for that entity type.
4. Add a page or block display, configure its access restrictions, and save.

For example, a menu View can list menu labels and machine names, while a role
View can filter out roles you do not want to show. Available fields depend on
that configuration entity's schema, rather than on content entity field storage.

Configuration can contain administrative information. Choose appropriate access
permissions for each display before making it available to other users.

## Use a View for entity references

For an entity reference field whose target is a configuration entity:

1. Create a View of the same configuration entity type.
2. Add an **Entity Reference** display and configure its search fields.
3. In the field's reference settings, choose **Views: Filter by a Configuration
   View** and select that View's Entity Reference display.
4. Save the field settings and check the available choices in the content form.

Use the Configuration View selection handler, rather than core's content-entity
Views selection handler. Config Views uses the configuration entity's label for
reference choices. The View's filters determine which entities are selectable.

## Information for module developers

Config Views discovers configuration entity types and reads their configuration
schema to build Views data. Define the entity's configuration prefix, ID and
label keys, exported properties, and matching schema in your module's
`config/schema/*.schema.yml` files. Labels in the schema identify the available
properties in the Views UI.

The 2.1.x implementation builds data in `config_views_views_data()` and processes
schema mappings in `_views_views_config_process_schema()`. It executes listings
with the `views_config_entity_query` plugin through configuration entity storage,
rather than querying a SQL table. The loaded entities' exported values supply
result-row properties.

This branch does not provide the proposed per-entity Views data handler API.
Do not assume that defining a SQL relationship or SQL argument handler will
work with the configuration entity query plugin.

## Limitations and troubleshooting

- Only schema properties handled by this branch are exposed. Sequence values,
  third-party settings, relationships between content and configuration entities,
  and contextual filters have outstanding work in the issue queue.
- Some default Views differ from the core administration pages they replace.
  Several are disabled by default, and field administration pages are not supplied.
- If a configuration entity type or property is missing, check that its provider
  module is enabled and that the entity has matching configuration schema.
  Rebuild caches after changing schema or enabling a provider module.
- If a View has no rows, confirm that entities of that type actually exist in
  active configuration, then inspect the View's filters and pager. Enabling REST
  does not itself create a saved REST resource configuration for every entity.
- If an Entity Reference display is unavailable, check that it belongs to a View
  of the field's target configuration entity type and that the View is enabled.
- To report a problem, use the [Config Views issue queue](https://git.drupalcode.org/project/config_views/-/issues).
  Include Drupal and module versions, the affected entity type, reproduction
  steps, the View's exported YAML, and relevant errors. Remove credentials and
  private site configuration from attachments.

## Supporting organizations:

[Solathat](https://www.drupal.org/solathat) supports ongoing maintenance.

[Nerdery](https://www.drupal.org/nerdery) supports ongoing maintenance.

[Soapbox](https://www.drupal.org/soapbox) supports ongoing maintenance.
