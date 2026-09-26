<?php

declare(strict_types=1);

namespace Drupal\bunny_stream\Hook;

use Drupal\bunny_stream\Form\BunnyStreamMediaLibraryForm;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for bunny_stream.
 */
final class BunnyStreamHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(array $existing, string $type, string $theme, string $path): array {
    return [
      'bunny_embed' => [
        'variables' => [
          'url' => NULL,
          'options' => [],
        ],
      ],
    ];
  }

  /**
   * Implements hook_media_source_info_alter().
   */
  #[Hook('media_source_info_alter')]
  public function mediaSourceInfoAlter(array &$sources): void {
    if (empty($sources['bunny_stream']['forms']['media_library_add'])) {
      $sources['bunny_stream']['forms']['media_library_add'] = BunnyStreamMediaLibraryForm::class;
    }
  }

}
