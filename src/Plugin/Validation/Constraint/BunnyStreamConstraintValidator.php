<?php

namespace Drupal\bunny_stream\Plugin\Validation\Constraint;

use Drupal\bunny_stream\Bunny\BunnyException;
use Drupal\bunny_stream\BunnyStreamManagerFactoryInterface;
use Drupal\bunny_stream\BunnyStreamSourceInterface;
use Drupal\bunny_stream\Plugin\media\Source\BunnyStreamSource;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates Media Remote URLs.
 */
class BunnyStreamConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  use AutowireTrait;

  /**
   * Constructor for the constraint validator.
   *
   * @param \Drupal\bunny_stream\BunnyStreamManagerFactoryInterface $bunnyStreamManagerFactory
   *   The bunny_stream.manager service.
   */
  public function __construct(
    protected BunnyStreamManagerFactoryInterface $bunnyStreamManagerFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint): void {
    if (!$constraint instanceof BunnyStreamConstraint) {
      throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\EntityExistsConstraint');
    }

    /** @var \Drupal\media\MediaInterface $media */
    $media = $value->getEntity();
    $source = $media->getSource();
    if (!($source instanceof BunnyStreamSource)) {
      throw new \LogicException('Media source must implement ' . BunnyStreamSource::class);
    }

    $id = $source->getSourceFieldValue($media);
    // The URL may be NULL if the source field is empty, which is invalid input.
    if (empty($id)) {
      $this->context->addViolation($constraint->emptyIdMessage);
      return;
    }

    $library = $source->getLibrary();

    // The Bunny video of a pending upload is created when a new media is saved.
    if ($id === BunnyStreamSourceInterface::PENDING_UPLOAD) {
      if (!$library?->isUploadAllowed()) {
        $this->context->addViolation($constraint->uploadNotAllowedMessage);
      }
      elseif (!$media->isNew()) {
        $this->context->addViolation($constraint->pendingUploadNotAllowedMessage);
      }
      return;
    }

    /** @var \Drupal\bunny_stream\Bunny\VideoManager|null $videoManager */
    $videoManager = $this->bunnyStreamManagerFactory->getVideoManager((string) $library?->id());

    try {
      if (is_null($videoManager?->getVideo($id))) {
        $this->context->addViolation($constraint->invalidIdMessage);
      }
    }
    catch (BunnyException) {
      $this->context->addViolation($constraint->invalidIdMessage);
    }

  }

}
