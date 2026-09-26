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

}
