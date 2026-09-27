<?php

namespace Drupal\bunny_stream\Bunny;

use Drupal\bunny_stream\BunnyStreamLibraryInterface;
use Drupal\bunny_stream\Bunny\DTO\BunnyCollection;
use Drupal\bunny_stream\Bunny\DTO\BunnyCollectionList;
use Drupal\bunny_stream\Bunny\DTO\BunnyVideo;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Execute request to Bunny.net API to manage videos.
 *
 * For more information about the API check the documentation:
 * https://docs.bunny.net/reference/api-overview.
 */
class VideoManager {

  /**
   * Endpoint for TUS resumable uploads.
   *
   * @see https://bunny.net/docs/stream/tus-resumable-uploads
   */
  public const TUS_ENDPOINT = "https://video.bunnycdn.com/tusupload";

  /**
   * Constructor of the class.
   *
   * @param \GuzzleHttp\ClientInterface $client
   *   The http client to execute the requests.
   * @param \Drupal\bunny_stream\BunnyStreamLibraryInterface $library
   *   The Bunny Stream library.
   */
  public function __construct(
    protected ClientInterface $client,
    protected BunnyStreamLibraryInterface $library,
  ) {}

  /**
   * Loads video information from bunny.net API.
   *
   * @param string $videoId
   *   The video to load.
   *
   * @return \Drupal\bunny_stream\Bunny\DTO\BunnyVideo
   *   Loaded video.
   *
   * @throws \Drupal\bunny_stream\Bunny\BunnyException
   *   If the video could not be loaded.
   */
  public function getVideo(string $videoId): BunnyVideo {
    try {
      $response = $this->executeRequest(
        'GET',
        sprintf("/library/%s/videos/%s", $this->library->id(), $videoId)
      );

      return BunnyVideo::fromResponse($response);
    }
    catch (\Exception $e) {
      throw new BunnyException($e->getmessage(), previous: $e);
    }
  }

  /**
   * Creates an empty video object that a file can be uploaded to.
   *
   * @param string $title
   *   The video title.
   * @param string|null $collectionId
   *   The collection to add the video to.
   * @param int|null $thumbnailTime
   *   Video time in milliseconds to extract the thumbnail from.
   *
   * @return \Drupal\bunny_stream\Bunny\DTO\BunnyVideo
   *   Created video.
   *
   * @throws \Drupal\bunny_stream\Bunny\BunnyException
   *   If the video could not be created.
   */
  public function createVideo(string $title, ?string $collectionId = NULL, ?int $thumbnailTime = NULL): BunnyVideo {
    try {
      $response = $this->executeRequest('POST', sprintf('/library/%s/videos', $this->library->id()), [
        'json' => array_filter([
          'title' => $title,
          'collectionId' => $collectionId,
          'thumbnailTime' => $thumbnailTime,
        ], static fn ($value) => $value !== NULL),
      ]);

      return BunnyVideo::fromResponse($response);
    }
    catch (\Exception $e) {
      throw new BunnyException('Failed to create Bunny Stream video: ' . $e->getMessage(), previous: $e);
    }
  }

  /**
   * Loads collection information from bunny.net API.
   *
   * @param string $collectionId
   *   The collection to load.
   * @param bool $includeThumbnails
   *   Whether to populate the preview image URLs.
   *
   * @return \Drupal\bunny_stream\Bunny\DTO\BunnyCollection
   *   Loaded collection.
   *
   * @throws \Drupal\bunny_stream\Bunny\BunnyException
   *   If the collection could not be loaded.
   */
  public function getCollection(string $collectionId, bool $includeThumbnails = FALSE): BunnyCollection {
    try {
      $response = $this->executeRequest(
        'GET',
        sprintf('/library/%s/collections/%s', $this->library->id(), $collectionId),
        ['query' => ['includeThumbnails' => $includeThumbnails ? 'true' : 'false']],
      );

      return BunnyCollection::fromResponse($response);
    }
    catch (\Exception $e) {
      throw new BunnyException($e->getMessage(), previous: $e);
    }
  }

  /**
   * Lists collections of the library.
   *
   * @param int $page
   *   The page to load, 1-based.
   * @param int $itemsPerPage
   *   Number of collections per page, between 10 and 1000.
   * @param string|null $search
   *   Filters collections by name.
   * @param string $orderBy
   *   Sort order, either "date" or "title".
   * @param bool $includeThumbnails
   *   Whether to populate the preview image URLs.
   *
   * @return \Drupal\bunny_stream\Bunny\DTO\BunnyCollectionList
   *   The page of collections.
   *
   * @throws \Drupal\bunny_stream\Bunny\BunnyException
   *   If the collections could not be loaded.
   */
  public function listCollections(int $page = 1, int $itemsPerPage = 100, ?string $search = NULL, string $orderBy = 'date', bool $includeThumbnails = FALSE): BunnyCollectionList {
    try {
      $response = $this->executeRequest('GET', sprintf('/library/%s/collections', $this->library->id()), [
        'query' => array_filter([
          'page' => $page,
          'itemsPerPage' => $itemsPerPage,
          'search' => $search,
          'orderBy' => $orderBy,
          'includeThumbnails' => $includeThumbnails ? 'true' : 'false',
        ], static fn ($value) => $value !== NULL),
      ]);

      return BunnyCollectionList::fromResponse($response);
    }
    catch (\Exception $e) {
      throw new BunnyException($e->getMessage(), previous: $e);
    }
  }

  /**
   * Creates a collection.
   *
   * @param string $name
   *   The collection name, up to 255 characters.
   *
   * @return \Drupal\bunny_stream\Bunny\DTO\BunnyCollection
   *   Created collection.
   *
   * @throws \Drupal\bunny_stream\Bunny\BunnyException
   *   If the collection could not be created.
   */
  public function createCollection(string $name): BunnyCollection {
    try {
      $response = $this->executeRequest('POST', sprintf('/library/%s/collections', $this->library->id()), [
        'json' => ['name' => $name],
      ]);

      return BunnyCollection::fromResponse($response);
    }
    catch (\Exception $e) {
      throw new BunnyException($e->getMessage(), previous: $e);
    }
  }

  /**
   * Renames a collection.
   *
   * @param string $collectionId
   *   The collection to update.
   * @param string $name
   *   The new collection name, up to 255 characters.
   *
   * @throws \Drupal\bunny_stream\Bunny\BunnyException
   *   If the collection could not be updated.
   */
  public function updateCollection(string $collectionId, string $name): void {
    try {
      $this->executeRequest('POST', sprintf('/library/%s/collections/%s', $this->library->id(), $collectionId), [
        'json' => ['name' => $name],
      ]);
    }
    catch (\Exception $e) {
      throw new BunnyException($e->getMessage(), previous: $e);
    }
  }

  /**
   * Deletes a collection.
   *
   * @param string $collectionId
   *   The collection to delete.
   *
   * @throws \Drupal\bunny_stream\Bunny\BunnyException
   *   If the collection could not be deleted.
   */
  public function deleteCollection(string $collectionId): void {
    try {
      $this->executeRequest('DELETE', sprintf('/library/%s/collections/%s', $this->library->id(), $collectionId));
    }
    catch (\Exception $e) {
      throw new BunnyException($e->getMessage(), previous: $e);
    }
  }

  /**
   * Execute the request to Bunny.net API.
   *
   * @param string $method
   *   The method for the request.
   * @param string $url
   *   The URL where execute the request.
   * @param array $options
   *   Request options, like headers or query parameters.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   Response of the request.
   *
   * @throws \LogicException
   *   If a write request is made without the read/write API key.
   * @throws \GuzzleHttp\Exception\GuzzleException
   *   If the request fails.
   */
  protected function executeRequest(string $method, string $url, array $options = []): ResponseInterface {
    // Read only API key is used for GET requests. It is up to the
    // caller not no call non-idempotent methods if only the read_only
    // key is available. Missing API key throws LogicException.
    $key = match ($method) {
      'GET' => $this->library->get('read_only_api_key') ?: $this->library->get('api_key'),
      default => $this->library->get('api_key'),
    };

    if (empty($key)) {
      throw new \LogicException('The library has no API key.');
    }

    return $this->client->request($method, "https://video.bunnycdn.com$url", array_merge_recursive($options, [
      'headers' => [
        'AccessKey' => $key,
        'accept' => 'application/json',
      ],
    ]));
  }

}
