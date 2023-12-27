<?php

namespace Drupal\bunny_stream;

/**
 * This class is to map the fields of the videos.
 */
class BunnyVideo {

  /**
   * Library ID of the video.
   *
   * @var int
   */
  public int $videoLibraryId;

  /**
   * Video uuid.
   *
   * @var string
   */
  public string $guid;

  /**
   * Video title.
   *
   * @var string
   */
  public string $title;

  /**
   * Uploaded date.
   *
   * @var string
   */
  public string $dateUploaded;

  /**
   * Number of views of the video.
   *
   * @var int
   */
  public int $views;

  /**
   * Indicates if field is public.
   *
   * @var bool
   */
  public bool $isPublic;

  /**
   * Length of the video in seconds.
   *
   * @var int
   */
  public int $length;

  /**
   * Status of the video.
   *
   * @var int
   */
  public int $status;

  /**
   * Framerate of the video.
   *
   * @var int
   */
  public int $framerate;

  /**
   * Rotation of the video.
   *
   * @var int
   */
  public int $rotation;

  /**
   * Width of the video.
   *
   * @var int
   */
  public int $width;

  /**
   * Height of the video.
   *
   * @var int
   */
  public int $height;

  /**
   * List of available resolutions separated by coma.
   *
   * @var string
   */
  public string $availableResolutions;

  /**
   * Number of thumbnails.
   *
   * @var int
   */
  public int $thumbnailCount;

  /**
   * Progress of the encode process.
   *
   * @var int
   */
  public int $encodeProgress;

  /**
   * Size of the video to storage.
   *
   * @var int
   */
  public int $storageSize;

  /**
   * Captions of the video.
   *
   * @var array
   */
  public array $captions;

  /**
   * Indicates if the video has fallback version in mp4.
   *
   * @var bool
   */
  public bool $hasMP4Fallback;

  /**
   * UUID of the collection.
   *
   * @var string
   */
  public string $collectionId;

  /**
   * File name of the thumbnail.
   *
   * @var string
   */
  public string $thumbnailFileName;

  /**
   * Average watch time in seconds.
   *
   * @var int
   */
  public int $averageWatchTime;

  /**
   * Total watch time in seconds.
   *
   * @var int
   */
  public int $totalWatchTime;

  /**
   * Category of the video.
   *
   * @var string
   */
  public string $category;

  /**
   * Available chapters in the video.
   *
   * @var array
   */
  public array $chapters;

  /**
   * Moments of the video.
   *
   * @var array
   */
  public array $moments;

  /**
   * Metadata of the video.
   *
   * @var array
   */
  public array $metaTags;

  /**
   * Transcoding messages.
   *
   * @var array
   */
  public array $transcodingMessages;

}
