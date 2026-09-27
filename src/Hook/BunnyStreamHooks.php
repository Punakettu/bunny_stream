<?php

declare(strict_types=1);

namespace Drupal\bunny_stream\Hook;

use Drupal\bunny_stream\BunnyStreamSourceInterface;
use Drupal\bunny_stream\Form\BunnyStreamMediaLibraryForm;
use Drupal\bunny_stream\Bunny\DTO\VideoStatus;
use Drupal\Core\Entity\ContentEntityFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Url;
use Drupal\media\MediaInterface;

/**
 * Hook implementations for bunny_stream.
 */
final readonly class BunnyStreamHooks {

  /**
   * Form state key set when a media form creates a video for upload.
   */
  public const string UPLOAD_MODE_KEY = 'bunny_stream_upload_mode';

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(array $existing, string $type, string $theme, string $path): array {
    return [
      'bunny_embed' => [
        'variables' => [
          'url' => NULL,
          'title' => NULL,
          'width' => NULL,
          'height' => NULL,
          'options' => [],
          'attributes' => [],
        ],
      ],
    ];
  }

  /**
   * Implements hook_media_source_info_alter().
   */
  #[Hook('media_source_info_alter')]
  public function mediaSourceInfoAlter(array &$sources): void {
    if (empty($sources['bunny_stream']['forms']['media_library_add'])) {
      $sources['bunny_stream']['forms']['media_library_add'] = BunnyStreamMediaLibraryForm::class;
    }
  }

  /**
   * Implements hook_form_BASE_FORM_ID_alter() for media_form.
   */
  #[Hook('form_media_form_alter')]
  public function formMediaFormAlter(array &$form, FormStateInterface $form_state): void {
    $form_object = $form_state->getFormObject();
    if (!$form_object instanceof ContentEntityFormInterface) {
      return;
    }
    $media = $form_object->getEntity();
    if (!$media instanceof MediaInterface || !$media->getSource() instanceof BunnyStreamSourceInterface) {
      return;
    }
    if (isset($form['actions']['submit']['#submit'])) {
      $form['actions']['submit']['#submit'][] = [self::class, 'redirectToUpload'];
    }
  }

  /**
   * Submit handler: continues to the upload page until the file is uploaded.
   *
   * Redirects after creating a video for upload, and after saving a media
   * whose Bunny video is still waiting for its file.
   */
  public static function redirectToUpload(array &$form, FormStateInterface $form_state): void {
    $form_object = $form_state->getFormObject();
    if (!$form_object instanceof ContentEntityFormInterface) {
      return;
    }
    $media = $form_object->getEntity();
    if (!$media instanceof MediaInterface || !$media->id()) {
      return;
    }

    $url = Url::fromRoute('entity.media.bunny_stream_upload', ['media' => $media->id()]);
    if ($form_state->get(self::UPLOAD_MODE_KEY) || ($url->access() && self::isAwaitingUpload($media))) {
      $form_state->setRedirectUrl($url);
    }
  }

  /**
   * Whether the Bunny video of the media still accepts a file upload.
   */
  private static function isAwaitingUpload(MediaInterface $media): bool {
    $status = $media->getSource()->getMetadata($media, 'status');
    return is_int($status) && VideoStatus::tryFrom($status)?->isUploadable() === TRUE;
  }

}
