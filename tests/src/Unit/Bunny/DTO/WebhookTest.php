<?php

declare(strict_types=1);

namespace Drupal\Tests\bunny_stream\Unit\Bunny\DTO;

use Drupal\Tests\UnitTestCase;
use Drupal\bunny_stream\Bunny\DTO\VideoStates;
use Drupal\bunny_stream\Bunny\DTO\Webhook;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\InputBag;

/**
 * Tests the webhook DTO.
 */
#[CoversClass(Webhook::class)]
#[Group('bunny_stream')]
final class WebhookTest extends UnitTestCase {

  /**
   * A valid payload is mapped to the DTO.
   */
  public function testFromPayload(): void {
    $webhook = Webhook::fromPayload(new InputBag([
      'VideoLibraryId' => 12345,
      'VideoGuid' => 'video-guid',
      'Status' => 3,
    ]));

    $this->assertSame(12345, $webhook->videoLibraryId);
    $this->assertSame('video-guid', $webhook->videoGuid);
    $this->assertSame(VideoStates::Finished, $webhook->status);
  }

  /**
   * Malformed payloads throw.
   *
   * @param array<string, mixed> $payload
   *   The webhook payload.
   */
  #[DataProvider('malformedPayloadProvider')]
  public function testMalformedPayload(array $payload): void {
    $this->expectException(\InvalidArgumentException::class);
    Webhook::fromPayload(new InputBag($payload));
  }

  /**
   * Data provider for testMalformedPayload().
   *
   * @return array<string, array{array<string, mixed>}>
   *   Payloads.
   */
  public static function malformedPayloadProvider(): array {
    $valid = ['VideoLibraryId' => 12345, 'VideoGuid' => 'video-guid', 'Status' => 3];

    return [
      'empty' => [[]],
      'missing library' => [['VideoGuid' => 'video-guid', 'Status' => 3]],
      'missing status' => [['VideoLibraryId' => 12345, 'VideoGuid' => 'video-guid']],
      'non-numeric library' => [['VideoLibraryId' => 'abc'] + $valid],
      'empty guid' => [['VideoGuid' => ''] + $valid],
      'non-string guid' => [['VideoGuid' => 123] + $valid],
      'unknown status' => [['Status' => 999] + $valid],
      'non-numeric status' => [['Status' => 'finished'] + $valid],
      'non-scalar guid' => [['VideoGuid' => ['video-guid']] + $valid],
    ];
  }

}
