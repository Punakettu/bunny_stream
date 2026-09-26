<?php

namespace Drupal\bunny_stream\Controller;

use Drupal\bunny_stream\BunnyStreamLibraryInterface;
use Drupal\bunny_stream\Event\WebhookEvent;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
    protected EventDispatcherInterface $eventDispatcher,
  ) {}

  /**
   * Checks the webhook signature.
   */
  public function access(Request $request): AccessResultInterface {
    $library_id = $request->getPayload()->get('VideoLibraryId');
    $library = is_scalar($library_id)
      ? $this->entityTypeManager()->getStorage('bunny_stream_library')->load((string) $library_id)
      : NULL;
    $key = $library instanceof BunnyStreamLibraryInterface ? (string) $library->get('read_only_api_key') : '';
    $signature = strtolower((string) $request->headers->get('X-BunnyStream-Signature'));

    return AccessResult::allowedIf($key !== '' && hash_equals(hash_hmac('sha256', $request->getContent(), $key), $signature))
      ->setCacheMaxAge(0);
  }

  /**
   * Method to dispatch the event with the payload.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request to get the payload.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Return normal empty response for a 200.
   */
  public function webhook(Request $request): Response {
    $post = $request->getPayload()->all();
    $event = new WebhookEvent($post);
    $this->eventDispatcher->dispatch($event);

    return new Response();
  }

}
