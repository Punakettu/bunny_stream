<?php

namespace Drupal\bunny_stream\EventSubscriber;

use Drupal\bunny_stream\Event\WebhookEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Base class for bunny webhook listeners.
 */
abstract class WebhookSubscriberBase implements EventSubscriberInterface {

  /**
   * Webhook handler.
   */
  abstract public function onWebhook(WebhookEvent $event): void;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      WebhookEvent::class => ['onWebhook'],
    ];
  }

}
