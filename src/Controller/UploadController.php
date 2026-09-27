<?php

declare(strict_types=1);

namespace Drupal\bunny_stream\Controller;

use Drupal\bunny_stream\Bunny\BunnyException;
use Drupal\bunny_stream\BunnyStreamManagerFactoryInterface;
use Drupal\bunny_stream\Plugin\media\Source\BunnyStreamSource;
use Drupal\bunny_stream\Bunny\VideoManager;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\media\MediaInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Upload page for Bunny Stream video.
 */
final class UploadController extends ControllerBase {

  /**
   * How many seconds an upload signature is valid.
   */
  private const int SIGNATURE_LIFETIME = 86400;

  public function __construct(
    private readonly BunnyStreamManagerFactoryInterface $bunnyStreamManagerFactory,
    private readonly TimeInterface $time,
  ) {}

  /**
   * Checks that the media allows uploads.
   */
  public function access(MediaInterface $media): AccessResultInterface {
    $source = $media->getSource();
    $library = $source instanceof BunnyStreamSource ? $source->getLibrary() : NULL;
    $result = AccessResult::allowedIf($library?->isUploadAllowed())
      ->addCacheableDependency($media)
      ->addCacheableDependency($media->getBundleEntity());

    if ($library) {
      $result->addCacheableDependency($library);
    }

    return $result;
  }

  /**
   * Renders the upload page.
   */
  public function page(MediaInterface $media): array {
    $build = [
      '#cache' => ['max-age' => 0],
    ];

    $source = $media->getSource();
    $videoId = $source->getSourceFieldValue($media);
    $library = $source instanceof BunnyStreamSource ? $source->getLibrary() : NULL;
    $videoManager = $library ? $this->bunnyStreamManagerFactory->getVideoManager((string) $library->id()) : NULL;

    try {
      $video = $videoId ? $videoManager?->getVideo($videoId) : NULL;
    }
    catch (BunnyException) {
      $build['message'] = [
        '#theme' => 'status_messages',
        '#message_list' => ['error' => [$this->t('The video could not be loaded from Bunny Stream.')]],
      ];
      return $build;
    }

    if (!$video || !$video->status->isUploadable()) {
      $build['message'] = [
        '#markup' => $this->t('The file of %label has already been uploaded. <a href=":url">Back to the media</a>.', [
          '%label' => $media->label(),
          ':url' => $media->toUrl('edit-form')->toString(),
        ]),
      ];
      return $build;
    }

    $build['description'] = [
      '#markup' => $this->t('Choose the video file for %label. The file is uploaded directly to Bunny Stream. Keep this page open until the upload is complete. An interrupted upload continues when you choose the same file again.', [
        '%label' => $media->label(),
      ]),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
    $build['upload'] = [
      '#type' => 'bunny_stream_upload',
      '#media' => $media,
    ];

    return $build;
  }

  /**
   * Returns a TUS upload signature for the Bunny video of the media.
   */
  public function signature(MediaInterface $media): JsonResponse {
    $source = $media->getSource();
    $videoId = $source->getSourceFieldValue($media) ?: '';
    $library = $source instanceof BunnyStreamSource ? $source->getLibrary() : NULL;
    $expire = $this->time->getRequestTime() + self::SIGNATURE_LIFETIME;

    if (!$library) {
      return new JsonResponse(['message' => 'The media type has no Bunny Stream library.'], 500);
    }

    return new JsonResponse([
      'endpoint' => VideoManager::TUS_ENDPOINT,
      'libraryId' => (int) $library->id(),
      'videoId' => $videoId,
      'expire' => $expire,
      'signature' => $library->createUploadSignature($videoId, $expire),
      'title' => (string) $media->label(),
    ]);
  }

}
