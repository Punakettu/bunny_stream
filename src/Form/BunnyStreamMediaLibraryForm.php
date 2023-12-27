<?php

namespace Drupal\bunny_stream\Form;

use Drupal\bunny_stream\BunnyStreamManagerFactoryInterface;
use Drupal\bunny_stream\BunnyStreamSourceInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\media_library\Form\AddFormBase;
use Drupal\media_library\MediaLibraryUiBuilder;
use Drupal\media_library\OpenerResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creates a form to create media entities from Bunny stream ID's.
 */
class BunnyStreamMediaLibraryForm extends AddFormBase {

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
    protected BunnyStreamManagerFactoryInterface $bunnyStreamManagerFactory
  ) {
    parent::__construct($entity_type_manager, $library_ui_builder, $opener_resolver);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('media_library.ui_builder'),
      $container->get('media_library.opener_resolver'),
      $container->get('bunny_stream.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
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
  protected function buildInputElement(array $form, FormStateInterface $form_state) {

    // This is just to validate that we have the correct media type
    // because getMediaType method throws exception if is not valid.
    $this->getMediaType($form_state);

    // Add a container to group the input elements for styling purposes.
    $form['container'] = [
      '#type' => 'container',
    ];

    $form['container']['id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Add @type via UUID', [
        '@type' => $this->getMediaType($form_state)->label(),
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
      '#ajax' => [
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
      ],
    ];
    return $form;
  }

  /**
   * Validates the given video exists.
   *
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  public function validateVideo(array &$form, FormStateInterface $form_state) {
    $video_id = $form_state->getValue('id');

    /** @var \Drupal\bunny_stream\Plugin\media\Source\BunnyStreamSource $source */
    $source = $this->getMediaType($form_state)->getSource();

    /** @var \Drupal\bunny_stream\BunnyStreamLibraryInterface $library */
    $library = $source->getLibrary();

    /** @var \Drupal\bunny_stream\VideoManager $video_manager */
    $video_manager = $this->bunnyStreamManagerFactory->getVideoManager($library->id());

    if ($video_id) {
      try {
        if (is_null($video_manager->getVideo($video_id))) {
          $form_state->setErrorByName('id', $this->t('The given ID is not valid video.'));
        }
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
  public function addButtonSubmit(array $form, FormStateInterface $form_state) {
    $this->processInputValues([$form_state->getValue('id')], $form, $form_state);
  }

}
