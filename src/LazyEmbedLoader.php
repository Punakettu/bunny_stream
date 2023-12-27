<?php

namespace Drupal\bunny_stream;

use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * This class is used to enabled BigPipe with videos with expiration time.
 *
 * We can't cache videos with expiration time, to let's use
 * BigPipe to send the video with the expiration date.
 */
class LazyEmbedLoader implements TrustedCallbackInterface {

  /**
   * Prepare the render array for the video.
   *
   * @param string $url
   *   Url of the video.
   * @param bool $fullscreen
   *   Indicated if video allow fullscreen.
   *
   * @return array
   *   Render array without cache.
   */
  public static function lazyLoad(string $url, bool $fullscreen = TRUE): array {
    return [
      '#theme' => "bunny_embed",
      '#url' => $url,
      '#options' => ['allow_fullscreen' => $fullscreen],
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * {@inheritDoc}
   */
  public static function trustedCallbacks(): array {
    return [
      'lazyLoad',
    ];
  }

}
