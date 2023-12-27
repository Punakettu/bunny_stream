<?php

declare(strict_types = 1);

namespace Drupal\bunny_stream_logger\EventSubscriber;

use Drupal\bunny_stream\Event\WebhookEvent;
use Drupal\Core\Database\Connection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listen the event to insert in the db the evento from webhook.
 */
final class BunnyStreamLoggerSubscriber implements EventSubscriberInterface {

  /**
   * Constructor for the event subscriber.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The 'database¡ service.
   */
  public function __construct(
    protected Connection $database
  ) {}

  /**
   * Inser in the databaser the webhook information.
   *
   * @param \Drupal\bunny_stream\Event\WebhookEvent $event
   *   Event with the information from webhook.
   */
  public function onWebhook(WebhookEvent $event): void {
    $payload = $event->getPayload();

    $this->database
      ->insert('bunny_stream_logger')
      ->fields([
        'status' => $payload['Status'],
        'library' => $payload['VideoLibraryId'],
        'video' => $payload['VideoGuid'],
        'timestamp' => time(),
      ])
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      WebhookEvent::WEBHOOK => ['onWebhook'],
    ];
  }

}
