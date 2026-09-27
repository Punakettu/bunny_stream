<?php

declare(strict_types=1);

namespace Drupal\bunny_stream\Bunny\DTO;

use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\InputBag;

/**
 * Webhook payload sent by Bunny Stream.
 *
 * @see https://docs.bunny.net/docs/stream-webhook
 */
final readonly class Webhook {

  public function __construct(
    public int $videoLibraryId,
    public string $videoGuid,
    public VideoStates $status,
  ) {}

  /**
   * Creates the webhook from the request payload.
   *
   * @param \Symfony\Component\HttpFoundation\InputBag<string|int|float|bool> $payload
   *   The request payload.
   *
   * @throws \InvalidArgumentException
   *   If the payload is malformed.
   */
  public static function fromPayload(InputBag $payload): self {
    try {
      $library_id = $payload->filter('VideoLibraryId', filter: FILTER_VALIDATE_INT);
      $guid = $payload->get('VideoGuid');
      $status = VideoStates::from($payload->filter('Status', filter: FILTER_VALIDATE_INT));
    }
    catch (BadRequestException | \ValueError $e) {
      throw new \InvalidArgumentException('Malformed Bunny Stream webhook payload.', previous: $e);
    }

    if (!is_string($guid) || $guid === '') {
      throw new \InvalidArgumentException('Malformed Bunny Stream webhook payload.');
    }

    return new self($library_id, $guid, $status);
  }

}
