<?php

declare(strict_types=1);

namespace Drupal\Tests\bunny_stream\Traits;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Replaces the http_client service with a mocked Guzzle client.
 */
trait HttpClientMockTrait {

  /**
   * The mocked HTTP client.
   */
  private ?Client $httpMockClient = NULL;

  /**
   * Queued responses.
   */
  private MockHandler $httpMockHandler;

  /**
   * Requests sent through the mocked HTTP client.
   *
   * @var array<int, array{request: \Psr\Http\Message\RequestInterface}>
   */
  private array $httpHistory = [];

  /**
   * Queues responses installing the mocked client on first use.
   *
   * @param \Psr\Http\Message\ResponseInterface|\Throwable ...$responses
   *   Responses to return, or exceptions to throw, in order.
   */
  protected function mockHttpClient(ResponseInterface|\Throwable ...$responses): void {
    if (!$this->httpMockClient || $this->container->get('http_client') !== $this->httpMockClient) {
      $this->httpMockHandler = new MockHandler();
      $stack = HandlerStack::create($this->httpMockHandler);
      $stack->push(Middleware::history($this->httpHistory));
      $this->httpMockClient = new Client(['handler' => $stack]);
      $this->container->set('http_client', $this->httpMockClient);
    }
    $this->httpMockHandler->append(...$responses);
  }

  /**
   * Returns the requests sent through the mocked client.
   *
   * @return list<\Psr\Http\Message\RequestInterface>
   *   The requests, oldest first.
   */
  protected function getHttpRequests(): array {
    return array_column($this->httpHistory, 'request');
  }

  /**
   * Returns the most recent request sent through the mocked client.
   */
  protected function getLastHttpRequest(): RequestInterface {
    $requests = $this->getHttpRequests();
    $this->assertNotEmpty($requests, 'No HTTP request was sent.');
    return array_last($requests);
  }

}
