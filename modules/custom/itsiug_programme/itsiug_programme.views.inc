<?php

/**
 * @file
 * Views integration for the conference programme.
 */

declare(strict_types=1);

/**
 * Implements hook_views_data_alter().
 */
function itsiug_programme_views_data_alter(array &$data): void {
  $data['node_field_data']['itsiug_session_current'] = [
    'title' => t('Session: relative to now'),
    'help' => t('Limit sessions to those on now, up next, or still to come today.'),
    'filter' => [
      'field' => 'nid',
      'id' => 'itsiug_session_current',
      'label' => t('Session: relative to now'),
      'allow empty' => TRUE,
    ],
  ];
}
