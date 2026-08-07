<?php

declare(strict_types=1);

namespace Drupal\tagify_icons\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Theme\Icon\IconDefinition;

/**
 * Hook implementations for the Tagify Icons module.
 */
final class TagifyIconsHooks {

  public function __construct(
    private readonly RendererInterface $renderer,
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
    if (str_contains($context['info_label'] ?? '', ':target_id')) {
      // @phpstan-ignore-next-line
      $renderable = IconDefinition::getRenderable($info_label);
      $info_label = $this->renderer->renderInIsolation($renderable);
    }
  }

}
