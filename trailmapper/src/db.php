<?php

/**
 * Returns a PDO pgsql connection built from config/settings.php.
 */

$settings = require __DIR__ . '/../config/settings.php';

$dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $settings['host'], $settings['port'], $settings['dbname']);

return new PDO($dsn, $settings['user'], $settings['password']);
