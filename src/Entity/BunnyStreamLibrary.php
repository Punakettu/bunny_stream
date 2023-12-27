<?php

declare(strict_types = 1);

namespace Drupal\bunny_stream\Entity;

use Drupal\bunny_stream\BunnyStreamLibraryInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the bunny_stream_library entity type.
 *
 * @ConfigEntityType(
 *   id = "bunny_stream_library",
 *   label = @Translation("Bunny Stream Library"),
 *   label_collection = @Translation("Bunny Stream libraries"),
 *   label_singular = @Translation("Bunny Stream library"),
 *   label_plural = @Translation("Bunny Stream libraries"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Bunny stream library",
 *     plural = "@count Bunny stream libraries",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\bunny_stream\BunnyStreamLibraryListBuilder",
 *     "form" = {
 *       "add" = "Drupal\bunny_stream\Form\BunnyStreamLibraryForm",
 *       "edit" = "Drupal\bunny_stream\Form\BunnyStreamLibraryForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *   },
 *   config_prefix = "bunny_stream_library",
 *   admin_permission = "administer bunny_stream_library",
 *   links = {
 *     "collection" = "/admin/structure/bunny-stream-library",
 *     "add-form" = "/admin/structure/bunny-stream-library/add",
 *     "edit-form" = "/admin/structure/bunny-stream-library/{bunny_stream_library}",
 *     "delete-form" = "/admin/structure/bunny-stream-library/{bunny_stream_library}/delete",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "api_key",
 *     "cdn_hostname",
 *     "pull_zone",
 *     "token_authentication_key",
 *     "time",
 *   },
 * )
 */
final class BunnyStreamLibrary extends ConfigEntityBase implements BunnyStreamLibraryInterface {

  /**
   * The id of the library.
   */
  protected int $id;

  /**
   * The name of the library.
   */
  protected string $label;

  /**
   * The example description.
   */
  protected string $description;

  /**
   * The API key to access to this library in Bunny stream.
   */
  protected string $api_key;

  /**
   * The cdn hostname of the library.
   */
  protected string $cdn_hostname;

  /**
   * The pull zone of the library.
   */
  protected string $pull_zone;

  /**
   * Security token.
   */
  protected string $token_authentication_key;

  /**
   * The time in seconds to expire private videos.
   */
  protected int $time;

}
