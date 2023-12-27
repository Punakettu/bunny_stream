<?php

namespace Drupal\bunny_stream\Controller;

use Drupal\bunny_stream\Event\WebhookEvent;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller for the webhook.
 */
class WebhookController extends ControllerBase {

  /**
   * Constructor to inject services.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event_dispatcher service.
   */
  public function __construct(
    protected EventDispatcherInterface $eventDispatcher
  ) {}

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('event_dispatcher')
    );
  }

  /**
   * Method to dispatch the event with the payload.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request to get the payload.
   * @param string $hash
   *   The security hash of the webhook.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Return normal empty response for a 200.
   */
  public function webhook(Request $request, string $hash) {
    $config_hash = $this->config('bunny_stream.settings')->get('webhook_hash');

    if ($config_hash !== $hash) {
      throw new AccessDeniedHttpException();
    }

    $post = $request->getPayload()->all();
    $event = new WebhookEvent($post);
    $this->eventDispatcher->dispatch($event, WebhookEvent::WEBHOOK);

    return new Response();
  }

}
