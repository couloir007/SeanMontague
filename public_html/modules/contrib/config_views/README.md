# Configuration Views

This module provides allows site builders to create Views that are lists of
Configuration. For example, a site builder can create lists of Roles, Menus,
Image Styles, Content Types, Views, and so on.

These lists can then be filtered and sorted, and turned into pages, exported
in other formats, used as targets for Entity Reference fields, and so on - ie,
leveraging the power of Views.

The module comes also replaces admin list/manage pages of configuration entities
(e.g content types, view modes, etc) with views so they can be subsequent
customized to suit your purposes.

## Detailed features

- The following default pages are installed as Views ready for customization:
  1. Comment types - admin/structure/comment
  2. Contact forms* - admin/structure/contact
  3. Content types - admin/structure/types
  4. Custom block library - admin/structure/block/block-content/types
  5. Date and time formats* - admin/config/regional/date-time
  6. Form modes* - admin/structure/display-modes/form
  7. Menus - admin/structure/menu
  8. Shortcuts - admin/config/user-interface/shortcut
  9. Image styles - admin/config/media/image-styles
  10. Taxonomy - admin/structure/taxonomy
  11. Text formats* - admin/config/content/formats
  12. User roles* - admin/people/roles
  13. View modes* - admin/structure/display-modes/view
  14. Views* - admin/structure/views (WHAT!!!)
- Then created your own Views of anything else.

* *- These Views are disabled by default. Enable them via Admin > Structure >
  Views to have them take over the list and start customizing them. *

## Limitations / Known issues

1. Provides only limited fields (name, description etc) - Getting entity 
   specific fields is Work in progress.
2. Few of the default views are not match with the core design - By default 
   disabled.
3. Fields pages are not yet done.

## Supporting organizations:

[Solathat](https://www.drupal.org/solathat) supports ongoing maintenance.

[Nerdery](https://www.drupal.org/nerdery) supports ongoing maintenance.

[Soapbox](https://www.drupal.org/soapbox) supports ongoing maintenance.
