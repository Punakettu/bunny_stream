<?php

namespace Drupal\bunny_stream;

/**
 * Interface for the service bunny_stream.manager.
 */
interface BunnyStreamManagerFactoryInterface {

  /**
   * Creates instance of the video manager with the configuration.
   *
   * @param string $config_id
   *   The config ID to load.
   *
   * @return \Drupal\bunny_stream\VideoManager|null
   *   VideoManager or Null if the library don't exist.
   */
  public function getVideoManager(string $config_id): ?VideoManager;

}
