<?php

declare(strict_types=1);

namespace Drupal\bunny_stream\Bunny\DTO;

use Psr\Http\Message\ResponseInterface;

/**
 * Page of collections returned by the Bunny Stream API.
 *
 * @see https://bunny.net/docs/api-reference/stream/manage-collections/get-collection-list
 */
final readonly class BunnyCollectionList {

  /**
   * Constructs a new instance.
   *
   * @param int $totalItems
   *   Total number of collections matching the query.
   * @param int $currentPage
   *   The current page, 1-based.
   * @param int $itemsPerPage
   *   Number of collections per page.
   * @param \Drupal\bunny_stream\Bunny\DTO\BunnyCollection[] $items
   *   Collections on the current page.
   */
  public function __construct(
    public int $totalItems,
    public int $currentPage,
    public int $itemsPerPage,
    public array $items = [],
  ) {}

  /**
   * Creates the collection list from a Bunny Stream API response.
   *
   * @throws \InvalidArgumentException
   *   If the response is not a collection list.
   */
  public static function fromResponse(ResponseInterface $response): self {
    try {
      $data = json_decode((string) $response->getBody(), TRUE, flags: JSON_THROW_ON_ERROR);
    }
    catch (\JsonException $e) {
      throw new \InvalidArgumentException('Bunny Stream returned invalid JSON.', previous: $e);
    }

    if (!is_array($data) || !is_array($data['items'] ?? NULL)) {
      throw new \InvalidArgumentException('Bunny Stream did not return a collection list.');
    }

    return new self(
      totalItems: (int) ($data['totalItems'] ?? 0),
      currentPage: (int) ($data['currentPage'] ?? 1),
      itemsPerPage: (int) ($data['itemsPerPage'] ?? 0),
      items: array_map(BunnyCollection::fromArray(...), array_values($data['items'])),
    );
  }

}
