<?php

namespace Drupal\bunny_stream\Plugin\media\Source;

use Drupal\bunny_stream\BunnyStreamManagerFactoryInterface;
use Drupal\bunny_stream\BunnyStreamSourceInterface;
use Drupal\Component\Render\PlainTextOutput;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\Utility\Token;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceBase;
use Drupal\media\MediaSourceFieldConstraintsInterface;
use Drupal\media\MediaTypeInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\TransferException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mime\MimeTypes;

/**
 * Provides a media source plugin for Bunny Stream resources.
 *
 * @MediaSource(
 *   id = "bunny_stream",
 *   label = @Translation("Bunny Stream"),
 *   description = @Translation("Use Bunny Stream for reusable media."),
 *   allowed_field_types = {"string"},
 *   default_thumbnail_filename = "bunny.png",
 *   providers = {},
 * )
 */
class BunnyStreamSource extends MediaSourceBase implements BunnyStreamSourceInterface, MediaSourceFieldConstraintsInterface {

  protected const DEFAULT_THUMBNAIL = 'public://bunny_stream_thumbnails/bunny-thumbnail.png';

  use MessengerTrait;
  use LoggerChannelTrait;

  /**
   * Bunny library that store the configuration.
   */
  protected ?ConfigEntityInterface $library;

  /**
   * Constructs a new BunnyStream instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type plugin manager service.
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The HTTP client.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file_system service.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $streamWrapperManager
   *   The stream_wrapper_manager service.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\bunny_stream\BunnyStreamManagerFactoryInterface $bunnyFactory
   *   The Bunny factory service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
    ConfigFactoryInterface $config_factory,
    FieldTypePluginManagerInterface $field_type_manager,
    protected ClientInterface $httpClient,
    protected FileSystemInterface $fileSystem,
    protected Token $token,
    protected StreamWrapperManagerInterface $streamWrapperManager,
    protected Request $request,
    protected BunnyStreamManagerFactoryInterface $bunnyFactory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $field_type_manager, $config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('config.factory'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('http_client'),
      $container->get('file_system'),
      $container->get('token'),
      $container->get('stream_wrapper_manager'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('bunny_stream.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    return [
      "videoLibraryId" => $this->t('Video library ID'),
      "guid" => $this->t('Video UUID'),
      "title" => $this->t('Title'),
      "dateUploaded" => $this->t('Uploaded date'),
      "views" => $this->t('Views'),
      "isPublic" => $this->t('Public'),
      "length" => $this->t('Length'),
      "status" => $this->t('Status'),
      "framerate" => $this->t('Framewrate'),
      "rotation" => $this->t('Rotation'),
      "width" => $this->t('Width'),
      "height" => $this->t('Height'),
      "availableResolutions" => $this->t('Available resolutions'),
      "thumbnailCount" => $this->t('Thumbnail count'),
      "encodeProgress" => $this->t('Encode progress'),
      "storageSize" => $this->t('Storage size'),
      "captions" => $this->t('Captions'),
      "hasMP4Fallback" => $this->t('Has mp4 fallback'),
      "collectionId" => $this->t('Collection ID'),
      "thumbnailFileName" => $this->t('Thumbnail file name'),
      "averageWatchTime" => $this->t('Average watch time'),
      "totalWatchTime" => $this->t('Total watch time'),
      "category" => $this->t('Category'),
      "chapters" => $this->t('Chapters'),
      "moments" => $this->t('Moments'),
      "metaTags" => $this->t('Meta tags'),
      "transcodingMessages" => $this->t('Transcoding messages'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $name) {

    $video_id = $this->getSourceFieldValue($media);
    // The URL may be NULL if the source field is empty, in which case just
    // return NULL.
    if (empty($video_id)) {
      return NULL;
    }

    try {
      /** @var \Drupal\bunny_stream\VideoManager $videoManager */
      $videoManager = $this->bunnyFactory->getVideoManager($this->getConfiguration()['library']);
      /** @var \Drupal\bunny_stream\BunnyVideo|null $video */
      $video = $videoManager->getVideo($video_id);

      if (is_null($video)) {
        return NULL;
      }
    }
    catch (\Exception $e) {
      $this->messenger()->addError($e->getMessage());
      return NULL;
    }

    switch ($name) {
      case 'default_name':
        if ($video->title) {
          return $video->title;
        }

        if ($title = $this->getMetadata($media, 'title')) {
          return $title;
        }

        if ($url = $this->getMetadata($media, 'url')) {
          return $url;
        }

        return parent::getMetadata($media, 'default_name');

      case 'thumbnail_uri':

        $library = $this->getLibrary();

        $thumbnail_uri = $library?->get('cdn_hostname') . '/' . $video_id . '/thumbnail.jpg';
        return $this->getLocalThumbnailUri($thumbnail_uri);

      case 'videoLibraryId':
        return $video->videoLibraryId;

      case 'guid':
        return $video->guid;

      case 'dateUploaded':
        return $video->dateUploaded;

      case 'views':
        return $video->views;

      case 'isPublic':
        return $video->isPublic;

      case 'length':
        return $video->length;

      case 'status':
        return $video->status;

      case 'framerate':
        return $video->framerate;

      case  'rotation':
        return $video->rotation;

      case 'width':
        return $video->width;

      case 'height':
        return $video->height;

      case 'availableResolutions':
        return $video->availableResolutions;

      case 'thumbnailCount':
        return $video->thumbnailCount;

      case 'encodeProgress':
        return $video->encodeProgress;

      case 'storageSize':
        return $video->storageSize;

      case 'captions':
        return $video->captions;

      case 'hasMP4Fallback':
        return $video->hasMP4Fallback;

      case 'collectionId':
        return $video->collectionId;

      case 'thumbnailFileName':
        return $video->thumbnailFileName;

      case 'averageWatchTime':
        return $video->averageWatchTime;

      case 'totalWatchTime':
        return $video->totalWatchTime;

      case 'category':
        return $video->category;

      case 'chapters':
        return $video->chapters;

      case 'moments':
        return $video->moments;

      case 'metaTags':
        return $video->metaTags;

      case 'transcodingMessages':
        return $video->transcodingMessages;

      default:
        break;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $configs = $this->entityTypeManager->getStorage('bunny_stream_library')->loadMultiple();

    $config_list = [];
    foreach ($configs as $config) {
      $config_list[$config->id()] = $config->label();
    }

    $configuration = $this->getConfiguration();

    $form['thumbnails_directory'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Thumbnails location'),
      '#default_value' => $configuration['thumbnails_directory'],
      '#description' => $this->t('Thumbnails will be fetched from the provider for local usage. This is the URI of the directory where they will be placed.'),
      '#required' => TRUE,
    ];

    $form['library'] = [
      '#type' => 'select',
      '#title' => $this->t('Bunny library'),
      '#options' => $config_list,
      '#default_value' => $configuration['library'],
      '#description' => $this->t('Select the library to use for this Media Type, if you dont have a library configured in your site, you can <a href="@url">create one</a>.', [
        '@url' => '/admin/structure/bunny-stream-library',
      ]),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $library = $form_state->getValue('library');

    if (!$this->entityTypeManager->getStorage('bunny_stream_library')->load($library)) {
      $form_state->setErrorByName('library', $this->t('The library ID @library is not configured in Drupal.', [
        '@library' => $library,
      ]));
    }

    $thumbnails_directory = $form_state->getValue('thumbnails_directory');

    if (!$this->streamWrapperManager->isValidUri($thumbnails_directory)) {
      $form_state->setErrorByName('thumbnails_directory', $this->t('@path is not a valid path.', [
        '@path' => $thumbnails_directory,
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceFieldConstraints() {
    return [
      'bunny_stream' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'thumbnails_directory' => 'public://bunny_stream_thumbnails/[date:custom:Y-m]',
      'library' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareViewDisplay(MediaTypeInterface $type, EntityViewDisplayInterface $display): void {
    $display->setComponent($this->getSourceFieldDefinition($type)->getName(), [
      'type' => 'bunny_stream_embed',
      'label' => 'visually_hidden',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareFormDisplay(MediaTypeInterface $type, EntityFormDisplayInterface $display): void {
    parent::prepareFormDisplay($type, $display);
    $source_field = $this->getSourceFieldDefinition($type)->getName();

    $display->setComponent($source_field, [
      'type' => 'bunny_stream_textfield',
      'weight' => $display->getComponent($source_field)['weight'],
    ]);
    $display->removeComponent('name');
  }

  /**
   * {@inheritdoc}
   */
  public function createSourceField(MediaTypeInterface $type) {
    $plugin_definition = $this->getPluginDefinition();

    $label = (string) $this->t('@type ID', [
      '@type' => $plugin_definition['label'],
    ]);
    return parent::createSourceField($type)->set('label', $label);
  }

  /**
   * Loads the library from the current configuration.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityInterface|null
   *   The library.
   */
  public function getLibrary(): ?ConfigEntityInterface {
    if (!isset($this->library)) {
      $this->library = $this->entityTypeManager->getStorage('bunny_stream_library')->load($this->getConfiguration()['library']);
    }
    return $this->library;
  }

  /**
   * Returns the local URI for a resource thumbnail.
   *
   * If the thumbnail is not already locally stored, this method will attempt
   * to download it.
   *
   * @param string $remote_thumbnail_url
   *   The url of the thumbnail.
   *
   * @return string|null
   *   The local thumbnail URI, or NULL if it could not be downloaded, or if the
   *   resource has no thumbnail at all.
   */
  protected function getLocalThumbnailUri(string $remote_thumbnail_url) {

    if (!$remote_thumbnail_url) {
      return NULL;
    }

    $configuration = $this->getConfiguration();
    $directory = $configuration['thumbnails_directory'];
    $directory = $this->token->replace($directory);
    $directory = PlainTextOutput::renderFromHtml($directory);

    // The local thumbnail doesn't exist yet, so try to download it. First,
    // ensure that the destination directory is writable, and if it's not,
    // log an error and bail out.
    if (!$this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
      $this->getLogger('bunny_stream')->warning('Could not prepare thumbnail destination directory @dir for Bunny stream media.', [
        '@dir' => $directory,
      ]);
      return NULL;
    }

    // The local filename of the thumbnail is always a hash of its remote URL.
    // If a file with that name already exists in the thumbnails directory,
    // regardless of its extension, return its URI.
    $hash = Crypt::hashBase64($remote_thumbnail_url);
    $files = $this->fileSystem->scanDirectory($directory, "/^$hash\..*/");
    if (count($files) > 0) {
      return reset($files)->uri;
    }

    // The local thumbnail doesn't exist yet, so we need to download it.
    try {
      $response = $this->httpClient->request('GET', $remote_thumbnail_url, [
        'headers' => [
          'Referer' => $this->request->getSchemeAndHttpHost(),
        ],
      ]);
      if ($response->getStatusCode() === 200) {
        $local_thumbnail_uri = $directory . DIRECTORY_SEPARATOR . $hash . '.' . $this->getThumbnailFileExtensionFromUrl($remote_thumbnail_url, $response);
        $this->fileSystem->saveData((string) $response->getBody(), $local_thumbnail_uri, FileSystemInterface::EXISTS_REPLACE);
        return $local_thumbnail_uri;
      }
    }
    catch (TransferException $e) {
      $this->getLogger('bunny_stream')->warning('Failed to download remote thumbnail file due to "%error".', [
        '%error' => $e->getMessage(),
      ]);
      return self::DEFAULT_THUMBNAIL;
    }
    catch (FileException $e) {
      $this->getLogger('bunny_stream')->warning('Could not download remote thumbnail from {url}.', [
        'url' => $remote_thumbnail_url,
      ]);
      return self::DEFAULT_THUMBNAIL;
    }
    return NULL;
  }

  /**
   * Tries to determine the file extension of a thumbnail.
   *
   * @param string $thumbnail_url
   *   The remote URL of the thumbnail.
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response for the downloaded thumbnail.
   *
   * @return string|null
   *   The file extension, or NULL if it could not be determined.
   */
  protected function getThumbnailFileExtensionFromUrl(string $thumbnail_url, ResponseInterface $response): ?string {
    // First, try to glean the extension from the URL path.
    $path = parse_url($thumbnail_url, PHP_URL_PATH);
    if ($path) {
      $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
      if ($extension) {
        return $extension;
      }
    }

    // If the URL didn't give us any clues about the file extension, see if the
    // response headers will give us a MIME type.
    $content_type = $response->getHeader('Content-Type');
    // If there was no Content-Type header, there's nothing else we can do.
    if (empty($content_type)) {
      return NULL;
    }
    $extensions = MimeTypes::getDefault()->getExtensions(reset($content_type));
    if ($extensions) {
      return reset($extensions);
    }
    // If no file extension could be determined from the Content-Type header,
    // we're stumped.
    return NULL;
  }

}
