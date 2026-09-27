<?php

declare(strict_types=1);

namespace Drupal\Tests\bunny_stream\Unit\Bunny\DTO;

use Drupal\Tests\UnitTestCase;
use Drupal\Tests\bunny_stream\Traits\ApiFixtureTrait;
use Drupal\bunny_stream\Bunny\DTO\BunnyCollection;
use Drupal\bunny_stream\Bunny\DTO\BunnyCollectionList;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the collection DTOs.
 */
#[CoversClass(BunnyCollection::class)]
#[CoversClass(BunnyCollectionList::class)]
#[Group('bunny_stream')]
final class BunnyCollectionTest extends UnitTestCase {

  use ApiFixtureTrait;

  /**
   * Null fields of a created collection get defaults.
   */
  public function testFromResponseDefaults(): void {
    $collection = BunnyCollection::fromResponse($this->getFixtureResponse('createCollection'));

    $this->assertSame(12345, $collection->videoLibraryId);
    $this->assertSame('collection-guid', $collection->guid);
    $this->assertSame('Kissat', $collection->name);
    $this->assertSame('', $collection->previewVideoIds);
    $this->assertSame([], $collection->previewImageUrls);
  }

  /**
   * Responses that are not collections throw.
   */
  #[DataProvider('invalidResponseProvider')]
  public function testFromResponseInvalid(string $body): void {
    $this->expectException(\InvalidArgumentException::class);
    BunnyCollection::fromResponse(new Response(200, [], $body));
  }

  /**
   * Data provider for testFromResponseInvalid().
   *
   * @return array<string, array{string}>
   *   Response bodies.
   */
  public static function invalidResponseProvider(): array {
    return [
      'invalid json' => ['{'],
      'not an object' => ['"collection"'],
      'missing guid' => ['{"videoLibraryId":12345}'],
      'missing library' => ['{"guid":"collection-guid"}'],
    ];
  }

  /**
   * Collection lists contain the collections of the page.
   */
  public function testListFromResponse(): void {
    $list = BunnyCollectionList::fromResponse($this->getFixtureResponse('listCollections'));

    $this->assertSame(2, $list->totalItems);
    $this->assertSame(['collection-guid', 'other-collection-guid'], array_map(static fn (BunnyCollection $collection) => $collection->guid, $list->items));
  }

  /**
   * Lists without items or with invalid items throw.
   */
  #[DataProvider('invalidListResponseProvider')]
  public function testListFromResponseInvalid(string $body): void {
    $this->expectException(\InvalidArgumentException::class);
    BunnyCollectionList::fromResponse(new Response(200, [], $body));
  }

  /**
   * Data provider for testListFromResponseInvalid().
   *
   * @return array<string, array{string}>
   *   Response bodies.
   */
  public static function invalidListResponseProvider(): array {
    return [
      'invalid json' => ['{'],
      'missing items' => ['{"totalItems":0}'],
      'invalid item' => ['{"items":[{"name":"No guid"}]}'],
    ];
  }

}
