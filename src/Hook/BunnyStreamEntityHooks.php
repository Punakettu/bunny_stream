<?php

declare(strict_types=1);

namespace Drupal\bunny_stream\Hook;

use Drupal\bunny_stream\Bunny\BunnyException;
use Drupal\bunny_stream\BunnyStreamManagerFactoryInterface;
use Drupal\bunny_stream\BunnyStreamSourceInterface;
use Drupal\bunny_stream\Plugin\Field\FieldType\BunnyStreamVideoItem;
use Drupal\bunny_stream\Plugin\media\Source\BunnyStreamSource;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\media\MediaInterface;

/**
 * Entity hooks for bunny stream.
 */
final readonly class BunnyStreamEntityHooks {

  public function __construct(
    private BunnyStreamManagerFactoryInterface $bunnyStreamManagerFactory,
    private LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * Implements hook_media_presave().
   *
   * Creates the Bunny video of a pending upload. This runs only once the media
   * has passed validation, so abandoned forms leave nothing behind in Bunny.
   *
   * Stores the video metadata when the video changes, or when it is missing
   * because Bunny had not processed the video yet.
   */
  #[Hook('media_presave')]
  public function mediaPresave(MediaInterface $media): void {
    $source = $media->getSource();
    if (!$source instanceof BunnyStreamSource) {
      return;
    }

    if ($source->getSourceFieldValue($media) === BunnyStreamSourceInterface::PENDING_UPLOAD) {
      $this->createVideo($media, $source);
    }
    else {
      foreach (array_keys($media->getTranslationLanguages()) as $langcode) {
        $translation = $media->getTranslation($langcode);
        if ($this->needsVideoMetadata($translation, $source)) {
          $source->updateVideoMetadata($translation);
        }
      }
    }
  }

  /**
   * Whether the stored video metadata of the media is missing or outdated.
   */
  private function needsVideoMetadata(MediaInterface $translation, BunnyStreamSource $source): bool {
    $field_name = $source->getConfiguration()['source_field'];
    $item = $translation->get($field_name)->first();
    if (!$item instanceof BunnyStreamVideoItem || $item->isEmpty()) {
      return FALSE;
    }
    if (!$item->hasVideoMetadata()) {
      return TRUE;
    }

    // Metadata given along with a new video is trusted.
    $langcode = $translation->language()->getId();
    $original = $translation->getOriginal();
    return $original?->hasTranslation($langcode)
      && $original->getTranslation($langcode)->get($field_name)->value !== $item->value;
  }

  /**
   * Creates the Bunny video of a pending upload.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   If the video could not be created.
   */
  private function createVideo(MediaInterface $media, BunnyStreamSource $source): void {
    $library = $source->getLibrary();
    $video_manager = $library ? $this->bunnyStreamManagerFactory->getVideoManager((string) $library->id()) : NULL;

    try {
      if (!$video_manager) {
        throw new \RuntimeException('The media type has no Bunny Stream library.');
      }
      $video = $video_manager->createVideo((string) $media->label());
    }
    catch (\RuntimeException | BunnyException $e) {
      $this->loggerFactory->get('bunny_stream')->error('Could not create a Bunny video for media %label: @message', [
        '%label' => $media->label(),
        '@message' => $e->getMessage(),
      ]);
      throw new EntityStorageException('Could not create the Bunny Stream video.', previous: $e);
    }

    $media->set($source->getConfiguration()['source_field'], $video->guid);
    $source->updateVideoMetadata($media, $video);
  }

}
