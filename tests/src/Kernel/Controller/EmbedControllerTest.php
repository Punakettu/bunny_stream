<?php

declare(strict_types=1);

namespace Drupal\Tests\bunny_stream\Kernel;

use Drupal\bunny_stream\Controller\EmbedController;
use Drupal\bunny_stream\EmbedUrlGenerator;
use Drupal\bunny_stream\Plugin\Field\FieldFormatter\BunnyStreamEmbedFormatter;
use Drupal\bunny_stream\Plugin\media\Source\BunnyStreamSource;
use Drupal\Component\Utility\Html;
use Drupal\Core\Render\RendererInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Drupal\Tests\bunny_stream\Traits\BunnyMediaTestTrait;
use Drupal\Tests\bunny_stream\Traits\HttpKernelTestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests the embed formatter and the embed endpoint.
 */
#[CoversClass(EmbedController::class)]
#[CoversClass(EmbedUrlGenerator::class)]
#[CoversClass(BunnyStreamEmbedFormatter::class)]
#[Group('bunny_stream')]
#[RunTestsInSeparateProcesses]
final class EmbedControllerTest extends KernelTestBase {

  use BunnyMediaTestTrait;
  use HttpKernelTestTrait;
  use UserCreationTrait;

  /**
   * GUID of the test video.
   */
  private const string VIDEO_ID = 'b4a4c1e0-5f3d-4c6e-9f8b-1a2b3c4d5e6f';

  /**
   * Test token authentication key.
   */
  private const string TOKEN_KEY = '44444444-4444-4444-8444-444444444444';

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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->setUpBunnyMedia();

    $source = $this->mediaType->getSource();
    assert($source instanceof BunnyStreamSource);
    $source->getLibrary()?->set('token_authentication_key', self::TOKEN_KEY)->save();

    $this->media = Media::create([
      'bundle' => $this->mediaType->id(),
      'name' => 'My video',
      $this->getSourceFieldName() => [
        'value' => self::VIDEO_ID,
        'width' => 720,
        'height' => 1280,
      ],
    ]);
    $this->media->save();
  }

  /**
   * The formatter renders a placeholder that loads a signed embed.
   */
  public function testEmbed(): void {
    $this->setUpCurrentUser(permissions: ['view media']);

    $html = $this->renderFormatter(['time' => 3600, 'autoplay' => TRUE]);
    $this->assertStringNotContainsString(self::TOKEN_KEY, $html);
    $this->assertStringNotContainsString('<iframe', $html);
    $this->assertStringContainsString('data-hx-trigger="revealed"', $html);
    $this->assertStringContainsString('aspect-ratio: 720 / 1280;', $html);

    $response = $this->processRequest(Request::create($this->getEmbedUrl($html)));
    $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string) $response->getContent());
    $this->assertStringContainsString('no-cache', (string) $response->headers->get('Cache-Control'));

    $this->assertStringContainsString('aspect-ratio: 720 / 1280;', (string) $response->getContent());
    $src = $this->getIframeSrc((string) $response->getContent());
    $this->assertStringStartsWith('//iframe.mediadelivery.net/embed/' . self::LIBRARY_ID . '/' . self::VIDEO_ID . '?', $src);
    parse_str((string) parse_url($src, PHP_URL_QUERY), $query);
    $this->assertSame('true', $query['autoplay']);
    $this->assertEqualsWithDelta(\Drupal::time()->getRequestTime() + 3600, (int) $query['expires'], 60);
    $this->assertSame(hash('sha256', self::TOKEN_KEY . self::VIDEO_ID . $query['expires']), $query['token']);
    $this->assertStringNotContainsString(self::TOKEN_KEY, (string) $response->getContent());
  }

  /**
   * Changed options are rejected.
   */
  public function testTamperedUrl(): void {
    $this->setUpCurrentUser(permissions: ['view media']);
    $url = $this->getEmbedUrl($this->renderFormatter(['time' => 3600]));

    $tampered = str_replace('lifetime=3600', 'lifetime=999999999', $url);
    $this->assertNotSame($url, $tampered);
    $this->assertSame(Response::HTTP_FORBIDDEN, $this->processRequest(Request::create($tampered))->getStatusCode());

    $unsigned = preg_replace('/[?&]hash=[^&]*/', '', $url);
    $this->assertSame(Response::HTTP_FORBIDDEN, $this->processRequest(Request::create((string) $unsigned))->getStatusCode());
  }

  /**
   * The endpoint requires view access to the media.
   */
  public function testAccessDenied(): void {
    $this->setUpCurrentUser(permissions: ['view media']);
    $url = $this->getEmbedUrl($this->renderFormatter());

    $this->setUpCurrentUser();
    $this->assertSame(Response::HTTP_FORBIDDEN, $this->processRequest(Request::create($url))->getStatusCode());
  }

  /**
   * Libraries without token authentication are embedded directly.
   */
  public function testPublicLibrary(): void {
    $this->setUpCurrentUser(permissions: ['view media']);
    $url = $this->getEmbedUrl($this->renderFormatter());

    // The source keeps the library it loaded, so change that object.
    $source = $this->media->getSource();
    assert($source instanceof BunnyStreamSource);
    $source->getLibrary()?->set('token_authentication_key', '')->save();

    $html = $this->renderFormatter();
    $this->assertStringNotContainsString('data-hx-get', $html);
    $this->assertStringContainsString('aspect-ratio: 720 / 1280;', $html);
    $this->assertStringStartsWith('//iframe.mediadelivery.net/embed/' . self::LIBRARY_ID . '/' . self::VIDEO_ID, $this->getIframeSrc($html));
    $this->assertSame(Response::HTTP_FORBIDDEN, $this->processRequest(Request::create($url))->getStatusCode());
  }

  /**
   * Renders the source field of the test media with the embed formatter.
   *
   * @param array<string, mixed> $settings
   *   The formatter settings.
   */
  private function renderFormatter(array $settings = []): string {
    $build = $this->media->get($this->getSourceFieldName())->view([
      'type' => 'bunny_stream_embed',
      'label' => 'hidden',
      'settings' => $settings,
    ]);
    return (string) $this->container->get(RendererInterface::class)->renderInIsolation($build);
  }

  /**
   * Gets the embed endpoint URL from the rendered placeholder.
   */
  private function getEmbedUrl(string $html): string {
    $element = (new \DOMXPath(Html::load($html)))->query('//*[@data-hx-get]')->item(0);
    $url = $element instanceof \DOMElement ? $element->getAttribute('data-hx-get') : NULL;
    $this->assertNotEmpty($url);
    return (string) $url;
  }

  /**
   * Gets the iframe source from the rendered embed.
   */
  private function getIframeSrc(string $html): string {
    $iframe = Html::load($html)->getElementsByTagName('iframe')->item(0);
    $this->assertNotNull($iframe);
    return $iframe->getAttribute('src');
  }

}
