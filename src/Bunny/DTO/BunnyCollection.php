<?php

declare(strict_types=1);

namespace Drupal\bunny_stream\Bunny\DTO;

use Psr\Http\Message\ResponseInterface;

/**
 * Collection returned by the Bunny Stream API.
 *
 * @see https://bunny.net/docs/api-reference/stream/manage-collections/get-collection
 */
final readonly class BunnyCollection {

  /**
   * Constructs a new instance.
   *
   * @param int $videoLibraryId
   *   Library ID of the collection.
   * @param string $guid
   *   Collection uuid.
   * @param string $name
   *   Collection name.
   * @param int $videoCount
   *   Number of on-demand videos in the collection.
   * @param int $liveStreamCount
   *   Number of live streams in the collection.
   * @param int $totalSize
   *   Storage size of the collection in bytes.
   * @param string $previewVideoIds
   *   Preview video GUIDs separated by comma.
   * @param string[] $previewImageUrls
   *   Preview image URLs, only populated when thumbnails are requested.
   */
  public function __construct(
    public int $videoLibraryId,
    public string $guid,
    public string $name = '',
    public int $videoCount = 0,
    public int $liveStreamCount = 0,
    public int $totalSize = 0,
    public string $previewVideoIds = '',
    public array $previewImageUrls = [],
  ) {}

  /**
   * Creates the collection from a Bunny Stream API response.
   *
   * @throws \InvalidArgumentException
   *   If the response is not a collection.
   */
  public static function fromResponse(ResponseInterface $response): self {
    try {
      $data = json_decode((string) $response->getBody(), TRUE, flags: JSON_THROW_ON_ERROR);
    }
    catch (\JsonException $e) {
      throw new \InvalidArgumentException('Bunny Stream returned invalid JSON.', previous: $e);
    }

    return self::fromArray($data);
  }

  /**
   * Creates the collection from decoded API data.
   *
   * @throws \InvalidArgumentException
   *   If the data is not a collection.
   */
  public static function fromArray(mixed $data): self {
    if (!is_array($data) || !is_int($data['videoLibraryId'] ?? NULL) || !is_string($data['guid'] ?? NULL)) {
      throw new \InvalidArgumentException('Bunny Stream did not return a collection.');
    }

    return new self(
      videoLibraryId: $data['videoLibraryId'],
      guid: $data['guid'],
      name: (string) ($data['name'] ?? ''),
      videoCount: (int) ($data['videoCount'] ?? 0),
      liveStreamCount: (int) ($data['liveStreamCount'] ?? 0),
      totalSize: (int) ($data['totalSize'] ?? 0),
      previewVideoIds: (string) ($data['previewVideoIds'] ?? ''),
      previewImageUrls: array_values(array_map('strval', (array) ($data['previewImageUrls'] ?? []))),
    );
  }

}
