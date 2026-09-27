<?php

declare(strict_types=1);

namespace Drupal\Tests\bunny_stream\Traits;

use Drupal\bunny_stream\Entity\BunnyStreamLibrary;
use Drupal\bunny_stream\Plugin\media\Source\BunnyStreamSource;
use Drupal\media\MediaTypeInterface;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * Sets up a Bunny Stream library and media type for kernel tests.
 */
trait BunnyMediaTestTrait {

  use MediaTypeCreationTrait;

  /**
   * Test library ID.
   */
  protected const int LIBRARY_ID = 12345;

  /**
   * Test library API key.
   */
  protected const string API_KEY = '22222222-2222-4222-8222-222222222222';

  /**
   * Test library read-only API key.
   */
  protected const string READ_ONLY_API_KEY = '33333333-3333-4333-8333-333333333333';

  /**
   * The Bunny media type.
   */
  protected MediaTypeInterface $mediaType;

  /**
   * Installs media and creates the library and media type.
   */
  protected function setUpBunnyMedia(): void {
    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installSchema('file', 'file_usage');
    $this->installEntitySchema('media');
    $this->installConfig(['field', 'system', 'media']);

    BunnyStreamLibrary::create([
      'id' => (string) self::LIBRARY_ID,
      'label' => 'Test library',
      'api_key' => self::API_KEY,
      'read_only_api_key' => self::READ_ONLY_API_KEY,
      'cdn_hostname' => 'vz-12345.b-cdn.net',
    ])->save();

    $this->mediaType = $this->createMediaType('bunny_stream', [
      'id' => 'video',
      'label' => 'Video',
      'source_configuration' => [
        'library' => (string) self::LIBRARY_ID,
      ],
    ]);
  }

  /**
   * Gets the name of the source field.
   */
  protected function getSourceFieldName(): string {
    return $this->mediaType->getSource()?->getSourceFieldDefinition($this->mediaType)->getName();
  }

  /**
   * Disables video uploads on the library.
   */
  protected function disallowUploads(): void {
    $source = $this->mediaType->getSource();
    assert($source instanceof BunnyStreamSource);
    $source->getLibrary()?->set('allow_upload', FALSE)->save();
  }

}
