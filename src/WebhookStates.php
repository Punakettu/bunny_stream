<?php

namespace Drupal\bunny_stream;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * List of states for webhooks.
 *
 * For more information check the documentation:
 * https://docs.bunny.net/docs/stream-webhook#status-list.
 */
enum WebhookStates: int {

  case QUEUED = 0;
  case PROCESSING = 1;
  case ENCODING = 2;
  case FINISHED = 3;
  case RESOLUTION_FINISHED = 4;
  case FAILED = 5;
  case PRESIGNED_UPLOAD_STARTED = 6;
  case PRESIGNED_UPLOAD_FINISHED = 7;
  case PRESIGNED_UPLOAD_FAILED = 8;
  case CAPTION_GENERATED = 9;
  case TITLE_OR_DESCRIPTION_GENERATED = 10;

  /**
   * Get human readable status label.
   */
  public function label(): TranslatableMarkup {
    return match ($this) {
      self::QUEUED => new TranslatableMarkup('Queued'),
      self::PROCESSING => new TranslatableMarkup('Processing'),
      self::ENCODING => new TranslatableMarkup('Encoding'),
      self::FINISHED => new TranslatableMarkup('Finished'),
      self::RESOLUTION_FINISHED => new TranslatableMarkup('Resolution finished'),
      self::FAILED => new TranslatableMarkup('Failed'),
      self::PRESIGNED_UPLOAD_STARTED => new TranslatableMarkup('Presigned upload started'),
      self::PRESIGNED_UPLOAD_FINISHED => new TranslatableMarkup('Presigned upload finished'),
      self::PRESIGNED_UPLOAD_FAILED => new TranslatableMarkup('Presigned upload failed'),
      self::CAPTION_GENERATED => new TranslatableMarkup('Caption generated'),
      self::TITLE_OR_DESCRIPTION_GENERATED => new TranslatableMarkup('Title or description generated'),
    };
  }

}
