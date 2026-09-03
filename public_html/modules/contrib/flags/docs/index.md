# Flags

Provides flag icons for countries and languages, with a simple API
for mapping country codes and language codes to their corresponding
flag icons using CSS sprites.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/flags).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/flags).


## Table of contents

- Requirements
- Recommended modules
- Installation
- Configuration
- Submodules
- Maintainers


## Requirements

This module requires no modules outside of Drupal core for basic
functionality. However, individual submodules have their own
requirements (see Submodules below).


## Recommended modules

- [Select Icons](https://www.drupal.org/project/select_icons) -
  Display flag icons in form select widgets.
- [Country](https://www.drupal.org/project/country) - Provides a
  country field type. Required by the Flags Country submodule.
- [Language Field](https://www.drupal.org/project/languagefield) -
  Provides a language field type. Required by the Flags Language
  Field submodule.


## Installation

Install as you would normally install a contributed Drupal module.
Visit
https://www.drupal.org/docs/extending-drupal/installing-modules
for further information.


## Configuration

1. After installing, enable the main Flags module and one or more
   submodules depending on your needs.
1. Navigate to Administration > Structure > Content types > [type]
   > Manage display to select a flag formatter for country or
   language fields.
1. Optionally enable the Flags UI submodule to customize flag
   mappings at Administration > Configuration > Regional and
   language > Flags.
1. The "Administer flag mapping" permission controls access to the
   flag mapping administration pages.


## Submodules

- **Flags Country** (`flags_country`) - Integrates flags with the
  [Country](https://www.drupal.org/project/country) module. Provides
  a "Country with flag" field formatter and a country select widget
  with flag icons (requires Select Icons).

- **Flags Language** (`flags_language`) - Integrates flags with
  Drupal core's language field. Provides a "Language with flag" field
  formatter and a language select widget with flag icons. Also adds
  flags to the language switcher block.

- **Flags Language Field** (`flags_languagefield`) - Integrates flags
  with the [Language Field](https://www.drupal.org/project/languagefield)
  module. Provides a "Language with flag" field formatter and widget.

- **Flags UI** (`flags_ui`) - Provides an administration interface
  for customizing country-to-flag and language-to-flag mappings.


## Maintainers

- SiliconMind - https://www.drupal.org/u/siliconmind
- vlad.dancer - https://www.drupal.org/u/vladdancer
- matsbla - https://www.drupal.org/u/matsbla
- batigolix - https://www.drupal.org/u/batigolix

Sponsored by:

- [Globalbility](https://www.drupal.org/globalbility)
- [Finalist](https://www.drupal.org/finalist)
