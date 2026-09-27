<?php

declare(strict_types=1);

namespace Drupal\bunny_stream\EventSubscriber;

use Drupal\bunny_stream\Event\WebhookEvent;
use Drupal\bunny_stream\Plugin\media\Source\BunnyStreamSource;
use Drupal\bunny_stream\Bunny\DTO\VideoStates;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\media\MediaTypeInterface;

/**
 * Refreshes media metadata when Bunny finishes encoding.
 */
final class WebhookMediaSyncSubscriber extends WebhookSubscriberBase {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritDoc}
   */
  #[\Override]
  public function onWebhook(WebhookEvent $event): void {
    $webhook = $event->getPayload();

    if ($webhook->status !== VideoStates::Finished) {
      return;
    }

    $storage = $this->entityTypeManager->getStorage('media');

    foreach ($this->getMediaTypes((string) $webhook->videoLibraryId) as $mediaType) {
      $sourceField = $mediaType->getSource()->getSourceFieldDefinition($mediaType);
      $bundleField = $this->entityTypeManager
        ->getDefinition($mediaType->getEntityType()->getBundleOf())
        ->getKey('bundle');

      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition($bundleField, $mediaType->id())
        ->condition($sourceField->getName(), $webhook->videoGuid)
        ->execute();

      /** @var \Drupal\media\Entity\Media $media */
      foreach ($storage->loadMultiple($ids) as $media) {
        $source = $media->getSource();
        assert($source instanceof BunnyStreamSource);
        $source->updateVideoMetadata($media);
        $media->updateQueuedThumbnail()->save();
      }
    }
  }

  /**
   * Gets the Bunny media types that use the given library.
   *
   * @return \Drupal\media\MediaTypeInterface[]
   *   The media types.
   */
  private function getMediaTypes(string $library_id): array {
    $storage = $this->entityTypeManager->getStorage('media_type');
    $ids = $storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('source', 'bunny_stream')
      ->execute();

    return array_filter(
      $storage->loadMultiple($ids),
      static fn (MediaTypeInterface $media_type): bool =>
        $media_type->getSource() instanceof BunnyStreamSource &&
        (string) ($media_type->getSource()->getConfiguration()['library'] ?? '') === $library_id,
    );
  }

}
