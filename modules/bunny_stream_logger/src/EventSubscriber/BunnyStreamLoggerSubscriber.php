<?php

declare(strict_types=1);

namespace Drupal\bunny_stream_logger\EventSubscriber;

use Drupal\bunny_stream\Event\WebhookEvent;
use Drupal\bunny_stream\EventSubscriber\WebhookSubscriberBase;
use Drupal\Core\Database\Connection;

/**
 * Listen the webhook events and insert the event data to database.
 */
final class BunnyStreamLoggerSubscriber extends WebhookSubscriberBase {

  /**
   * Constructor for the event subscriber.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The 'database¡ service.
   */
  public function __construct(
    protected Connection $database,
  ) {}

  /**
   * Inser in the databaser the webhook information.
   *
   * @param \Drupal\bunny_stream\Event\WebhookEvent $event
   *   Event with the information from webhook.
   */
  #[\Override]
  public function onWebhook(WebhookEvent $event): void {
    $webhook = $event->getPayload();

    $this->database
      ->insert('bunny_stream_logger')
      ->fields([
        'status' => $webhook->status->value,
        'library' => $webhook->videoLibraryId,
        'video' => $webhook->videoGuid,
        'timestamp' => time(),
      ])
      ->execute();
  }

}
