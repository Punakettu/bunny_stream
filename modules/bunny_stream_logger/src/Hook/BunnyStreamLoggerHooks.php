<?php

declare(strict_types=1);

namespace Drupal\bunny_stream_logger\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for bunny_stream_logger.
 */
final class BunnyStreamLoggerHooks {

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected Connection $database,
  ) {}

  /**
   * Implements hook_cron().
   */
  #[Hook('cron')]
  public function cron(): void {
    // Cleanup the watchdog table.
    $row_limit = $this->configFactory->get('bunny_stream_logger.settings')->get('row_limit');

    // For row limit n, get the wid of the nth row in descending wid order.
    // Counting the most recent n rows avoids issues with wid number sequences,
    // e.g. auto_increment value > 1 or rows deleted directly from the table.
    if ($row_limit > 0) {
      $min_row = $this->database->select('bunny_stream_logger', 'b')
        ->fields('b', ['bid'])
        ->orderBy('bid', 'DESC')
        ->range($row_limit - 1, 1)
        ->execute()
        ->fetchField();

      // Delete all table entries older than the nth row, if nth row was found.
      if ($min_row) {
        $this->database->delete('bunny_stream_logger')
          ->condition('bid', $min_row, '<')
          ->execute();
      }
    }
  }

}
