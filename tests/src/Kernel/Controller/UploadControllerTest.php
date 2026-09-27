<?php

declare(strict_types=1);

namespace Drupal\Tests\bunny_stream\Kernel\Controller;

use Drupal\bunny_stream\Controller\UploadController;
use Drupal\bunny_stream\Bunny\DTO\VideoStatus;
use Drupal\Core\Access\CsrfRequestHeaderAccessCheck;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Drupal\Tests\bunny_stream\Traits\BunnyMediaTestTrait;
use Drupal\Tests\bunny_stream\Traits\HttpClientMockTrait;
use Drupal\Tests\bunny_stream\Traits\HttpKernelTestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests the upload page and TUS signature endpoint.
 */
#[CoversClass(UploadController::class)]
#[Group('bunny_stream')]
#[RunTestsInSeparateProcesses]
final class UploadControllerTest extends KernelTestBase {

  use BunnyMediaTestTrait;
  use HttpClientMockTrait;
  use HttpKernelTestTrait;
  use UserCreationTrait;

  /**
   * GUID of the test video.
   */
  private const string VIDEO_ID = 'b4a4c1e0-5f3d-4c6e-9f8b-1a2b3c4d5e6f';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'bunny_stream',
    'field',
    'file',
    'image',
    'media',
    'system',
    'user',
  ];

  /**
   * The test media.
   */
  private MediaInterface $media;

  /**
   * CSRF Token generator.
   */
  private CsrfTokenGenerator $tokenGenerator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->mockHttpClient();
    $this->setUpBunnyMedia();

    $this->tokenGenerator = $this->container->get(CsrfTokenGenerator::class);
    $this->media = Media::create([
      'bundle' => $this->mediaType->id(),
      'name' => 'My video',
      $this->getSourceFieldName() => self::VIDEO_ID,
    ]);
    $this->media->save();
  }

  /**
   * The signature endpoint signs the upload URL.
   */
  public function testSignature(): void {
    $this->setUpCurrentUser(permissions: ['update any media']);

    $response = $this->post('bunny_stream.media_upload_signature',
      headers: ['X-CSRF-Token' => $this->tokenGenerator->get(CsrfRequestHeaderAccessCheck::TOKEN_KEY)],
      parameters: ['media' => $this->media->id()],
    );
    $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string) $response->getContent());

    $data = json_decode((string) $response->getContent(), TRUE, flags: JSON_THROW_ON_ERROR);
    $this->assertSame(self::LIBRARY_ID, $data['libraryId']);
    $this->assertSame(self::VIDEO_ID, $data['videoId']);
    $this->assertSame('My video', $data['title']);
    $this->assertGreaterThanOrEqual(\Drupal::time()->getRequestTime() + 3600, $data['expire']);
    $this->assertSame(hash('sha256', self::LIBRARY_ID . self::API_KEY . $data['expire'] . self::VIDEO_ID), $data['signature']);
  }

  /**
   * The signature endpoint requires update access to the media.
   */
  public function testSignatureAccessDenied(): void {
    $response = $this->post('bunny_stream.media_upload_signature',
      headers: ['X-CSRF-Token' => $this->tokenGenerator->get(CsrfRequestHeaderAccessCheck::TOKEN_KEY)],
      parameters: ['media' => $this->media->id()],
    );
    $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
  }

  /**
   * The upload page and signature are denied when uploads are disallowed.
   */
  public function testUploadNotAllowed(): void {
    $this->disallowUploads();
    $this->setUpCurrentUser(permissions: ['update any media']);

    $response = $this->post('bunny_stream.media_upload_signature',
      headers: ['X-CSRF-Token' => $this->tokenGenerator->get(CsrfRequestHeaderAccessCheck::TOKEN_KEY)],
      parameters: ['media' => $this->media->id()],
    );

    $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    $this->assertSame(Response::HTTP_FORBIDDEN, $this->get('entity.media.bunny_stream_upload', ['media' => $this->media->id()]));
  }

  /**
   * The upload page is shown only while the video accepts a file.
   */
  public function testPage(): void {
    $this->setUpCurrentUser(permissions: ['update any media']);
    $this->mockHttpClient(
      // Status is created.
      new GuzzleResponse(200, [], json_encode([
        'videoLibraryId' => self::LIBRARY_ID,
        'guid' => self::VIDEO_ID,
        'title' => 'My video',
        'status' => VideoStatus::Created->value,
      ], JSON_THROW_ON_ERROR)),
      // Status is finished.
      new GuzzleResponse(200, [], json_encode([
        'videoLibraryId' => self::LIBRARY_ID,
        'guid' => self::VIDEO_ID,
        'title' => 'My video',
        'status' => VideoStatus::Finished->value,
      ], JSON_THROW_ON_ERROR))
    );

    // File input is displayed.
    $response = $this->get('entity.media.bunny_stream_upload', ['media' => $this->media->id()]);
    $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    $this->assertStringContainsString('bunny-stream-upload__file', (string) $response->getContent());

    // No file input.
    $response = $this->get('entity.media.bunny_stream_upload', ['media' => $this->media->id()]);
    $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    $this->assertStringNotContainsString('bunny-stream-upload__file', (string) $response->getContent());
    $this->assertStringContainsString('has already been uploaded', (string) $response->getContent());
  }

}
