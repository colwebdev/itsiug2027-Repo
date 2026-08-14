<?php

declare(strict_types=1);

namespace Drupal\itsiug_programme;

use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Renders the time-sensitive parts of the programme outside the page cache.
 */
final class ProgrammeLazyBuilders implements TrustedCallbackInterface {

  use StringTranslationTrait;

  /**
   * Builds the "On now" and "Up next" listings.
   */
  public function nowNext(): array {
    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['programme-now-next']],
      '#attached' => ['library' => ['itsiug_programme/programme']],
      // Reflects the clock, so it must never be cached.
      '#cache' => ['max-age' => 0],
    ];

    $build['now'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['programme-now-next__panel', 'programme-now-next__panel--now']],
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $this->t('On now'),
      ],
      'view' => views_embed_view('programme', 'block_now'),
    ];

    $build['next'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['programme-now-next__panel', 'programme-now-next__panel--next']],
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $this->t('Up next'),
      ],
      'view' => views_embed_view('programme', 'block_next'),
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks(): array {
    return ['nowNext'];
  }

}
