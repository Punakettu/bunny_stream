<?php

namespace Drupal\bunny_stream;

use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Url;

/**
 * This class is used to enabled BigPipe with videos with expiration time.
 *
 * We can't cache videos with expiration time, to let's use
 * BigPipe to send the video with the expiration date.
 */
class LazyEmbedLoader implements TrustedCallbackInterface {

  /**
   * Constructs a LazyEmbedLoader object.
   *
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch $pageCacheKillSwitch
   *   The page_cache_kill_switch service.
   */
  public function __construct(
    protected KillSwitch $pageCacheKillSwitch,
  ) {}

  /**
   * Prepare the render array for the video.
   *
   * @param string $url
   *   Url of the video.
   * @param int $expires
   *   The time for the expiration of the video.
   * @param string $video_id
   *   The video id.
   * @param string $token_auth
   *   The token auth of bunny to generate the token.
   *  @param bool $fullscreen
   *    Indicated if video allow fullscreen.
   *
   * @return array
   *   Render array without cache.
   */
  public function lazyLoad(string $url, int $expires, string $video_id, string $token_auth, bool $fullscreen = TRUE): array {
    // @todo find better way to don't cache this response for anonymous
    // without this kill switch, just works for authenticated users.
    $this->pageCacheKillSwitch->trigger();

    $expires += time();

    $security_token = hash("sha256", $token_auth . $video_id . $expires);

    $settings = [];
    $settings['token'] = $security_token;
    $settings['expires'] = $expires;

    return [
      '#theme' => "bunny_embed",
      '#url' => Url::fromUri($url, ['query' => $settings]),
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
