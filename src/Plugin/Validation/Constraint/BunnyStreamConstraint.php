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
  public string $emptyIdMessage = 'The video id cannot be empty.';

  /**
   * The error message if the URL does not match.
   *
   * @var string
   */
  public string $invalidIdMessage = 'The given ID is not valid video.';

  /**
   * The error message if a pending upload is set on an existing media.
   *
   * @var string
   */
  public string $pendingUploadNotAllowedMessage = 'Only a new media can create a video for upload.';

  /**
   * The error message if the media source does not allow uploads.
   *
   * @var string
   */
  public string $uploadNotAllowedMessage = 'Video uploads are not allowed for this media type.';

}
