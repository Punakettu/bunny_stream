<?php

declare(strict_types=1);

namespace Drupal\bunny_stream\Element;

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Attribute\RenderElement;
use Drupal\Core\Render\Element\RenderElementBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\media\MediaInterface;

/**
 * Provides a TUS uploader for the Bunny video of a media.
 *
 * The file is sent from the browser directly to Bunny Stream. The file input
 * has no name, so the file is never posted to Drupal.
 *
 * Properties:
 * - #media: The media whose Bunny video the file is uploaded to.
 *
 * Usage example:
 * @code
 * $build['upload'] = [
 *   '#type' => 'bunny_stream_upload',
 *   '#media' => $media,
 * ];
 * @endcode
 */
#[RenderElement('bunny_stream_upload')]
final class BunnyStreamUpload extends RenderElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return [
      '#media' => NULL,
      '#pre_render' => [
        [self::class, 'preRenderUpload'],
      ],
    ];
  }

  /**
   * Builds the upload widget.
   */
  public static function preRenderUpload(array $element): array {
    $media = $element['#media'];
    if (!$media instanceof MediaInterface || $media->isNew()) {
      return $element;
    }

    $id = Html::getUniqueId('bunny-stream-upload-file');

    $element['#theme_wrappers'][] = 'container';
    $element['#attributes']['class'][] = 'bunny-stream-upload';
    $element['#attributes']['data-signature-url'] = Url::fromRoute('bunny_stream.media_upload_signature', ['media' => $media->id()])->toString();
    $element['#attributes']['data-media-url'] = $media->toUrl('edit-form')->toString();
    $element['#attached']['library'][] = 'bunny_stream/upload';

    $element['label'] = [
      '#type' => 'html_tag',
      '#tag' => 'label',
      '#value' => new TranslatableMarkup('Video file'),
      '#attributes' => [
        'for' => $id,
        'class' => ['form-item__label'],
      ],
    ];
    $element['file'] = [
      '#type' => 'html_tag',
      '#tag' => 'input',
      '#attributes' => [
        'type' => 'file',
        'id' => $id,
        'accept' => 'video/*',
        'class' => ['bunny-stream-upload__file', 'form-element'],
      ],
    ];
    $element['progress'] = [
      '#type' => 'html_tag',
      '#tag' => 'progress',
      '#attributes' => [
        'class' => ['bunny-stream-upload__progress'],
        'max' => 100,
        'value' => 0,
        'hidden' => 'hidden',
      ],
    ];
    $element['cancel'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#value' => new TranslatableMarkup('Cancel upload'),
      '#attributes' => [
        'type' => 'button',
        'class' => ['bunny-stream-upload__cancel', 'button'],
        'hidden' => 'hidden',
      ],
    ];
    $element['status'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['bunny-stream-upload__status'],
        'aria-live' => 'polite',
      ],
    ];

    return $element;
  }

}
