
# Cypress Tests for Mercury Editor
This directory contains Cypress tests for the Mercury Editor module. These tests are designed to ensure that the Mercury Editor functions correctly within a Drupal environment.

## Installation and Setup

### Prerequisites
These tests require Mercury Editor's test modules to be enabled. In settings.php, ensure that the following configuration is set so Drupal can properly discover hidden test modules:

```php
$settings['extension_discovery_scan_tests'] = TRUE;
```

Install npm dependencies:

```bash
npm install
```

### Running Tests
To run the Cypress tests, use the following command:
```
npm run cy:run -- --config baseUrl=http://mercury-demo.ddev.site --spec ./cypress/e2e/mercury-editor/ --env drushCommand="ddev drush \$COMMAND"
```

Set the `baseUrl` to your local Drupal site URL. The `drushCommand` environment variable allows you to run Drush commands within the tests.
