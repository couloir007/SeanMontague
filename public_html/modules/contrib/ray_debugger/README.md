# Drupal Ray Debugger

A simple Drupal module for integrating the [Ray Debugging tool](https://myray.app/) for use within a Drupal project.
Ray allows developers to debug code in PHP, Twig, Javascript, and [integrates](https://spatie.be/docs/ray/v1/installation-in-your-project/introduction)
with numerous PHP/JS frameworks and content management systems.


## Install

This module requires installation with [composer](https://getcomposer.org/).

```
composer install drupal/ray_debugger
```

:bulb: It's common to include a development module like this using `--require-dev` in composer, or adding the module
to Drupal's `$settings['config_exclude_modules']` setting so the module won't be installed on other environments. Keep
in mind that leftover debug statements in the codebase might result in a fatal error without the module and dependencies
installed

## Requirements

- drupal/core 8.8 or greater
- PHP 7.3 or greater

## Modules

### Ray Debugger

The base module for common libraries.

Example usage in mytheme.theme file:
```php
function mytheme_preprocess_html(&$variables) {
  ray($variables)->purple()->label('HTML variables');
}
```

### Ray Debugger Twig

A module that allows you to debug Twig variables using Ray in theme/module Twig files.

Example usage in node.html.twig:
```twig
  {{ ray(view_mode, 'label', 'The current View Mode' ) }}
```

### Ray Debugger Javascript

A module that allows you to debug Javascript variables using Ray in theme/module JS files.

Example usage in a custom module javascript library:
```js
Drupal.behaviors.myModule = {
  attach: function (context, settings) {
    ray(settings);
  }
};
```

### Ray Debugger AlpineJS

A module that allows you to debug [alpine.js](https://alpinejs.dev/start-here) using Ray.

Example usage in a Twig template:
```twig
<button x-on:click="$ray(message)" x-data="{ message: 'I ❤️ Debugging Alpine' }">Click me!</button>
```

## Setting up Ray

Setup requires adding a [ray.php](https://spatie.be/docs/ray/v1/configuration/framework-agnostic-php) config file to
your project root.

### Lando

If using [Lando](https://lando.dev/) as a development environment for your Drupal project, you'll want to follow the [docker configuration docs
for Ray](https://spatie.be/docs/ray/v1/environment-specific-configuration/docker), setting up the `ray.php`
[config](https://spatie.be/docs/ray/v1/configuration/framework-agnostic-php) file specific to your `.lando` file
and adding `127.0.0.1 host.docker.internal` to your`/etc/hosts` file.

An example .lando.yml (using the Drupal 9 recipe):

```yaml
name: example-drupal-project
recipe: drupal9
config:
  webroot: web
  composer_version: '2.2.12'
  php: '8.1'
  via: nginx
  database: mysql:8.0
  xdebug: false
services:
  appserver:
    overrides:
      extra_hosts:
        - "host.docker.internal:host-gateway"

```
and the corresponding `ray.php` at the project root:

```php
<?php

return [
  'enable' => true,
  'host' => 'host.docker.internal',
  'port' => 23517,
  'remote_path' => '/app/web',
  'local_path' => '/home/username/example-drupal-project/web',
  'always_send_raw_values' => false,
];
```

### DDEV

TODO: Document setup with [ddev](https://github.com/drud/ddev/).
