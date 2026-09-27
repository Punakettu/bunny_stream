<?php

declare(strict_types=1);

namespace Drupal\Tests\bunny_stream\Unit;

use Drupal\bunny_stream\Entity\BunnyStreamLibrary;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the Bunny Stream library entity.
 */
#[CoversClass(BunnyStreamLibrary::class)]
#[Group('bunny_stream')]
final class BunnyStreamLibraryTest extends UnitTestCase {

  /**
   * The upload signature follows the Bunny TUS documentation.
   *
   * @see https://bunny.net/docs/stream/tus-resumable-uploads
   */
  public function testCreateUploadSignature(): void {
    $library = new BunnyStreamLibrary(['id' => '12345', 'api_key' => 'api-key'], 'bunny_stream_library');

    $this->assertTrue($library->isUploadAllowed());
    $this->assertSame(
      hash('sha256', '12345api-key1700000000video-guid'),
      $library->createUploadSignature('video-guid', 1700000000),
    );
  }

  /**
   * No upload signature is created without the read/write API key.
   */
  public function testCreateUploadSignatureWithoutApiKey(): void {
    $library = new BunnyStreamLibrary(['id' => '12345', 'read_only_api_key' => 'read-only'], 'bunny_stream_library');

    $this->assertFalse($library->isUploadAllowed());
    $this->expectException(\LogicException::class);
    $library->createUploadSignature('video-guid', 1700000000);
  }

  /**
   * The embed token is the SHA-256 of the key, the video and the expiry.
   */
  public function testCreateEmbedToken(): void {
    $library = new BunnyStreamLibrary(['id' => '12345', 'token_authentication_key' => 'token-key'], 'bunny_stream_library');

    $this->assertTrue($library->isTokenAuthenticationEnabled());
    $this->assertSame(
      hash('sha256', 'token-keyvideo-guid1700000000'),
      $library->createEmbedToken('video-guid', 1700000000),
    );
  }

  /**
   * No embed token is created without the token authentication key.
   */
  public function testCreateEmbedTokenWithoutKey(): void {
    $library = new BunnyStreamLibrary(['id' => '12345'], 'bunny_stream_library');

    $this->assertFalse($library->isTokenAuthenticationEnabled());
    $this->expectException(\LogicException::class);
    $library->createEmbedToken('video-guid', 1700000000);
  }

}
