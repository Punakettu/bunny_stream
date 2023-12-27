<?php

declare(strict_types = 1);

namespace Drupal\bunny_stream\Plugin\Field\FieldFormatter;

use Drupal\bunny_stream\BunnyStreamSourceInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\media\Entity\MediaType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'Bunny Stream' formatter.
 *
 * This plugin never should be used out of Media, loads information
 * of Media Source to obtain required data.
 *
 * @FieldFormatter(
 *   id = "bunny_stream_embed",
 *   label = @Translation("Bunny Stream Embed"),
 *   field_types = {"string"},
 * )
 */
class BunnyStreamEmbedFormatter extends FormatterBase {

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
   */
  public function __construct($plugin_id,
  $plugin_definition,
  FieldDefinitionInterface $field_definition,
  array $settings,
  $label,
  $view_mode,
  array $third_party_settings,
    protected EntityTypeManagerInterface $entityTypeManager
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'responsive' => 1,
      'autoplay' => 0,
      'preload' => 1,
      'loop' => 0,
      'muted' => 0,
      'allow_fullscreen' => 1,
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

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $element = [];

    $field_definition = $items->getFieldDefinition();
    $bundle = $field_definition->getTargetBundle();
    /** @var \Drupal\media\Entity\MediaType $media_entity */
    $media_entity = $this->entityTypeManager->getStorage('media_type')->load($bundle);

    /** @var \Drupal\bunny_stream\Plugin\media\Source\BunnyStreamSource $source */
    $source = $media_entity->getSource();
    /** @var \Drupal\bunny_stream\Entity\BunnyStreamLibrary $library */
    $library = $source->getLibrary();
    $library_id = $library->id();

    foreach ($items as $delta => $item) {

      $video_id = $item->value;
      $video_url = strtr(
        "//iframe.mediadelivery.net/embed/{library_id}/{video_id}",
        ["{library_id}" => $library_id, "{video_id}" => $video_id]
      );

      $url = Url::fromUri($video_url);

      $settings = [];
      if ($this->getSetting('responsive')) {
        $settings['responsive'] = 'true';
      }

      if ($this->getSetting('autoplay')) {
        $settings['autoplay'] = 'true';
      }

      if ($this->getSetting('loop')) {
        $settings['loop'] = 'true';
      }

      if ($this->getSetting('muted')) {
        $settings['muted'] = 'true';
      }

      if ($this->getSetting('preload')) {
        $settings['preload'] = 'true';
      }

      $token_auth = $library->get('token_authentication_key');

      if (!empty($token_auth)) {
        $time = time() + $library->get('time');
        $security_token = hash("sha256", $token_auth . $video_id . $time);

        $settings['token'] = $security_token;
        $settings['expires'] = $time;

        $url->setOptions(['query' => $settings]);

        // We can't cache videos with expiration time, so let's use BigPipe
        // to avoid cache.
        $render = [
          '#lazy_builder' => [
            '\Drupal\bunny_stream\LazyEmbedLoader::lazyLoad',
            [
              $url->toString(),
              $this->getSetting('allow_fullscreen'),
            ],
          ],
          '#create_placeholder' => TRUE,
          '#lazy_builder_preview' => [
            '#attributes' => ['id' => 'toolbar-link-preview'],
            '#type' => 'container',
            '#markup' => 'Loading video...',
          ],
        ];
      }
      else {
        $render = [
          '#theme' => "bunny_embed",
          '#url' => $url->toString(),
          '#options' => ['allow_fullscreen' => $this->getSetting('allow_fullscreen')],
        ];
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
