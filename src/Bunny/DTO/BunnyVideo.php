<?php

declare(strict_types=1);

namespace Drupal\bunny_stream\Bunny\DTO;

use Psr\Http\Message\ResponseInterface;

/**
 * Video returned by the Bunny Stream API.
 *
 * @see https://docs.bunny.net/reference/video_getvideo
 */
final readonly class BunnyVideo {

  /**
   * Constructs a new instance.
   *
   * @param int $videoLibraryId
   *   Library ID of the video.
   * @param string $guid
   *   Video uuid.
   * @param string $title
   *   Video title.
   * @param string $description
   *   Video description.
   * @param string $dateUploaded
   *   Uploaded date.
   * @param int $views
   *   Number of views of the video.
   * @param bool $isPublic
   *   Indicates if field is public.
   * @param int $length
   *   Length of the video in seconds.
   * @param \Drupal\bunny_stream\Bunny\DTO\VideoStatus $status
   *   Status of the video.
   * @param float $framerate
   *   Framerate of the video.
   * @param int|null $rotation
   *   Rotation of the video, NULL until the file has been processed.
   * @param int $width
   *   Width of the video.
   * @param int $height
   *   Height of the video.
   * @param string|null $availableResolutions
   *   List of available resolutions separated by comma, NULL until the file
   *   has been processed.
   * @param string $outputCodecs
   *   List of generated output codecs separated by comma.
   * @param int $thumbnailCount
   *   Number of thumbnails.
   * @param int $encodeProgress
   *   Progress of the encode process.
   * @param int $storageSize
   *   Size of the video to storage.
   * @param array $captions
   *   Captions of the video.
   * @param bool $hasMP4Fallback
   *   Indicates if the video has fallback version in mp4.
   * @param string $collectionId
   *   UUID of the collection.
   * @param string $thumbnailFileName
   *   File name of the thumbnail.
   * @param string $thumbnailUrl
   *   CDN URL of the thumbnail.
   * @param string $thumbnailBlurhash
   *   Blurhash placeholder of the thumbnail.
   * @param int $averageWatchTime
   *   Average watch time in seconds.
   * @param int $totalWatchTime
   *   Total watch time in seconds.
   * @param string $category
   *   Category of the video.
   * @param array $chapters
   *   Available chapters in the video.
   * @param array $moments
   *   Moments of the video.
   * @param array $metaTags
   *   Metadata of the video.
   * @param array $transcodingMessages
   *   Transcoding messages.
   * @param bool|null $jitEncodingEnabled
   *   Per-video override of just-in-time encoding, NULL to use the library
   *   setting.
   * @param int|null $smartGenerateStatus
   *   Aggregate status of AI generation: 0 = none, 1 = queued,
   *   2 = in progress, 3 = finished, 4 = failed.
   * @param array $smartGenerateFeaturesStatus
   *   AI generation status per feature (title, description, chapters,
   *   moments), using the same values as $smartGenerateStatus.
   * @param bool|null $hasOriginal
   *   Indicates if the original file is available.
   * @param string $originalHash
   *   SHA256 hash of the original file.
   * @param bool|null $hasHighQualityPreview
   *   Indicates if a high quality animated preview has been generated.
   */
  public function __construct(
    public int $videoLibraryId,
    public string $guid,
    public string $title = '',
    public string $description = '',
    public string $dateUploaded = '',
    public int $views = 0,
    public bool $isPublic = FALSE,
    public int $length = 0,
    public VideoStatus $status = VideoStatus::Created,
    public float $framerate = 0,
    public ?int $rotation = NULL,
    public int $width = 0,
    public int $height = 0,
    public ?string $availableResolutions = NULL,
    public string $outputCodecs = '',
    public int $thumbnailCount = 0,
    public int $encodeProgress = 0,
    public int $storageSize = 0,
    public array $captions = [],
    public bool $hasMP4Fallback = FALSE,
    public string $collectionId = '',
    public string $thumbnailFileName = '',
    public string $thumbnailUrl = '',
    public string $thumbnailBlurhash = '',
    public int $averageWatchTime = 0,
    public int $totalWatchTime = 0,
    public string $category = '',
    public array $chapters = [],
    public array $moments = [],
    public array $metaTags = [],
    public array $transcodingMessages = [],
    public ?bool $jitEncodingEnabled = NULL,
    public ?int $smartGenerateStatus = NULL,
    public array $smartGenerateFeaturesStatus = [],
    public ?bool $hasOriginal = NULL,
    public string $originalHash = '',
    public ?bool $hasHighQualityPreview = NULL,
  ) {}

  /**
   * Creates the video from a Bunny Stream API response.
   *
   * @throws \InvalidArgumentException
   *   If the response is not a video.
   */
  public static function fromResponse(ResponseInterface $response): self {
    try {
      $data = json_decode((string) $response->getBody(), TRUE, flags: JSON_THROW_ON_ERROR);
    }
    catch (\JsonException $e) {
      throw new \InvalidArgumentException('Bunny Stream returned invalid JSON.', previous: $e);
    }

    if (!is_array($data) || !is_int($data['videoLibraryId'] ?? NULL) || !is_string($data['guid'] ?? NULL)) {
      throw new \InvalidArgumentException('Bunny Stream did not return a video.');
    }

    return new self(
      videoLibraryId: $data['videoLibraryId'],
      guid: $data['guid'],
      title: (string) ($data['title'] ?? ''),
      description: (string) ($data['description'] ?? ''),
      dateUploaded: (string) ($data['dateUploaded'] ?? ''),
      views: (int) ($data['views'] ?? 0),
      isPublic: (bool) ($data['isPublic'] ?? FALSE),
      length: (int) ($data['length'] ?? 0),
      status: VideoStatus::tryFrom($data['status'] ?? 0) ?: VideoStatus::Created,
      framerate: (float) ($data['framerate'] ?? 0),
      rotation: isset($data['rotation']) ? (int) $data['rotation'] : NULL,
      width: (int) ($data['width'] ?? 0),
      height: (int) ($data['height'] ?? 0),
      availableResolutions: isset($data['availableResolutions']) ? (string) $data['availableResolutions'] : NULL,
      outputCodecs: (string) ($data['outputCodecs'] ?? ''),
      thumbnailCount: (int) ($data['thumbnailCount'] ?? 0),
      encodeProgress: (int) ($data['encodeProgress'] ?? 0),
      storageSize: (int) ($data['storageSize'] ?? 0),
      captions: (array) ($data['captions'] ?? []),
      hasMP4Fallback: (bool) ($data['hasMP4Fallback'] ?? FALSE),
      collectionId: (string) ($data['collectionId'] ?? ''),
      thumbnailFileName: (string) ($data['thumbnailFileName'] ?? ''),
      thumbnailUrl: (string) ($data['thumbnailUrl'] ?? ''),
      thumbnailBlurhash: (string) ($data['thumbnailBlurhash'] ?? ''),
      averageWatchTime: (int) ($data['averageWatchTime'] ?? 0),
      totalWatchTime: (int) ($data['totalWatchTime'] ?? 0),
      category: (string) ($data['category'] ?? ''),
      chapters: (array) ($data['chapters'] ?? []),
      moments: (array) ($data['moments'] ?? []),
      metaTags: (array) ($data['metaTags'] ?? []),
      transcodingMessages: (array) ($data['transcodingMessages'] ?? []),
      jitEncodingEnabled: isset($data['jitEncodingEnabled']) ? (bool) $data['jitEncodingEnabled'] : NULL,
      smartGenerateStatus: isset($data['smartGenerateStatus']) ? (int) $data['smartGenerateStatus'] : NULL,
      smartGenerateFeaturesStatus: (array) ($data['smartGenerateFeaturesStatus'] ?? []),
      hasOriginal: isset($data['hasOriginal']) ? (bool) $data['hasOriginal'] : NULL,
      originalHash: (string) ($data['originalHash'] ?? ''),
      hasHighQualityPreview: isset($data['hasHighQualityPreview']) ? (bool) $data['hasHighQualityPreview'] : NULL,
    );
  }

}
