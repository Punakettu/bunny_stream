<?php

declare(strict_types=1);

namespace Drupal\bunny_stream\Entity;

use Drupal\bunny_stream\BunnyStreamLibraryInterface;
use Drupal\bunny_stream\Entity\Handlers\BunnyStreamLibraryListBuilder;
use Drupal\bunny_stream\Form\BunnyStreamLibraryForm;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the bunny_stream_library entity type.
 */
#[ConfigEntityType(
  id: 'bunny_stream_library',
  label: new TranslatableMarkup('Bunny Stream Library'),
  label_collection: new TranslatableMarkup('Bunny Stream libraries'),
  label_singular: new TranslatableMarkup('Bunny Stream library'),
  label_plural: new TranslatableMarkup('Bunny Stream libraries'),
  config_prefix: 'bunny_stream_library',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'uuid' => 'uuid',
  ],
  handlers: [
    'list_builder' => BunnyStreamLibraryListBuilder::class,
    'form' => [
      'add' => BunnyStreamLibraryForm::class,
      'edit' => BunnyStreamLibraryForm::class,
      'delete' => EntityDeleteForm::class,
    ],
  ],
  links: [
    'collection' => '/admin/structure/bunny-stream-library',
    'add-form' => '/admin/structure/bunny-stream-library/add',
    'edit-form' => '/admin/structure/bunny-stream-library/{bunny_stream_library}',
    'delete-form' => '/admin/structure/bunny-stream-library/{bunny_stream_library}/delete',
  ],
  admin_permission: 'administer bunny_stream_library',
  label_count: [
    'singular' => '@count Bunny stream library',
    'plural' => '@count Bunny stream libraries',
  ],
  config_export: [
    'id',
    'label',
    'description',
    'allow_upload',
    'api_key',
    'cdn_hostname',
    'pull_zone',
    'token_authentication_key',
    'read_only_api_key',
  ],
)]
final class BunnyStreamLibrary extends ConfigEntityBase implements BunnyStreamLibraryInterface {

  /**
   * The id of the library.
   */
  protected ?string $id = NULL;

  /**
   * The name of the library.
   */
  protected ?string $label = NULL;

  /**
   * The example description.
   */
  protected ?string $description = NULL;

  /**
   * Whether videos can be created and uploaded from Drupal.
   */
  protected bool $allow_upload = TRUE;

  /**
   * The read/write API key, used only to create and upload videos.
   */
  protected ?string $api_key = NULL;

  /**
   * The cdn hostname of the library.
   */
  protected ?string $cdn_hostname = NULL;

  /**
   * The pull zone of the library.
   */
  protected ?string $pull_zone = NULL;

  /**
   * Security token.
   */
  protected ?string $token_authentication_key = NULL;

  /**
   * The read-only API key, used to read videos and verify webhook signatures.
   */
  protected ?string $read_only_api_key = NULL;

  /**
   * {@inheritDoc}
   */
  public function validateSignature(string $signature, string $version, string $algorithm, string $content): bool {
    $key = $this->get('read_only_api_key');
    $signature = strtolower($signature);

    return $version === 'v1' &&
      $algorithm === 'hmac-sha256' &&
      !empty($key) &&
      hash_equals(hash_hmac('sha256', $content, $key), $signature);
  }

  /**
   * {@inheritDoc}
   */
  public function isUploadAllowed(): bool {
    return $this->allow_upload && !empty($this->api_key);
  }

  /**
   * {@inheritDoc}
   */
  public function createUploadSignature(string $video_id, int $expire): string {
    if (!$this->isUploadAllowed()) {
      throw new \LogicException('Uploads are not allowed for this library.');
    }
    return hash('sha256', $this->id() . $this->get('api_key') . $expire . $video_id);
  }

  /**
   * {@inheritDoc}
   */
  public function isTokenAuthenticationEnabled(): bool {
    return !empty($this->token_authentication_key);
  }

  /**
   * {@inheritDoc}
   */
  public function createEmbedToken(string $video_id, int $expires): string {
    if (!$this->isTokenAuthenticationEnabled()) {
      throw new \LogicException('Token authentication is not enabled for this library.');
    }
    return hash('sha256', $this->token_authentication_key . $video_id . $expires);
  }

}
