# AI generated.

# Flags Module - Agent Documentation

## Overview

The Flags module maps country codes and language codes to flag icons
displayed via CSS sprites. It provides a service-based mapping API and
integrates with field formatters and widgets through submodules.

## Architecture

### Services (`flags.services.yml`)

| Service | Class | Dependencies |
|---------|-------|--------------|
| `flags.mapping.country` | `Drupal\flags\Mapping\Country` | `config.factory` |
| `flags.mapping.language` | `Drupal\flags\Mapping\Language` | `config.factory` |
| `flags.language_helper` | `Drupal\flags\FullLanguageManager` | `language_manager`, `config.factory` |
| `flags.manager` | `Drupal\flags\Flags\FlagsManager` | `module_handler` |

### Mapping API

The core mapping logic lives in `BaseMapping` (abstract):

```
FlagMappingInterface
  └── BaseMapping (abstract)
        ├── Country (config key: flags.country_flag_mapping)
        └── Language (config key: flags.language_flag_mapping)
```

Key methods:
- `map($value)` - Maps a code to a flag code (lowercase). Checks config
  entities for overrides, falls back to the input code.
- `getOptionAttributes(array $options)` - Returns `Attribute` objects with
  CSS classes for select widget integration.
- `getExtraClasses()` - Returns extra CSS classes (`country-flag` or
  `flag-lang`).

### Config Entities

| Entity type | Class | Config prefix |
|-------------|-------|---------------|
| `country_flag_mapping` | `Drupal\flags\Entity\CountryFlagMapping` | `flags.country_flag_mapping` |
| `language_flag_mapping` | `Drupal\flags\Entity\LanguageFlagMapping` | `flags.language_flag_mapping` |

Both extend `FlagMapping` (abstract) which extends `ConfigEntityBase`.
Entity key: `source`. Config exports: `source`, `flag`.

### Flag Rendering

The `flags` theme hook (`flags.module`) renders flag icons:
- Template: `templates/flags.html.twig`
- Variables: `code`, `source` (determines which mapping service to use),
  `tag`, `attributes`
- `template_preprocess_flags()` resolves the mapping service dynamically
  via `flags.mapping.{source}`.

### FlagsManager

`FlagsManager::getStandardList()` provides 250+ flag codes with
translatable names. `getList()` adds alter hook support
(`hook_flags_alter`).

## Submodules

### flags_country
- **Depends on:** flags, country
- **Formatter:** `CountryFlagFormatter` (id: `country_flag`)
- **Widgets:** `CountrySelectMenuWidget`, `CountryFlagAutocompleteWidget`
- **Controller:** `CountryFlagAutocompleteController`
- **Facets processor:** `CountryFlag`

### flags_language
- **Depends on:** flags, drupal:language
- **Formatter:** `LanguageFlagFormatter` (id: `language_flag`)
- **Widget:** `LanguageSelectMenuWidget`
- **Hooks:** Alters language switch links and language block

### flags_languagefield
- **Depends on:** flags, languagefield
- **Formatter:** `LanguagefieldFlagFormatter` (id: `languagefield_flag`)
- **Widget:** `LanguagefieldSelectMenuWidget`

### flags_ui
- **Depends on:** flags
- **Provides:** Admin forms for CRUD on country/language flag mappings
- **Routes:** 11 routes under `/admin/config/regional/flags/`
- **Permission:** `administer flag mapping`

## Key File Paths

```
flags/
├── src/
│   ├── Entity/
│   │   ├── FlagMapping.php              # Abstract config entity base
│   │   ├── CountryFlagMapping.php       # Country mapping entity
│   │   ├── LanguageFlagMapping.php      # Language mapping entity
│   │   ├── FlagMappingListBuilder.php   # Country list builder
│   │   └── LanguageFlagMappingListBuilder.php
│   ├── Flags/
│   │   ├── FlagsManager.php             # Flag list provider (250+ flags)
│   │   └── FlagsManagerInterface.php
│   ├── Mapping/
│   │   ├── FlagMappingInterface.php     # Mapping service interface
│   │   ├── BaseMapping.php              # Abstract mapping implementation
│   │   ├── Country.php                  # Country code mapper
│   │   └── Language.php                 # Language code mapper
│   ├── FullLanguageManager.php          # Combines predefined + configured languages
│   └── Security/
│       └── FlagMappingAccessController.php
├── config/schema/flags.schema.yml       # Config entity schema
├── css/flag-icons.css                   # CSS sprites for flag display
├── flags.module                         # Theme hook + preprocess
├── flags.services.yml                   # Service definitions
├── flags.permissions.yml                # Permissions
├── flags.libraries.yml                  # CSS library
└── tests/
    └── src/
        ├── Unit/                        # Unit tests
        └── Kernel/                      # Kernel tests
```

## Testing

```bash
# Run all flags tests
ddev phpunit web/modules/contrib/flags

# Run only unit tests
ddev phpunit web/modules/contrib/flags/tests/src/Unit/

# Run only kernel tests
ddev phpunit web/modules/contrib/flags/tests/src/Kernel/

# Coding standards
ddev phpcs web/modules/contrib/flags

# Static analysis
ddev phpstan web/modules/contrib/flags
```

## Contributing

- Issue queue: https://www.drupal.org/project/issues/flags
- Repository: https://git.drupalcode.org/project/flags
