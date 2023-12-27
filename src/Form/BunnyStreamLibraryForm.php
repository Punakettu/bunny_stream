<?php

declare(strict_types = 1);

namespace Drupal\bunny_stream\Form;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Bunny_stream_library form.
 */
final class BunnyStreamLibraryForm extends EntityForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $entity;

  /**
   * Constructor of the class to inject services.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date.formatter service.
   * @param \GuzzleHttp\Client $client
   *   The http_client service.
   */
  public function __construct(
    protected DateFormatterInterface $dateFormatter,
    protected Client $client
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('date.formatter'),
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {

    $form = parent::form($form, $form_state);

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#required' => TRUE,
      '#description' => $this->t('Name of the library, used only in Drupal..'),
    ];

    $form['id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Library ID'),
      '#default_value' => $this->entity->id(),
      '#disabled' => !$this->entity->isNew(),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#description' => $this->t('The library ID, just numbers, will be used like entity ID.'),
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $this->entity->get('description'),
      '#description' => $this->t('Set description for this library, just for information porpoises.'),
    ];

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->get('api_key'),
      '#required' => TRUE,
      '#description' => $this->t('The API key for the request to Bunny API. You can get it from Stream -> Library -> API.'),
    ];

    $form['cdn_hostname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CDN hostname'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->get('cdn_hostname'),
      '#required' => TRUE,
      '#description' => $this->t('The hostname to use to link the videos on the site. You can get it from Stream -> Library -> API.'),
    ];

    $form['pull_zone'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pull zone'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->get('pull_zone'),
      '#required' => FALSE,
    ];

    $form['token_authentication_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Token Authentication Key'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->get('token_authentication_key'),
      '#required' => FALSE,
      '#description' => $this->t('This is used to generate a token hash, to use this, you must enable the option "Embed View Token Authentication" inside Stream -> Library -> Security. If this field has some value, the module will assume that this library is private.'),
    ];

    $options = [3600, 10800, 21600, 43200, 86400, 604800];
    $form['time'] = [
      '#type' => 'select',
      '#title' => $this->t('Expiration time'),
      '#description' => $this->t('Chose the time to expire the video, this value will be used only if token authentication is enabled.'),
      '#default_value' => $this->entity->get('time') ?? 43200,
      '#options' => array_map([$this->dateFormatter, 'formatInterval'], array_combine($options, $options)),
      '#states' => [
        'visible' => [
          ':input[name="token_authentication_key"]' => ['filled' => TRUE],
        ],
        'required' => [
          ':input[name="token_authentication_key"]' => ['filled' => TRUE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $library_id = $form_state->getValue('id');
    $api_key = $form_state->getValue('api_key');

    $error_message = $this->t('Please, check if API Key @api_key and library ID @library_id exists and are valid.',
      [
        '@api_key' => $api_key,
        '@library_id' => $library_id,
      ]
    );

    try {
      $response = $this->client->request('GET', 'https://video.bunnycdn.com/library/' . $library_id . '/videos', [
        'headers' => [
          'AccessKey' => $api_key,
          'accept' => 'application/json',
        ],
      ]);

      if ($response->getStatusCode() !== 200) {
        $form_state->setError($form, (string) $error_message);
      }
    }
    catch (GuzzleException $exception) {
      $form_state->setError($form, (string) $error_message);
    }

  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $message_args = ['%label' => $this->entity->label()];
    $this->messenger()->addStatus(
      match($result) {
        \SAVED_NEW => $this->t('Created new bunny stream library %label.', $message_args),
        \SAVED_UPDATED => $this->t('Updated bunny stream library %label.', $message_args),
      }
    );
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

}
