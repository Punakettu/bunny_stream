<?php

namespace Drupal\bunny_stream\Plugin\Validation\Constraint;

use Drupal\bunny_stream\BunnyStreamManagerFactoryInterface;
use Drupal\bunny_stream\Plugin\media\Source\BunnyStreamSource;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates Media Remote URLs.
 */
class BunnyStreamConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Constructor for the constraint validator.
   *
   * @param \Drupal\bunny_stream\BunnyStreamManagerFactoryInterface $bunnyStreamManagerFactory
   *   The bunny_stream.manager service.
   */
  public function __construct(
    protected BunnyStreamManagerFactoryInterface $bunnyStreamManagerFactory
  ) {}

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('bunny_stream.manager')
    );
  }

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

    /** @var \Drupal\bunny_stream\BunnyStreamLibraryInterface $library */
    $library = $source->getLibrary();

    /** @var \Drupal\bunny_stream\VideoManager|null $videoManager */
    $videoManager = $this->bunnyStreamManagerFactory->getVideoManager((string) $library->id());

    try {
      if (is_null($videoManager?->getVideo($id))) {
        $this->context->addViolation($constraint->invalidIdMessage);
      }
    }
    catch (\Exception $exception) {
      $this->context->addViolation($constraint->invalidIdMessage);
    }

  }

}
