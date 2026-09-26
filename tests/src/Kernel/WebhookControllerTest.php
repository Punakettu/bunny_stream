<?php

declare(strict_types=1);

namespace Drupal\Tests\bunny_stream\Kernel;

use Drupal\bunny_stream\BunnyStreamLibraryInterface;
use Drupal\bunny_stream\Controller\WebhookController;
use Drupal\bunny_stream\Entity\BunnyStreamLibrary;
use Drupal\bunny_stream\Event\WebhookEvent;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\bunny_stream\Traits\HttpKernelTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests posting to the webhook endpoint.
 */
#[CoversClass(WebhookController::class)]
#[Group('bunny_stream')]
#[RunTestsInSeparateProcesses]
final class WebhookControllerTest extends KernelTestBase {

  use HttpKernelTestTrait;

  /**
   * Test library id
   */
  private const int LIBRARY_ID = 12345;

  /**
   * Test read only api key. The HMAC signature is calculated from this value.
   */
  private const string READ_ONLY_API_KEY = '11111111-1111-4111-8111-111111111111';

  /**
   * Captured events.
   *
   * @var list<WebhookEvent>
   */
  private array $events = [];

  /**
   * Library entity.
   */
  private BunnyStreamLibraryInterface $library;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'bunny_stream',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container->get(EventDispatcherInterface::class)->addListener(
      WebhookEvent::class,
      fn (WebhookEvent $event) => $this->events[] = $event,
    );

    BunnyStreamLibrary::create([
      'id' => self::LIBRARY_ID,
      'label' => 'Test library',
      'api_key' => '22222222-2222-4222-8222-222222222222',
      'cdn_hostname' => 'vz-12345.b-cdn.net',
      'read_only_api_key' => self::READ_ONLY_API_KEY,
    ])->save();
  }

  /**
   * A signed request dispatches the event.
   */
  public function testEventDispatched(): void {
    $payload = self::payload();
    $response = $this->post('bunny_stream.webhook', $payload, [
      'X-BunnyStream-Signature' => self::sign($payload, self::READ_ONLY_API_KEY),
    ]);

    $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    $this->assertSame([self::payload()], array_map(
      static fn (WebhookEvent $event) => $event->getPayload(),
      $this->events
    ));
  }

  /**
   * Missing or invalid signatures are rejected.
   *
   * @param array<string, int|string> $payload
   *   The webhook payload.
   * @param array<string, string> $headers
   *   Request headers.
   */
  #[DataProvider('invalidSignatureProvider')]
  public function testInvalidSignature(array $payload, array $headers): void {
    // Libraries without API key reject all requests.
    BunnyStreamLibrary::create([
      'id' => '23456',
      'label' => 'Test library',
      'api_key' => '22222222-2222-4222-8222-222222222222',
      'cdn_hostname' => 'vz-12345.b-cdn.net',
      'read_only_api_key' => NULL,
    ])->save();

    $response = $this->post('bunny_stream.webhook', $payload, $headers);
    $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    $this->assertEmpty($this->events);
  }

  /**
   * Data provider for testInvalidSignature().
   *
   * @return array<string, array{array<string, int|string>, array<string, string>}>
   *   Payloads and headers.
   */
  public static function invalidSignatureProvider(): array {
    return [
      'missing signature' => [self::payload(), []],
      'invalid signature' => [
        self::payload(),
        ['X-BunnyStream-Signature' => 'invalid'],
      ],
      'library without API key' => [
        self::payload(23456),
        ['X-BunnyStream-Signature' => self::sign(self::payload(23456), '')],
      ],
    ];
  }

  /**
   * Signs the example payload.
   *
   * @phpstan-param array<string, int|string> $payload
   */
  private static function sign(array $payload, string $key): string {
    return hash_hmac('sha256', json_encode($payload, JSON_THROW_ON_ERROR), $key);
  }

  /**
   * An example webhook payload.
   *
   * @return array<string, int|string>
   *   The payload.
   */
  private static function payload(int $id = self::LIBRARY_ID): array {
    return [
      'VideoLibraryId' => $id,
      'VideoGuid' => 'b4a4c1e0-5f3d-4c6e-9f8b-1a2b3c4d5e6f',
      'Status' => 3,
    ];
  }

}
