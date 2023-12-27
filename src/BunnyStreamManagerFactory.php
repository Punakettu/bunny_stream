<?php

namespace Drupal\bunny_stream;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use GuzzleHttp\Client;

/**
 * Service to manage the factory of the videos.
 */
class BunnyStreamManagerFactory implements BunnyStreamManagerFactoryInterface {

  /**
   * Constructor of the factory service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   * @param \GuzzleHttp\Client $client
   *   The http_client service.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Client $client
  ) {}

  /**
   * {@inheritDoc}
   */
  public function getVideoManager(string $config_id): ?VideoManager {
    /** @var \Drupal\bunny_stream\BunnyStreamLibraryInterface|null $config */
    $config = $this->loadConfig($config_id);

    if (!is_null($config)) {
      return new VideoManager($this->client, $config->id(), $config->get('api_key'));
    }

    return NULL;
  }

  /**
   * Loads the configuration entity with the given ID.
   *
   * @param string $config_id
   *   The id of the configuration entity to load.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The configuration entity.
   */
  private function loadConfig(string $config_id): ?ConfigEntityInterface {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface|null $entity */
    $entity = $this->entityTypeManager->getStorage('bunny_stream_library')->load($config_id);
    return $entity;
  }

}
