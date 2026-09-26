<?php

namespace Drupal\bunny_stream\Plugin\Field\FieldWidget;

use Drupal\bunny_stream\BunnyStreamSourceInterface;
use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextfieldWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\media\Entity\MediaType;

/**
 * Plugin implementation of the 'bunny_stream_textfield' widget.
 */
#[FieldWidget(
  id: 'bunny_stream_textfield',
  label: new TranslatableMarkup('Bunny Stream'),
  field_types: ['string'],
)]
class BunnyStreamWidget extends StringTextfieldWidget {

  /**
   * {@inheritdoc}
   *
   * @phpstan-param \Drupal\Core\Field\FieldItemListInterface<\Drupal\Core\Field\Plugin\Field\FieldType\StringItem> $items
   *   Array of default values for this field.
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['value']['#description'] = $this->t('Use this field to write the UUID of a video from Bunny Stream.');

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $target_bundle = $field_definition->getTargetBundle();

    if (!parent::isApplicable($field_definition) || $field_definition->getTargetEntityTypeId() !== 'media' || !$target_bundle) {
      return FALSE;
    }

    return MediaType::load($target_bundle)?->getSource() instanceof BunnyStreamSourceInterface;
  }

}
