<?php

namespace Drupal\bunny_stream\Form;

use Drupal\bunny_stream\BunnyStreamManagerFactoryInterface;
use Drupal\bunny_stream\BunnyStreamSourceInterface;
use Drupal\bunny_stream\Plugin\Field\FieldWidget\BunnyStreamWidget;
use Drupal\bunny_stream\Plugin\media\Source\BunnyStreamSource;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\media\MediaTypeInterface;
use Drupal\media_library\Form\AddFormBase;
use Drupal\media_library\MediaLibraryUiBuilder;
use Drupal\media_library\OpenerResolverInterface;

/**
 * Creates a form to create media entities from Bunny stream ID's.
 */
class BunnyStreamMediaLibraryForm extends AddFormBase {

  use AutowireTrait;

  /**
   * Form state key holding the title of a video created for upload.
   */
  protected const string UPLOAD_TITLE_KEY = 'bunny_stream_upload_title';

  /**
   * Title for the media created by processInputValues().
   */
  protected ?string $pendingUploadTitle = NULL;

  /**
   * Constructs an AddFormBase object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\media_library\MediaLibraryUiBuilder $library_ui_builder
   *   The media library UI builder.
   * @param \Drupal\media_library\OpenerResolverInterface $opener_resolver
   *   The opener resolver.
   * @param \Drupal\bunny_stream\BunnyStreamManagerFactoryInterface $bunnyStreamManagerFactory
   *   The service bunny_stream.manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    MediaLibraryUiBuilder $library_ui_builder,
    OpenerResolverInterface $opener_resolver,
    protected BunnyStreamManagerFactoryInterface $bunnyStreamManagerFactory,
  ) {
    parent::__construct($entity_type_manager, $library_ui_builder, $opener_resolver);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return $this->getBaseFormId() . '_bunny_stream';
  }

  /**
   * {@inheritdoc}
   */
  protected function getMediaType(FormStateInterface $form_state) {
    if ($this->mediaType) {
      return $this->mediaType;
    }

    $media_type = parent::getMediaType($form_state);
    if (!$media_type->getSource() instanceof BunnyStreamSourceInterface) {
      throw new \InvalidArgumentException('Can only add media types which use an Bunny Stream source plugin.');
    }
    return $media_type;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildInputElement(array $form, FormStateInterface $form_state): array {
    $media_type = $this->getMediaType($form_state);

    // Add a container to group the input elements for styling purposes.
    $form['container'] = [
      '#type' => 'container',
    ];

    $ajax = [
      'callback' => '::updateFormCallback',
      'wrapper' => 'media-library-wrapper',
      // Add a fixed URL to post the form since AJAX forms are automatically
      // posted to <current> instead of $form['#action'].
      // @todo Remove when https://www.drupal.org/project/drupal/issues/2504115
      //   is fixed.
      'url' => Url::fromRoute('media_library.ui'),
      'options' => [
        'query' => $this->getMediaLibraryState($form_state)->all() + [
          FormBuilderInterface::AJAX_FORM_REQUEST => TRUE,
        ],
      ],
    ];

    if ($this->createsVideoForUpload($media_type)) {
      $form['container']['upload_title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Create a new @type for upload', [
          '@type' => $media_type->label(),
        ]),
        '#description' => $this->t('Enter the video title. You can upload the video file after saving.'),
        '#maxlength' => 255,
        '#required' => TRUE,
      ];
      $form['container']['create'] = [
        '#type' => 'submit',
        '#value' => $this->t('Create video for upload'),
        '#button_type' => 'primary',
        '#validate' => ['::validateUploadTitle'],
        '#submit' => ['::createUploadSubmit'],
        '#ajax' => $ajax,
      ];
      return $form;
    }

    $form['container']['id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Add @type via UUID', [
        '@type' => $media_type->label(),
      ]),
      '#description' => $this->t("Allowed add Bunny Stream video using UUID's."),
      '#required' => TRUE,
    ];

    $form['container']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add'),
      '#button_type' => 'primary',
      '#validate' => ['::validateVideo'],
      '#submit' => ['::addButtonSubmit'],
      // @todo Move validation in https://www.drupal.org/node/2988215
      '#ajax' => $ajax,
    ];

    return $form;
  }

  /**
   * Whether the form creates a Bunny video for upload instead of asking a UUID.
   *
   * Follows the mode of the source field widget on the media library form
   * display, which is also used for the media added by this form.
   *
   * @param \Drupal\media\MediaTypeInterface $media_type
   *   The media type.
   *
   * @return bool
   *   TRUE if new media create a Bunny video for upload.
   */
  protected function createsVideoForUpload(MediaTypeInterface $media_type): bool {
    $media = $this->entityTypeManager->getStorage('media')->create([
      'bundle' => $media_type->id(),
    ]);
    $source_field_name = $media_type->getSource()->getSourceFieldDefinition($media_type)?->getName();
    $widget = $source_field_name ? EntityFormDisplay::collectRenderDisplay($media, 'media_library')->getRenderer($source_field_name) : NULL;
    if ($widget instanceof BunnyStreamWidget) {
      return $widget->createsVideoForUpload();
    }

    // Without the widget, fall back to its default upload mode.
    $source = $media_type->getSource();
    return $source instanceof BunnyStreamSource && $source->getLibrary()?->isUploadAllowed();
  }

  /**
   * Validates the title of a video created for upload.
   *
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  public function validateUploadTitle(array &$form, FormStateInterface $form_state): void {
    if (trim((string) $form_state->getValue('upload_title')) === '') {
      $form_state->setErrorByName('upload_title', $this->t('Enter a title for the video.'));
    }
  }

  /**
   * Submit handler for the create video for upload button.
   *
   * The Bunny video is created when the media is saved.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function createUploadSubmit(array $form, FormStateInterface $form_state): void {
    $form_state->set(self::UPLOAD_TITLE_KEY, trim((string) $form_state->getValue('upload_title')));
    $this->processInputValues([BunnyStreamSourceInterface::PENDING_UPLOAD], $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function processInputValues(array $source_field_values, array $form, FormStateInterface $form_state): void {
    // Media are created from the values without access to the form state.
    $this->pendingUploadTitle = $form_state->get(self::UPLOAD_TITLE_KEY);
    parent::processInputValues($source_field_values, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function createMediaFromValue(MediaTypeInterface $media_type, EntityStorageInterface $media_storage, $source_field_name, $source_field_value) {
    $media = parent::createMediaFromValue($media_type, $media_storage, $source_field_name, $source_field_value);
    if ($source_field_value === BunnyStreamSourceInterface::PENDING_UPLOAD && $this->pendingUploadTitle) {
      $media->setName($this->pendingUploadTitle);
    }
    return $media;
  }

  /**
   * {@inheritdoc}
   *
   * Links to the upload pages of videos created for upload.
   */
  public function updateLibrary(array &$form, FormStateInterface $form_state) {
    $response = parent::updateLibrary($form, $form_state);

    if (!$response instanceof AjaxResponse || !$form_state->get(self::UPLOAD_TITLE_KEY)) {
      return $response;
    }

    foreach ($this->getAddedMediaItems($form_state) as $media) {
      $response->addCommand(new MessageCommand($this->t('Video %label was created. <a href=":url" target="_blank">Upload the video file</a> (opens in a new tab).', [
        '%label' => $media->label(),
        ':url' => Url::fromRoute('entity.media.bunny_stream_upload', ['media' => $media->id()])->toString(),
      ]), '#media-library-messages', ['type' => 'status'], FALSE));
    }

    return $response;
  }

  /**
   * Validates the given video exists.
   *
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  public function validateVideo(array &$form, FormStateInterface $form_state): void {
    $video_id = $form_state->getValue('id');

    /** @var \Drupal\bunny_stream\Plugin\media\Source\BunnyStreamSource $source */
    $source = $this->getMediaType($form_state)->getSource();

    /** @var \Drupal\bunny_stream\BunnyStreamLibraryInterface $library */
    $library = $source->getLibrary();

    /** @var \Drupal\bunny_stream\Bunny\VideoManager $video_manager */
    $video_manager = $this->bunnyStreamManagerFactory->getVideoManager($library->id());

    if ($video_id) {
      try {
        $video_manager->getVideo($video_id);
      }
      catch (\Exception $exception) {
        $form_state->setErrorByName('id', $exception->getMessage());
      }
    }
  }

  /**
   * Submit handler for the add button.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function addButtonSubmit(array $form, FormStateInterface $form_state): void {
    $this->processInputValues([$form_state->getValue('id')], $form, $form_state);
  }

}
