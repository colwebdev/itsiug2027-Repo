<?php

declare(strict_types=1);

namespace Drupal\tagify_iconify_icons\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Hook implementations for the Tagify Iconify Icons module.
 */
final class TagifyIconifyIconsHooks {

  /**
   * Constructs a TagifyIconifyIconsHooks object.
   *
   * @param object $iconifyService
   *   The Iconify service ('iconify_icons.iconify_service').
   */
  public function __construct(
    #[Autowire(service: 'iconify_icons.iconify_service')]
    private readonly object $iconifyService,
  ) {}

  /**
   * Implements hook_tagify_autocomplete_match_alter().
   *
   * @param string|null $label
   *   The matched label. Set to NULL to exclude the match.
   * @param string|null $info_label
   *   The extra information to be shown aside the entity label.
   * @param array $context
   *   An array of context data. The following keys are always available:
   *     - entity: The entity object.
   *     - info_label: The info label, but without token replacement.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \JsonException
   */
  #[Hook('tagify_autocomplete_match_alter')]
  public function tagifyAutocompleteMatchAlter(?string &$label, ?string &$info_label, array $context): void {
    // Override the info label with svg icon.
    if (str_contains($context['info_label'] ?? '', ':icon')) {
      // Take 'Icon name (collection name)', match the collection name from
      // inside the parentheses.
      // @see \Drupal\Core\Entity\Element\EntityAutocomplete::extractEntityIdFromAutocompleteInput
      if (preg_match('/(.+\\s)\\(([^\\)]+)\\)/', $info_label, $matches)) {
        // @phpstan-ignore-next-line
        $info_label = $this->iconifyService->generateSvgIcon(trim($matches[2]), trim($matches[1]));
      }
    }
  }

}
