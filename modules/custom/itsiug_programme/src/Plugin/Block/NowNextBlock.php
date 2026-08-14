<?php

declare(strict_types=1);

namespace Drupal\itsiug_programme\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Shows what is running now and what starts next.
 */
#[Block(
  id: "itsiug_programme_now_next",
  admin_label: new TranslatableMarkup("Programme: on now and up next"),
  category: new TranslatableMarkup("ITSIUG"),
)]
final class NowNextBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [
      // Rendered after the page cache so the surrounding page stays cacheable.
      '#lazy_builder' => ['itsiug_programme.lazy_builders:nowNext', []],
      '#create_placeholder' => TRUE,
    ];
  }

}
