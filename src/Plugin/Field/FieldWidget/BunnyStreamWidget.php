<?php

namespace Drupal\bunny_stream\Plugin\Field\FieldWidget;

use Drupal\bunny_stream\BunnyStreamSourceInterface;
use Drupal\bunny_stream\Hook\BunnyStreamHooks;
use Drupal\bunny_stream\Plugin\media\Source\BunnyStreamSource;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\media\Entity\MediaType;
use Drupal\media\MediaInterface;
use Drupal\media\MediaTypeInterface;

/**
 * Plugin implementation of the 'bunny_stream_textfield' widget.
 *
 * In upload mode, a new media creates a new Bunny video instead. The video
 * object is created when the media is saved, and the file is uploaded on the
 * media's upload page afterwards.
 */
#[FieldWidget(
  id: 'bunny_stream_textfield',
  label: new TranslatableMarkup('Bunny Stream'),
  field_types: ['bunny_stream_video'],
)]
class BunnyStreamWidget extends WidgetBase {

  /**
   * Constructor for the plugin.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'mode' => 'upload',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $element['mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('New media'),
      '#options' => $this->modeOptions(),
      '#default_value' => $this->getSetting('mode'),
      '#access' => $this->isUploadAllowed(),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $mode = $this->isUploadAllowed() ? $this->getSetting('mode') : 'existing';
    return [
      $this->t('New media: @mode', ['@mode' => $this->modeOptions()[$mode] ?? $mode]),
    ];
  }

  /**
   * Whether the media source of the field allows uploads.
   */
  protected function isUploadAllowed(): bool {
    $media_type = $this->entityTypeManager->getStorage('media_type')->load((string) $this->fieldDefinition->getTargetBundle());
    $source = $media_type instanceof MediaTypeInterface ? $media_type->getSource() : NULL;
    return $source instanceof BunnyStreamSource && $source->getLibrary()?->isUploadAllowed();
  }

  /**
   * Whether new media create a Bunny video for upload.
   *
   * @return bool
   *   TRUE if the widget is in upload mode and the media source allows it.
   */
  public function createsVideoForUpload(): bool {
    return $this->getSetting('mode') === 'upload' && $this->isUploadAllowed();
  }

  /**
   * Returns the options for the mode setting.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup>
   *   The mode labels keyed by mode.
   */
  protected function modeOptions(): array {
    return [
      'upload' => $this->t('Create a new video for upload'),
      'existing' => $this->t('Enter the UUID of an existing video'),
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param \Drupal\Core\Field\FieldItemListInterface<\Drupal\bunny_stream\Plugin\Field\FieldType\BunnyStreamVideoItem> $items
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $element['value'] = $element + [
      '#type' => 'textfield',
      '#default_value' => $items[$delta]->value ?? NULL,
      '#maxlength' => $this->getFieldSetting('max_length'),
    ];

    // Keep the stored video metadata. It is updated if the video changes.
    foreach (['width', 'height'] as $property) {
      $element[$property] = [
        '#type' => 'value',
        '#value' => $items[$delta]->{$property} ?? NULL,
      ];
    }

    $element['value']['#description'] = $this->t('Use this field to write the UUID of a video from Bunny Stream.');

    if (!$this->isUploadMode($items)) {
      // Videos managed through upload mode are deleted from Bunny along with
      // the media, so the stored UUID must not point to another video.
      if ($this->usesUploadMode($items)) {
        $element['value']['#disabled'] = TRUE;
        $element['value']['#description'] = $this->t('The UUID of the video in Bunny Stream.');
      }
      return $element;
    }

    $element['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Video title'),
      '#description' => $this->t('You can upload the video file after saving.'),
      '#maxlength' => 255,
      '#required' => $element['value']['#required'],
      '#weight' => -5,
    ];
    $element['value']['#access'] = FALSE;

    return $element;
  }

  /**
   * Whether the widget creates a new Bunny video instead of asking a UUID.
   *
   * @phpstan-param \Drupal\Core\Field\FieldItemListInterface<\Drupal\bunny_stream\Plugin\Field\FieldType\BunnyStreamVideoItem> $items
   *   The field values.
   */
  protected function isUploadMode(FieldItemListInterface $items): bool {
    return $items->getEntity()->isNew() && $this->usesUploadMode($items);
  }

  /**
   * Whether the widget is in upload mode and the media source allows it.
   *
   * @phpstan-param \Drupal\Core\Field\FieldItemListInterface<\Drupal\bunny_stream\Plugin\Field\FieldType\BunnyStreamVideoItem> $items
   *   The field values.
   */
  protected function usesUploadMode(FieldItemListInterface $items): bool {
    $entity = $items->getEntity();
    return $this->getSetting('mode') === 'upload'
      && $entity instanceof MediaInterface
      && $entity->getSource() instanceof BunnyStreamSource
      && $entity->getSource()->getLibrary()?->isUploadAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state): array {
    foreach ($values as &$value) {
      if (isset($value['title'])) {
        $value['value'] = trim($value['title']) === '' ? '' : BunnyStreamSourceInterface::PENDING_UPLOAD;
      }
      unset($value['title']);
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   *
   * In upload mode the title becomes the media name, which is also used as
   * the title of the Bunny video.
   *
   * @phpstan-param \Drupal\Core\Field\FieldItemListInterface<\Drupal\bunny_stream\Plugin\Field\FieldType\BunnyStreamVideoItem> $items
   *   The field values.
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state): void {
    parent::extractFormValues($items, $form, $form_state);

    $path = array_merge($form['#parents'], [$this->fieldDefinition->getName(), 0]);
    $values = NestedArray::getValue($form_state->getValues(), $path);
    if (!isset($values['title'])) {
      return;
    }

    $entity = $items->getEntity();
    $title = trim($values['title'] ?? '');
    if ($entity instanceof MediaInterface && $title !== '') {
      $entity->setName($title);
    }
    $form_state->set(BunnyStreamHooks::UPLOAD_MODE_KEY, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition): bool {
    $target_bundle = $field_definition->getTargetBundle();

    if (!parent::isApplicable($field_definition) || $field_definition->getTargetEntityTypeId() !== 'media' || !$target_bundle) {
      return FALSE;
    }

    return MediaType::load($target_bundle)?->getSource() instanceof BunnyStreamSourceInterface;
  }

}
