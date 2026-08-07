<?php

declare(strict_types=1);

namespace Drupal\tagify\Hook;

use Drupal\Core\Asset\LibrariesDirectoryFileFinder;
use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Runtime requirements for the Tagify module.
 */
final class TagifyRequirements {

  use StringTranslationTrait;

  public function __construct(
    private readonly LibrariesDirectoryFileFinder $librariesDirectoryFileFinder,
  ) {}

  /**
   * Implements hook_runtime_requirements().
   *
   * Reports whether the Tagify JS/CSS assets are served locally or fetched
   * from the third-party CDN, so administrators on networks that block
   * external requests (CSP, offline, or company policy) can tell at a glance
   * why the widget might fail to load.
   */
  #[Hook('runtime_requirements')]
  public function runtime(): array {
    $served_locally = (bool) $this->librariesDirectoryFileFinder->find('tagify/dist/tagify.js');

    if ($served_locally) {
      return [
        'tagify_library' => [
          'title' => $this->t('Tagify library'),
          'value' => $this->t('Served locally'),
          'severity' => RequirementSeverity::OK,
        ],
      ];
    }

    return [
      'tagify_library' => [
        'title' => $this->t('Tagify library'),
        'value' => $this->t('Loaded from CDN'),
        'description' => $this->t('The Tagify assets are loaded from the third-party CDN <em>cdnjs.cloudflare.com</em>. If your environment blocks external requests (Content Security Policy, offline deployments, or company policy), download the library and serve it locally. See the <a href=":readme">module README</a> for installation instructions.', [
          ':readme' => Url::fromUri('https://www.drupal.org/project/tagify')->toString(),
        ]),
        'severity' => RequirementSeverity::Info,
      ],
    ];
  }

}
