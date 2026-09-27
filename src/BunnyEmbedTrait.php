<?php

declare(strict_types=1);

namespace Drupal\bunny_stream;

use Drupal\bunny_stream\Plugin\Field\FieldType\BunnyStreamVideoItem;
use Drupal\Core\Url;
use Drupal\media\MediaInterface;

/**
 * Builds the Bunny player iframe of a video.
 */
trait BunnyEmbedTrait {

  /**
   * Builds the render array of the Bunny player iframe.
   *
   * @param \Drupal\bunny_stream\BunnyStreamLibraryInterface $library
   *   The library of the video.
   * @param \Drupal\media\MediaInterface $media
   *   The media of the video.
   * @param \Drupal\bunny_stream\Plugin\Field\FieldType\BunnyStreamVideoItem $item
   *   The video.
   * @param array<string, string|int> $query
   *   The player parameters of the embed URL.
   * @param bool $allowFullscreen
   *   Whether the video can be shown in fullscreen.
   *
   * @return array<string, mixed>
   *   The render array, without cacheability metadata.
   */
  protected function buildBunnyEmbed(BunnyStreamLibraryInterface $library, MediaInterface $media, BunnyStreamVideoItem $item, array $query, bool $allowFullscreen): array {
    $url = Url::fromUri(
      sprintf('//iframe.mediadelivery.net/embed/%s/%s', $library->id(), $item->value),
      ['query' => $query],
    );

    return [
      '#theme' => 'bunny_embed',
      '#url' => $url->toString(),
      '#title' => $media->label(),
      '#width' => $item->width,
      '#height' => $item->height,
      '#options' => ['allow_fullscreen' => $allowFullscreen],
    ];
  }

}
