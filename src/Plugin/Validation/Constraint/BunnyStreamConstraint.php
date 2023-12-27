<?php

namespace Drupal\bunny_stream\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if a value represents a valid remote resource URL.
 *
 * @Constraint(
 *   id = "bunny_stream",
 *   label = @Translation("Bunny Stream resource", context = "Validation"),
 *   type = {"string"}
 * )
 */
class BunnyStreamConstraint extends Constraint {

  /**
   * The error message if the URL is empty.
   *
   * @var string
   */
  public $emptyIdMessage = 'The video id cannot be empty.';

  /**
   * The error message if the URL does not match.
   *
   * @var string
   */
  public $invalidIdMessage = 'The given ID is not valid video.';

}
