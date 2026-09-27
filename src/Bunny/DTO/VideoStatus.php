<?php

declare(strict_types=1);

namespace Drupal\bunny_stream\Bunny\DTO;

/**
 * List of video statuses.
 *
 * Not to be confused with the video states sent in webhooks, which use
 * different values.
 *
 * @see https://docs.bunny.net/reference/video_getvideo
 */
enum VideoStatus: int {

  case Created = 0;
  case Uploaded = 1;
  case Processing = 2;
  case Transcoding = 3;
  case Finished = 4;
  case Error = 5;
  case UploadFailed = 6;
  case JitSegmenting = 7;
  case JitPlaylistsCreated = 8;

  /**
   * Whether the video still accepts a file upload.
   */
  public function isUploadable(): bool {
    return match ($this) {
      self::Created, self::UploadFailed => TRUE,
      default => FALSE,
    };
  }

}
