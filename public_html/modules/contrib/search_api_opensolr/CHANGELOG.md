# Changelog

All notable changes to this project from version 2.2.4 onwards will be documented in this file.

This changelog is automatically generated from [Conventional Commits](https://www.conventionalcommits.org/) using [git-cliff](https://git-cliff.org/).
This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

For releases prior to 2.2.4, please see the [release history on Drupal.org](https://www.drupal.org/project/search_api_opensolr/releases).

---
## [2.4.0](https://www.drupal.org/project/search_api_opensolr/releases/2.4.0) - 2026-08-18

### 🚀 Features

- Replace the Get started registration wizard with a guided setup page

### 🎨 Styling

- Property order in get-started.css (stylelint)
## [2.3.0](https://www.drupal.org/project/search_api_opensolr/releases/2.3.0) - 2026-08-18

### 🐛 Bug Fixes

- [#3602784](https://dgo.to/3602784) Drupal 12 readiness — OOP hook classes, hook_entity_operation cacheability param, phpstan fixes
- [#3576419](https://dgo.to/3576419) Key module integration is now an explicit opt-in instead of auto-detection
- Never let a failed opensolr API call crash the admin UI

### 🎨 Styling

- Wrap a comment line to 80 characters (phpcs)
## [2.2.4](https://www.drupal.org/project/search_api_opensolr/releases/2.2.4) - 2026-03-02

### 🐛 Bug Fixes

- [#3533991](https://dgo.to/3533991) Ensure index list is always an array
- [#3533996](https://dgo.to/3533996) Resolve "Call to a member function isSuccess() on null"
- [#3576417](https://dgo.to/3576417) Use immutable config for reading OpenSolrConfig values

### ⚙️ Miscellaneous Tasks

- Add gitlab-ci and phpstan.neon file
- Fix remaining gitlab-ci errors
- Introduce automated changelog generation with git-cliff and conventional commits
## [8.x-1.0-alpha1](https://www.drupal.org/project/search_api_opensolr/releases/8.x-1.0-alpha1) - 2020-09-03
