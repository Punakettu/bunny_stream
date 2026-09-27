<?php

declare(strict_types=1);

namespace Drupal\bunny_stream;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a bunny_stream_library entity type.
 */
interface BunnyStreamLibraryInterface extends ConfigEntityInterface {

  /**
   * Verifies that payload is signed for this library.
   *
   * @see https://bunny.net/docs/stream/webhooks#signature-validation
   */
  public function validateSignature(string $signature, string $version, string $algorithm, string $content): bool;

  /**
   * Whether videos can be created and uploaded from Drupal.
   *
   * @return bool
   *   TRUE if uploads are enabled and the read/write API key is set.
   */
  public function isUploadAllowed(): bool;

  /**
   * Generates the signature for upload of the given video.
   *
   * @param string $video_id
   *   GUID of the video the file is uploaded to.
   * @param int $expire
   *   UNIX timestamp when the signature expires.
   *
   * @return string
   *   The signature for the AuthorizationSignature header.
   *
   * @throws \LogicException
   *   If uploads are not allowed for the library.
   *
   * @see https://bunny.net/docs/stream/tus-resumable-uploads
   */
  public function createUploadSignature(string $video_id, int $expire): string;

  /**
   * Whether embeds of this library need a token.
   *
   * @return bool
   *   TRUE if the token authentication key is set.
   */
  public function isTokenAuthenticationEnabled(): bool;

  /**
   * Generates the embed view token of the given video.
   *
   * @param string $video_id
   *   GUID of the embedded video.
   * @param int $expires
   *   UNIX timestamp when the token expires.
   *
   * @return string
   *   The token for the embed URL.
   *
   * @throws \LogicException
   *   If token authentication is not enabled for the library.
   */
  public function createEmbedToken(string $video_id, int $expires): string;

}
