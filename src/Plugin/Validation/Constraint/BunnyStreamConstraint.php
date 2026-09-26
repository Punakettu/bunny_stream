<?php

namespace Drupal\bunny_stream\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Checks if a value represents a valid remote resource URL.
 */
#[Constraint(
  id: 'bunny_stream',
  label: new TranslatableMarkup('Bunny Stream resource', [], ['context' => 'Validation']),
  type: ['string'],
)]
class BunnyStreamConstraint extends SymfonyConstraint {

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
