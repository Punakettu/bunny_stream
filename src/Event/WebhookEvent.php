<?php

namespace Drupal\bunny_stream\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event for webhook endpoint.
 */
class WebhookEvent extends Event {

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
