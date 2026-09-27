<?php

declare(strict_types=1);

namespace Drupal\bunny_stream\Bunny\DTO;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * List of video states sent in webhooks.
 *
 * @see https://docs.bunny.net/docs/stream-webhook#status-list
 */
enum VideoStates: int {

  case Queued = 0;
  case Processing = 1;
  case Encoding = 2;
  case Finished = 3;
  case ResolutionFinished = 4;
  case Failed = 5;
  case PresignedUploadStarted = 6;
  case PresignedUploadFinished = 7;
  case PresignedUploadFailed = 8;
  case CaptionGenerated = 9;
  case TitleOrDescriptionGenerated = 10;

  /**
   * Get human readable status label.
   */
  public function label(): TranslatableMarkup {
    return match ($this) {
      self::Queued => new TranslatableMarkup('Queued'),
      self::Processing => new TranslatableMarkup('Processing'),
      self::Encoding => new TranslatableMarkup('Encoding'),
      self::Finished => new TranslatableMarkup('Finished'),
      self::ResolutionFinished => new TranslatableMarkup('Resolution finished'),
      self::Failed => new TranslatableMarkup('Failed'),
      self::PresignedUploadStarted => new TranslatableMarkup('Presigned upload started'),
      self::PresignedUploadFinished => new TranslatableMarkup('Presigned upload finished'),
      self::PresignedUploadFailed => new TranslatableMarkup('Presigned upload failed'),
      self::CaptionGenerated => new TranslatableMarkup('Caption generated'),
      self::TitleOrDescriptionGenerated => new TranslatableMarkup('Title or description generated'),
    };
  }

}
