<?php

namespace Drupal\bunny_stream;

use GuzzleHttp\Client;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Execute request to Bunny.net API to manage videos.
 *
 * For more information about the API check the documentation:
 * https://docs.bunny.net/reference/api-overview.
 */
class VideoManager {

  /**
   * Domain of bunny API.
   */
  public const DOMAIN = "https://video.bunnycdn.com";

  /**
   * Get Video, GET.
   */
  public const GET = "/library/{libraryId}/videos/{videoId}";

  /**
   * Serializer for videos.
   */
  protected Serializer $serializer;

  /**
   * Constructor of the class.
   *
   * @param \GuzzleHttp\Client $client
   *   The http client to execute the requests.
   * @param int $library_id
   *   Library ID of Bunny Stream.
   * @param string $api_key
   *   API key for the request to bunny.net.
   */
  public function __construct(
    protected Client $client,
    protected int $library_id,
    protected string $api_key
  ) {}

  /**
   * Loads video information from bunny.net API.
   *
   * @param string $video_id
   *   The video to load.
   *
   * @return ?\Drupal\bunny_stream\BunnyVideo
   *   Loaded video.
   */
  public function getVideo(string $video_id): ?BunnyVideo {
    $url = $this->generateUrl(self::GET, ["{libraryId}" => $this->library_id, "{videoId}" => $video_id]);

    try {
      $response = $this->executeRequest('GET', $url);

      if ($response->getStatusCode() === 200) {

        $body = $response->getBody();

        /** @var \Drupal\bunny_stream\BunnyVideo $bunnyVideo */
        $bunnyVideo = $this->getSerializer()->deserialize($body, BunnyVideo::class, 'json');

        return $bunnyVideo;
      }
    }
    catch (\Exception $e) {
      // Log the error here.
    }

    return NULL;
  }

  /**
   * Execute the request to Bunny.net API.
   *
   * @param string $method
   *   The method for the request.
   * @param string $url
   *   The URL where execute the request.
   * @param array $options
   *   Different options for the url, like headers or query parameters.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   Response of the request.
   */
  protected function executeRequest(string $method, string $url, array $options = []): object {
    return $this->client->request($method, self::DOMAIN . $url, [
      'headers' => [
        'AccessKey' => $this->api_key,
        'accept' => 'application/json',
      ],
    ]);
  }

  /**
   * Replace the given values on the url to generate the correct URL.
   *
   * @param string $url
   *   Url to replace with the values.
   * @param array $values
   *   Values to replace in the url.
   *
   * @return string
   *   The url with the values replaced.
   */
  protected function generateUrl(string $url, array $values): string {
    return strtr(
      $url,
      $values
    );
  }

  /**
   * Loads the serializer for the class.
   *
   * @return \Symfony\Component\Serializer\Serializer
   *   The serializer for the class.
   */
  protected function getSerializer() {

    if (!isset($this->serializer)) {
      $encoders = [new JsonEncoder()];
      $normalizers = [new ObjectNormalizer()];

      $this->serializer = new Serializer($normalizers, $encoders);
    }

    return $this->serializer;
  }

}
