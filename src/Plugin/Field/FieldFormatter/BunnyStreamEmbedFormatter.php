<?php

declare(strict_types=1);

namespace Drupal\bunny_stream\Plugin\Field\FieldFormatter;

use Drupal\bunny_stream\BunnyEmbedTrait;
use Drupal\bunny_stream\BunnyStreamSourceInterface;
use Drupal\bunny_stream\EmbedUrlGenerator;
use Drupal\bunny_stream\Plugin\media\Source\BunnyStreamSource;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Htmx\Htmx;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\media\Entity\MediaType;
use Drupal\media\MediaInterface;

/**
 * Plugin implementation of the 'Bunny Stream' formatter.
 *
 * This plugin never should be used out of Media, loads information
 * of Media Source to obtain required data.
 *
 * @extends \Drupal\Core\Field\FormatterBase<\Drupal\Core\Field\FieldItemListInterface<\Drupal\bunny_stream\Plugin\Field\FieldType\BunnyStreamVideoItem>>
 */
#[FieldFormatter(
  id: 'bunny_stream_embed',
  label: new TranslatableMarkup('Bunny Stream Embed'),
  field_types: ['bunny_stream_video'],
)]
class BunnyStreamEmbedFormatter extends FormatterBase {

  use BunnyEmbedTrait;

  /**
   * Constructor for the plugin.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date.formatter service.
   * @param \Drupal\bunny_stream\EmbedUrlGenerator $embedUrlGenerator
   *   The embed URL generator.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected DateFormatterInterface $dateFormatter,
    protected EmbedUrlGenerator $embedUrlGenerator,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   *
   * @phsptan-return FieldSettings
   */
  public static function defaultSettings(): array {
    return [
      'responsive' => TRUE,
      'autoplay' => FALSE,
      'preload' => TRUE,
      'loop' => FALSE,
      'muted' => FALSE,
      'allow_fullscreen' => TRUE,
      'time' => 21600,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['responsive'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Responsive'),
      '#default_value' => $this->getSetting('responsive'),
      '#description' => $this->t('Allow video to be responsive.'),
    ];

    $form['autoplay'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autoplay'),
      '#default_value' => $this->getSetting('autoplay'),
      '#description' => $this->t('Enable autoplay of the video.'),
    ];

    $form['preload'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Preload'),
      '#default_value' => $this->getSetting('preload'),
      '#description' => $this->t('Preload the video to play it faster.'),
    ];

    $form['loop'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Loop'),
      '#default_value' => $this->getSetting('loop'),
      '#description' => $this->t('Enable loop of the video.'),
    ];

    $form['muted'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Muted'),
      '#default_value' => $this->getSetting('muted'),
      '#description' => $this->t('Mute the video.'),
    ];

    $form['allow_fullscreen'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow Fullscreen'),
      '#default_value' => $this->getSetting('allow_fullscreen'),
      '#description' => $this->t('Allow video to be fullscreen.'),
    ];

    $options = [3600, 10800, 21600, 43200, 86400, 604800];
    $form['time'] = [
      '#type' => 'select',
      '#title' => $this->t('Expiration time'),
      '#description' => $this->t('Choose the time to expire the video, this value will be used only if token authentication is set on library configuration.'),
      '#default_value' => $this->getSetting('time') ?? 43200,
      '#options' => array_map([$this->dateFormatter, 'formatInterval'], array_combine($options, $options)),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $summary[] = $this->t('Responsive: @enabled', [
      '@enabled' => $this->getSetting('responsive') ? $this->t('Enabled') : $this->t('Disabled'),
    ]);

    $summary[] = $this->t('Autoplay: @enabled', [
      '@enabled' => $this->getSetting('autoplay') ? $this->t('Enabled') : $this->t('Disabled'),
    ]);

    $summary[] = $this->t('Preload: @enabled', [
      '@enabled' => $this->getSetting('preload') ? $this->t('Enabled') : $this->t('Disabled'),
    ]);

    $summary[] = $this->t('Loop: @enabled', [
      '@enabled' => $this->getSetting('loop') ? $this->t('Enabled') : $this->t('Disabled'),
    ]);

    $summary[] = $this->t('Muted: @enabled', [
      '@enabled' => $this->getSetting('muted') ? $this->t('Enabled') : $this->t('Disabled'),
    ]);

    $summary[] = $this->t('Allow fullscreen: @enabled', [
      '@enabled' => $this->getSetting('allow_fullscreen') ? $this->t('Enabled') : $this->t('Disabled'),
    ]);

    $summary[] = $this->t('Expiration time: @time', [
      '@time' => $this->dateFormatter->formatInterval($this->getSetting('time')),
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Field\FieldItemListInterface<\Drupal\bunny_stream\Plugin\Field\FieldType\BunnyStreamVideoItem> $items
   *   The field values to be rendered.
   * @param string $langcode
   *   The language that should be used to render the field.
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $element = [];

    $media = $items->getEntity();
    $source = $media instanceof MediaInterface ? $media->getSource() : NULL;
    $library = $source instanceof BunnyStreamSource ? $source->getLibrary() : NULL;
    if (!$library) {
      return $element;
    }

    $settings = [
      'responsive' => $this->getSetting('responsive') ? 'true' : 'false',
      'autoplay' => $this->getSetting('autoplay') ? 'true' : 'false',
      'loop' => $this->getSetting('loop') ? 'true' : 'false',
      'muted' => $this->getSetting('muted') ? 'true' : 'false',
      'preload' => $this->getSetting('preload') ? 'true' : 'false',
    ];

    foreach ($items as $delta => $item) {
      if ($library->isTokenAuthenticationEnabled()) {
        // The token expires, so it is fetched separately with HTMX to keep
        // the page cacheable.
        $render = [
          '#theme' => 'bunny_embed',
          '#width' => $item->width,
          '#height' => $item->height,
          '#cache' => ['tags' => $library->getCacheTags()],
        ];
        if (!$media->isNew()) {
          $url = $this->embedUrlGenerator->getUrl($media, $settings + [
            EmbedUrlGenerator::FULLSCREEN_OPTION => $this->getSetting('allow_fullscreen') ? '1' : '0',
            EmbedUrlGenerator::LIFETIME_OPTION => (string) $this->getSetting('time'),
          ]);

          // Signed embed URLs have expiration date. We render a
          // placeholder and fetch the signed URL with Htmx, so
          // the page stays cacheable.
          (new Htmx())
            ->get($url)
            ->trigger('revealed')
            ->select('.bunny-embed')
            ->swap('outerHTML')
            ->applyTo($render);
        }
      }
      else {
        $render = $this->buildBunnyEmbed($library, $media, $item, $settings, (bool) $this->getSetting('allow_fullscreen'));
        $render['#cache']['tags'] = $library->getCacheTags();
      }

      $element[$delta] = $render;
    }
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
    return MediaType::load($target_bundle)->getSource() instanceof BunnyStreamSourceInterface;
  }

}
