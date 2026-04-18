Table of contents
-----------------

* Introduction
* Features
* Requirements


Introduction
------------

The **Schema.org Blueprint Demo Admin module** enables and configure the
Gin admin theme and provides admin UI enhancements.


Features
--------

Gin admin theme

- Enables the Gin admin theme.
- Enables the Gin related modules.
- Configures the Gin admin theme.
- Uninstalls unused themes.

CSS/JS

- Attaches CSS and JavaScript, that fixes minor UI issues with minor
  UX enhancements.
- Adds custom toolbar icons.

Schema.org

- Fixes relationships filter.
  @see /admin/reports/schemadotorg/docs/relationships/targets

Navigation

- Removes unneeded second level menu items for navigation.
- Alters secondary toolbar when navigation module is enabled.

Toolbar

- Tweaks the Environment Indicator module's toolbar weight.
- Removes 'Devel' icon and link from toolbar.
- Changes admin toolbar search placeholder text.
- Adds missing masquerade icon class attribute.  
  @see https://www.drupal.org/project/masquerade/issues/3325299
- Never display the default environment indicator bar at the top of the page.

Node edit

- Close the 'URL alias' details widget in the sidebar.
- Hides 'Promoted to front page' and 'Sticky at top of lists' which are never
  used and simplifies the demo.
- Allows the state of details widget to be tracked.

Contrib

- **Admin Dialogs:** Improve default configuration.
- **Environment Indicator:** Tweak the Environment Indicator module's toolbar weights.
- **Masquerade:** Disable the unmasquerade menu link.
- **Meta Tags:** Limits meta tags and robots directives.
- **Content Moderation Sidebar:** Removes secondary actions.
- **Entity Embed:** Disables captioning and align attributes.


Requirements
------------

**[Gin](https://www.drupal.org/project/gin)**  
Admin theme with a strong focus on improving the Editorial Experience

