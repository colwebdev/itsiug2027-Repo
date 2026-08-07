<?php

/**
 * @file
 * Provides tokens for the content_moderation_link module.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;

/**
 * Implements hook_token_info().
 */
function content_moderation_link_token_info() {
  // Found in the node replacement patterns
  $info['tokens']['node']['moderation-link'] = [
    'name' => t('Moderation Link'),
    'description' => t('Link to move the node to a specified moderation state'),
    'dynamic' => TRUE,
    'module' => 'content_moderation_link',
  ];

  return $info;
}

/**
 * Implements hook_tokens().
 */
function content_moderation_link_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type !== 'node' || empty($data['node'])) {
    return $replacements;
  }

  foreach ($tokens as $token_name => $original) {
    // @todo consider using "published" as a default?
    if (!str_contains($token_name, ':')) {
      continue;
    }
    [$name, $state] = explode(':', $token_name, 2);
    switch ($name) {
      case 'moderation-link':
        $bubbleable_metadata->addCacheContexts(['url.site']);
        // Allow machine name characters, and a hyphen. Strip all others from
        // the specified state string.
        $sanitized = preg_replace('/[^-a-z0-9_]/', '', $state);
        $url = Url::fromRoute(
          'content_moderation_link.moderate',
          ['state' => $sanitized, 'type' => 'node', 'id' => $data['node']->id()],
          ['absolute' => TRUE],
        );
        $replacements[$original] = $url->toString();
        break;
    }
  }
  return $replacements;
}
