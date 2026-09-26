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

  /**
   * Is not posible to translate this options here, move to another place.
   *
   * @deprecated in bunny_stream:1.0.0-beta3 and is removed from
   *   bunny_stream:2.0.0. Use label() method instead.
   */
  public const STATES = [
    0 => 'Queued',
    1 => 'Processing',
    2 => 'Encoding',
    3 => 'Finished',
    4 => 'Resolution finished',
    5 => 'Failed',
    6 => 'Presigned upload stated',
    7 => 'Presigned upload finished',
    8 => 'Presigned upload failed',
    9 => 'Caption generated',
    10 => 'Title or description generated',
  ];

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
   * Misspelled alias of WebhookStates::PROCESSING.
   *
   * @deprecated in bunny_stream:1.0.0 and is removed from bunny_stream:2.0.0.
   *   Use WebhookStates::PROCESSING instead.
   *
   * @see https://docs.bunny.net/docs/stream-webhook#status-list
   */
  public const PROCESING = self::PROCESSING;

  /**
   * Misspelled alias of WebhookStates::PRESIGNED_UPLOAD_STARTED.
   *
   * @deprecated in bunny_stream:1.0.0 and is removed from bunny_stream:2.0.0.
   *   Use WebhookStates::PRESIGNED_UPLOAD_STARTED instead.
   *
   * @see https://docs.bunny.net/docs/stream-webhook#status-list
   */
  public const PRESIGNED_UPLOAD_STATED = self::PRESIGNED_UPLOAD_STARTED;

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
