<?php

namespace Drupal\bunny_stream\Event;

use Drupal\bunny_stream\Bunny\DTO\Webhook;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event for webhook endpoint.
 */
class WebhookEvent extends Event {

  /**
   * Constructor of the event.
   *
   * @param \Drupal\bunny_stream\Bunny\DTO\Webhook $payload
   *   The webhook payload from bunny.net.
   */
  public function __construct(
    protected Webhook $payload,
  ) {}

  /**
   * Get the webhook payload.
   *
   * @return \Drupal\bunny_stream\Bunny\DTO\Webhook
   *   The webhook payload.
   */
  public function getPayload(): Webhook {
    return $this->payload;
  }

}
