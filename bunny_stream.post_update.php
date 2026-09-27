<?php

/**
 * @file
 * Post update functions for Bunny Stream.
 */

use Drupal\bunny_stream\Plugin\media\Source\BunnyStreamSource;
use Drupal\media\MediaTypeInterface;

/**
 * Stores the metadata of Bunny Stream videos in their source fields.
 */
function bunny_stream_post_update_video_metadata(array &$sandbox): string {
  $entity_type_manager = \Drupal::entityTypeManager();
  $media_storage = $entity_type_manager->getStorage('media');

  if (!isset($sandbox['ids'])) {
    $bundles = array_keys(array_filter(
      $entity_type_manager->getStorage('media_type')->loadMultiple(),
      static fn (MediaTypeInterface $media_type): bool => $media_type->getSource() instanceof BunnyStreamSource,
    ));
    $sandbox['ids'] = $bundles ? array_values($media_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('bundle', $bundles, 'IN')
      ->execute()) : [];
    $sandbox['total'] = count($sandbox['ids']);
  }

  foreach ($media_storage->loadMultiple(array_splice($sandbox['ids'], 0, 10)) as $media) {
    $source = $media->getSource();
    assert($source instanceof BunnyStreamSource);
    $source->updateVideoMetadata($media);
    $media->save();
  }

  $sandbox['#finished'] = $sandbox['total'] ? 1 - count($sandbox['ids']) / $sandbox['total'] : 1;
  return (string) t('Stored the metadata of @count Bunny Stream videos.', ['@count' => $sandbox['total']]);
}

/**
 * Removes the unused textfield settings from the Bunny Stream widget.
 */
function bunny_stream_post_update_widget_settings(): void {
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('core.entity_form_display.') as $name) {
    $config = $config_factory->getEditable($name);
    $changed = FALSE;
    foreach ($config->get('content') ?? [] as $field_name => $component) {
      if (($component['type'] ?? NULL) === 'bunny_stream_textfield') {
        $config->clear("content.$field_name.settings.size");
        $config->clear("content.$field_name.settings.placeholder");
        $changed = TRUE;
      }
    }
    if ($changed) {
      $config->save(TRUE);
    }
  }
}
