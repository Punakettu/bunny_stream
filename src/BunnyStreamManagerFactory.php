<?php

namespace Drupal\bunny_stream;

use Drupal\bunny_stream\Bunny\VideoManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use GuzzleHttp\ClientInterface;

/**
 * Service to manage the factory of the videos.
 */
class BunnyStreamManagerFactory implements BunnyStreamManagerFactoryInterface {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ClientInterface $client,
  ) {}

  /**
   * {@inheritDoc}
   */
  public function getVideoManager(string $configId): ?VideoManager {
    $config = $this->loadConfig($configId);

    if (!is_null($config)) {
      return new VideoManager($this->client, $config);
    }

    return NULL;
  }

  /**
   * Loads the configuration entity with the given ID.
   *
   * @param string $configId
   *   The id of the configuration entity to load.
   */
  private function loadConfig(string $configId): ?BunnyStreamLibraryInterface {
    $entity = $this->entityTypeManager->getStorage('bunny_stream_library')->load($configId);
    assert(!$entity || $entity instanceof BunnyStreamLibraryInterface);
    return $entity;
  }

}
