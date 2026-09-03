<?php

// phpcs:ignoreFile
$settings['file_private_path'] = dirname(DRUPAL_ROOT) . '/private';
//$config['system.site']['uuid'] = '343bfea3-d7c2-4df4-a6c1-2816e948d47f';

//$db = 'roundybr_seanmontague';
$db = 'roundybr_drupal';
$settings['config_sync_directory'] = dirname(DRUPAL_ROOT) . '/config/sync';

$databases['default']['default'] = [
  'database' => $db,
  'username' => 'roundybr',
  'password' => '!WestmoreEightHours2026!',
  'prefix' => '',
  'host' => 'us19.acugis-dns.com',
  'port' => '5432',
  'isolation_level' => 'READ COMMITTED',
  'driver' => 'pgsql',
  'namespace' => 'Drupal\\pgsql\\Driver\\Database\\pgsql',
  'autoload' => 'core/modules/pgsql/src/Driver/Database/pgsql/',
];

$settings['hash_salt'] = 'hgk88PAggtje3XVJApwV77p3C851__ZiH8zbN7bBAZNbqP9xkw1rLTwK3LAG8VQW3atXeLk-xA';

$options['uri'] = "https://seanmontague.com/";
$options['base_url'] = "https://seanmontague.com/";

$settings['trusted_host_patterns'] = [
  '^roundybrookfarm\.com$',
  '^seanmontague\.com$',
  '^localhost'
];


$config['environment_indicator.indicator']['name'] = 'AcuGIS';
$config['environment_indicator.indicator']['bg_color'] = '#e7131a';
$config['environment_indicator.indicator']['fg_color'] = '#ffffff';

$settings['container_yamls'][] = __DIR__ . '/services.yml';

/**
 * If there is a local settings file, then include it
 */
//$local_settings = __DIR__ . "/settings.local.php";
if (file_exists(__DIR__ . "/settings.local.php")) {
  $settings['container_yamls'][] = __DIR__ . '/local.services.yml';
  include __DIR__ . "/settings.local.php";
}
