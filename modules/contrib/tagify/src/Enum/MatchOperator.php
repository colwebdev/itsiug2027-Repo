<?php

declare(strict_types=1);

namespace Drupal\tagify\Enum;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * The autocomplete matching strategy used by the Tagify widgets.
 *
 * Backed by the same strings persisted in widget settings and passed to core's
 * entity reference selection handlers, so existing configuration and the
 * hashed selection_settings payload remain valid.
 */
enum MatchOperator: string {

  case StartsWith = 'STARTS_WITH';
  case Contains = 'CONTAINS';

  /**
   * Returns the human-readable label for the operator.
   */
  public function label(): TranslatableMarkup {
    return match ($this) {
      self::StartsWith => new TranslatableMarkup('Starts with'),
      self::Contains => new TranslatableMarkup('Contains'),
    };
  }

  /**
   * Returns the data-match-operator flag consumed by the Tagify JS.
   */
  public function toDataAttribute(): int {
    return match ($this) {
      self::Contains => 1,
      self::StartsWith => 0,
    };
  }

  /**
   * Builds the options array for a radios form element.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup>
   *   Operator value keyed to its label.
   */
  public static function options(): array {
    $options = [];
    foreach (self::cases() as $case) {
      $options[$case->value] = $case->label();
    }
    return $options;
  }

  /**
   * Resolves a stored/raw setting value to an operator, defaulting to CONTAINS.
   *
   * @param mixed $value
   *   The raw value from settings, query parameters or an element property.
   */
  public static function fromSettings(mixed $value): self {
    return self::tryFrom((string) ($value ?? '')) ?? self::Contains;
  }

}
