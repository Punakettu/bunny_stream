<?php

declare(strict_types=1);

namespace Drupal\Tests\bunny_stream\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\bunny_stream\WebhookStates;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the webhook states enum.
 */
#[CoversClass(WebhookStates::class)]
#[Group('bunny_stream')]
final class WebhookStatesTest extends UnitTestCase {

  /**
   * Every case has a label.
   *
   * An unhandled case would throw \UnhandledMatchError.
   */
  public function testLabels(): void {
    $this->expectNotToPerformAssertions();

    foreach (WebhookStates::cases() as $case) {
      $case->label();
    }
  }

}
