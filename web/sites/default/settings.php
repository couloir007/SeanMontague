<?php

// phpcs:ignoreFile

$settings['config_sync_directory'] = dirname(DRUPAL_ROOT) . '/config/sync';
$databases['default']['default'] = [
  'database' => 'drupal',
  'username' => 'postgres',
  'password' => '',
  'prefix' => '',
  'host' => 'database',
  'port' => '5432',
  'isolation_level' => 'READ COMMITTED',
  'driver' => 'pgsql',
  'namespace' => 'Drupal\\pgsql\\Driver\\Database\\pgsql',
  'autoload' => 'core/modules/pgsql/src/Driver/Database/pgsql/',
];

$settings['hash_salt'] = 'hgk88PAggtje3XVJApwV77p3C851__ZiH8zbN7bBAZNbqP9xkw1rLTwK3LAG8VQW3atXeLk-xA';

$options['uri'] = "https://trailmapper-psql.lndo.site/";
$options['base_url'] = "https://trailmapper-psql.lndo.site/";

$settings['trusted_host_patterns'] = [
  '^trailmapper-psql\.lndo\.site$',
  '^localhost'
];

$config['system.performance']['css']['preprocess'] = FALSE;
$config['system.performance']['js']['preprocess'] = FALSE;

$settings['container_yamls'][] = __DIR__ . '/local.services.yml';
