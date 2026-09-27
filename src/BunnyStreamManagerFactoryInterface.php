<?php

namespace Drupal\bunny_stream;

use Drupal\bunny_stream\Bunny\VideoManager;

/**
 * Interface for the service bunny_stream.manager.
 */
interface BunnyStreamManagerFactoryInterface {

  /**
   * Creates instance of the video manager with the configuration.
   *
   * @param string $configId
   *   The config ID to load.
   *
   * @return \Drupal\bunny_stream\Bunny\VideoManager|null
   *   VideoManager or Null if the library don't exist.
   */
  public function getVideoManager(string $configId): ?VideoManager;

}
