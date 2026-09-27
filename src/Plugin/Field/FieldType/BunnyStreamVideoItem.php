<?php

declare(strict_types=1);

namespace Drupal\bunny_stream\Plugin\Field\FieldType;

use Drupal\bunny_stream\Bunny\DTO\BunnyVideo;
use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\StringItem;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'bunny_stream_video' field type.
 *
 * @property string|null $value
 *   The GUID of the video.
 * @property int|null $width
 *   The width of the video in pixels, NULL until Bunny has processed it.
 * @property int|null $height
 *   The height of the video in pixels, NULL until Bunny has processed it.
 */
#[FieldType(
  id: 'bunny_stream_video',
  label: new TranslatableMarkup('Bunny Stream video'),
  description: new TranslatableMarkup('Stores a Bunny Stream video ID and its metadata.'),
  default_widget: 'bunny_stream_textfield',
  default_formatter: 'bunny_stream_embed',
  no_ui: TRUE,
)]
final class BunnyStreamVideoItem extends StringItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition): array {
    $properties = parent::propertyDefinitions($field_definition);
    $properties['width'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Width'));
    $properties['height'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Height'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition): array {
    $schema = parent::schema($field_definition);
    $schema['columns']['width'] = [
      'type' => 'int',
      'unsigned' => TRUE,
    ];
    $schema['columns']['height'] = [
      'type' => 'int',
      'unsigned' => TRUE,
    ];
    return $schema;
  }

  /**
   * Stores the metadata of the video.
   */
  public function setVideoMetadata(BunnyVideo $video): void {
    // Bunny reports zero dimensions until the file has been processed.
    $this->set('width', $video->width > 0 ? $video->width : NULL);
    $this->set('height', $video->height > 0 ? $video->height : NULL);
  }

  /**
   * Whether the metadata of the video has been stored.
   */
  public function hasVideoMetadata(): bool {
    return !empty($this->width) && !empty($this->height);
  }

}
