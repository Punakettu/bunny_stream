<?php

declare(strict_types=1);

namespace Drupal\Tests\bunny_stream\Traits;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Helper methods for processing mocked requests with Drupal kernel.
 */
trait HttpKernelTestTrait {

  /**
   * Gets the given route.
   *
   * @param string $route
   *   Drupal route.
   * @param array<string, mixed> $parameters
   *   Route parameters.
   */
  private function get(string $route, array $parameters = []): Response {
    return $this->processRequest(Request::create(Url::fromRoute($route, $parameters)->toString()));
  }

  /**
   * Posts the given route.
   *
   * @param string $route
   *   Drupal route.
   * @param array<string, mixed> $body
   *   Body payload.
   * @param array<string, string> $headers
   *   Request headers.
   * @param array<string, mixed> $parameters
   *   Route parameters.
   */
  private function post(string $route, array $body = [], array $headers = [], array $parameters = []): Response {
    $request = Request::create(
      Url::fromRoute($route, $parameters)->toString(),
      'POST',
      content: json_encode($body, JSON_THROW_ON_ERROR),
    );

    $request->headers->set('Content-Type', 'application/json');

    if ($headers) {
      foreach ($headers as $name => $value) {
        $request->headers->set($name, $value);
      }
    }

    return $this->processRequest($request);
  }

  /**
   * Process a request.
   */
  private function processRequest(Request $request): Response {
    /** @var \Drupal\Core\StackMiddleware\StackedHttpKernel $kernel */
    $kernel = $this->container->get(HttpKernelInterface::class);
    $response = $kernel->handle($request);
    $kernel->terminate($request, $response);

    return $response;
  }

}
