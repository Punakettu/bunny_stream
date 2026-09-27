<?php

declare(strict_types=1);

namespace Drupal\Tests\bunny_stream\Traits;

use GuzzleHttp\Psr7\Response;

/**
 * Loads Bunny Stream API response fixtures.
 */
trait ApiFixtureTrait {

  /**
   * Creates a response from an API fixture.
   *
   * @param string $fixture
   *   The fixture name, matching the video manager method.
   */
  protected function getFixtureResponse(string $fixture): Response {
    return new Response(body: file_get_contents(__DIR__ . "/../../fixtures/api/$fixture.json"));
  }

}
