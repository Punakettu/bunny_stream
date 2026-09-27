<?php

declare(strict_types=1);

namespace Drupal\bunny_stream;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\PrivateKey;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\media\MediaInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Generates and validates embed endpoint URLs.
 *
 * @see \Drupal\bunny_stream\Controller\EmbedController
 */
final class EmbedUrlGenerator {

  /**
   * Bunny player parameters.
   */
  public const array PLAYER_OPTIONS = ['responsive', 'autoplay', 'loop', 'muted', 'preload'];

  /**
   * Option for whether the video can be shown in fullscreen.
   */
  public const string FULLSCREEN_OPTION = 'fullscreen';

  /**
   * Option for how long the token is valid, in seconds.
   */
  public const string LIFETIME_OPTION = 'lifetime';

  public function __construct(
    private readonly PrivateKey $privateKey,
  ) {}

  /**
   * Gets the signed embed endpoint URL of the media.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The saved media.
   * @param array<string, string> $options
   *   The embed options, keyed by the option constants of this class.
   */
  public function getUrl(MediaInterface $media, array $options): Url {
    $options = $this->filterOptions($options);
    $options['hash'] = $this->hash($media, $options);

    return Url::fromRoute('bunny_stream.embed', ['media' => $media->id()], ['query' => $options]);
  }

  /**
   * Gets the embed options of the request.
   *
   * @return array<string, string>
   *   The embed options, keyed by the option constants of this class.
   */
  public function getOptions(Request $request): array {
    return $this->filterOptions($request->query->all());
  }

  /**
   * Whether the request has a valid signature for the media.
   */
  public function isValid(MediaInterface $media, Request $request): bool {
    $hash = $request->query->get('hash');
    return is_string($hash) && hash_equals($this->hash($media, $this->getOptions($request)), $hash);
  }

  /**
   * Keeps only the known options as strings, sorted by key.
   *
   * @param array<string, mixed> $options
   *   The options to filter.
   *
   * @return array<string, string>
   *   The filtered options.
   */
  private function filterOptions(array $options): array {
    $keys = [...self::PLAYER_OPTIONS, self::FULLSCREEN_OPTION, self::LIFETIME_OPTION];
    $options = array_filter(
      array_intersect_key($options, array_flip($keys)),
      is_scalar(...),
    );
    ksort($options);
    return array_map(strval(...), $options);
  }

  /**
   * Computes the signature of the options for the media.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media.
   * @param array<string, string> $options
   *   The filtered options.
   */
  private function hash(MediaInterface $media, array $options): string {
    return Crypt::hmacBase64(
      $media->id() . ':' . UrlHelper::buildQuery($options),
      $this->privateKey->get() . Settings::getHashSalt(),
    );
  }

}
