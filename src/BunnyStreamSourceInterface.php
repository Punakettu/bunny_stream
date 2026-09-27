<?php

namespace Drupal\bunny_stream;

/**
 * Interface for the BunnyStream Source.
 */
interface BunnyStreamSourceInterface {

  /**
   * Source field value of a media whose Bunny video is created on save.
   *
   * The value is replaced with the GUID of a new Bunny video before the media
   * is stored.
   */
  public const string PENDING_UPLOAD = 'bunny_stream:pending_upload';

}
