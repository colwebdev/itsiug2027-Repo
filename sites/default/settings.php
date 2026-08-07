<?php

// phpcs:ignoreFile

$databases = [];

$databases['default']['default'] = [
  'driver' => 'mysql',
  'database' => 'db',
  'username' => 'db',
  'password' => 'db',
  'host' => '127.0.0.1',
  'port' => '65194',
  'prefix' => '',
  'collation' => 'utf8mb4_general_ci',
];

/**
 * REQUIRED: Hash salt
 * You can generate a random string for local dev.
 */
$settings['hash_salt'] = 'local-dev-salt-change-me-8f3c9a1d2b';

/**
 * Container settings
 */
$settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.yml';

/**
 * File scan ignore (keep default safe values)
 */
$settings['file_scan_ignore_directories'] = [
  'node_modules',
  'bower_components',
];

/**
 * Drupal temp / state improvements (optional but fine for local dev)
 */
$settings['state_cache'] = TRUE;
$settings['entity_update_batch_size'] = 50;
$settings['entity_update_backup'] = TRUE;

/**
 * DDEV support (keep if you're using DDEV)
 */
$settings['config_sync_directory'] = 'config/sync';

if (getenv('IS_DDEV_PROJECT') == 'true' && file_exists(__DIR__ . '/settings.ddev.php')) {
  include __DIR__ . '/settings.ddev.php';
}

$settings['testing_package_manager'] = TRUE;