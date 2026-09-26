<?php

namespace Drupal\bunny_stream\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event for webhook endpoint.
 */
class WebhookEvent extends Event {

  /**
   * Legacy name of the webhook event.
   *
   * @deprecated in bunny_stream:1.0.0-beta3 and is removed from
   *   bunny_stream:2.0.0. Subscribe to
   *   \Drupal\bunny_stream\Event\WebhookEvent::class instead.
   */
  public const WEBHOOK = 'bunny_stream.webhook';

  /**
   * Constructor of the event.
   *
   * @param array $payload
   *   Payload of the webhook from bunny.net.
   */
  public function __construct(
    protected array $payload
  ) {}

  /**
   * Get the payload of the webhook.
   *
   * @return array
   *   Payload of the event.
   */
  public function getPayload(): array {
    return $this->payload;
  }

}
