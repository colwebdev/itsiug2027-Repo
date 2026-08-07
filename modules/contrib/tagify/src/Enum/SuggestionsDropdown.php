<?php

declare(strict_types=1);

namespace Drupal\tagify\Enum;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Controls when the Tagify suggestions dropdown is shown.
 *
 * Backed by the integers persisted in widget settings (0/1), so existing
 * configuration remains valid.
 */
enum SuggestionsDropdown: int {

  case OnClick = 0;
  case OnFirstCharacter = 1;

  /**
   * Returns the human-readable label for the behaviour.
   */
  public function label(): TranslatableMarkup {
    return match ($this) {
      self::OnClick => new TranslatableMarkup('On click'),
      self::OnFirstCharacter => new TranslatableMarkup('When 1 character is typed'),
    };
  }

  /**
   * Builds the options array for a radios form element.
   *
   * @return array<int, \Drupal\Core\StringTranslation\TranslatableMarkup>
   *   Behaviour value keyed to its label.
   */
  public static function options(): array {
    $options = [];
    foreach (self::cases() as $case) {
      $options[$case->value] = $case->label();
    }
    return $options;
  }

  /**
   * Resolves a stored/raw setting value, defaulting to "on first character".
   *
   * @param mixed $value
   *   The raw value from settings or an element property.
   */
  public static function fromSettings(mixed $value): self {
    return self::tryFrom((int) ($value ?? 1)) ?? self::OnFirstCharacter;
  }

}
