<?php

namespace Drupal\bunny_stream\Controller;

use Drupal\bunny_stream\BunnyStreamLibraryInterface;
use Drupal\bunny_stream\Bunny\DTO\Webhook;
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
    $payload = $request->getPayload();

    $library_id = $payload->get('VideoLibraryId');
    $signature = $request->headers->get('X-BunnyStream-Signature');
    $version = $request->headers->get('X-BunnyStream-Signature-Version');
    $algorithm = $request->headers->get('X-BunnyStream-Signature-Algorithm');

    $library = is_scalar($library_id)
      ? $this->entityTypeManager()->getStorage('bunny_stream_library')->load((string) $library_id)
      : NULL;

    assert(!$library || $library instanceof BunnyStreamLibraryInterface);

    return AccessResult::allowedIf($library?->validateSignature($signature, $version, $algorithm, $request->getContent()))
      ->setCacheMaxAge(0);
  }

  /**
   * Method to dispatch the event with the payload.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request to get the payload.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Empty response, 400 if the payload is malformed.
   */
  public function webhook(Request $request): Response {
    try {
      $webhook = Webhook::fromPayload($request->getPayload());
    }
    catch (\InvalidArgumentException) {
      return new Response(status: Response::HTTP_BAD_REQUEST);
    }

    $this->eventDispatcher->dispatch(new WebhookEvent($webhook));

    return new Response();
  }

}
