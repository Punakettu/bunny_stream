<?php

declare(strict_types=1);

namespace Drupal\bunny_stream\Form;

use Drupal\bunny_stream\BunnyStreamLibraryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Utils;

/**
 * Bunny_stream_library form.
 */
final class BunnyStreamLibraryForm extends EntityForm {

  /**
   * Constructor of the class to inject services.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date.formatter service.
   * @param \GuzzleHttp\ClientInterface $client
   *   The http_client service.
   */
  public function __construct(
    protected DateFormatterInterface $dateFormatter,
    protected ClientInterface $client,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    assert($this->entity instanceof BunnyStreamLibraryInterface);

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#required' => TRUE,
      '#description' => $this->t('Name of the library, used only in Drupal.'),
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $this->entity->get('description'),
      '#description' => $this->t('Set description for this library. used only in Drupal.'),
    ];

    $form['id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Library ID'),
      '#default_value' => $this->entity->id(),
      '#disabled' => !$this->entity->isNew(),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#description' => $this->t('The library ID. You can get it from Stream -> Library -> API.'),
    ];

    $form['cdn_hostname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CDN hostname'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->get('cdn_hostname'),
      '#required' => TRUE,
      '#description' => $this->t('The hostname to use to link the videos on the site. You can get it from Stream -> Library -> API.'),
    ];

    $form['read_only_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Read-only API key'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->get('read_only_api_key'),
      '#required' => TRUE,
      '#description' => $this->t('Used to read videos from Bunny API. Bunny also signs webhook requests with this key. You can get it from Stream -> Library -> API.'),
    ];

    $form['allow_upload'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow video uploads'),
      '#default_value' => $this->entity->get('allow_upload'),
      '#description' => $this->t('Allow users to upload files from Drupal. When disabled, videos can only be added with the UUID.'),
    ];

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->get('api_key'),
      '#description' => $this->t('The read/write API key, used to create and upload videos. You can get it from Stream -> Library -> API.'),
      '#states' => [
        'visible' => [':input[name="allow_upload"]' => ['checked' => TRUE]],
        'required' => [':input[name="allow_upload"]' => ['checked' => TRUE]],
      ],
    ];

    $form['webhook_url'] = [
      '#type' => 'item',
      '#title' => $this->t('Webhook URL'),
      '#markup' => Url::fromRoute('bunny_stream.webhook', [], ['absolute' => TRUE])->toString(),
      '#description' => $this->t('Set this as the "Webhook URL" in Stream -> Library -> API.'),
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

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $library_id = (string) $form_state->getValue('id');
    $keys = ['read_only_api_key' => $this->t('Read-only API key')];

    if ($form_state->getValue('allow_upload')) {
      if (mb_trim((string) $form_state->getValue('api_key')) === '') {
        $form_state->setErrorByName('api_key', $this->t('The API key is required to allow video uploads.'));
      }
      else {
        $keys['api_key'] = $this->t('API key');
      }
    }
    else {
      // A library without uploads never needs the read/write key.
      $form_state->setValue('api_key', NULL);
    }

    // Empty required fields already have an error.
    $keys = array_filter($keys, fn (string $name) => (string) $form_state->getValue($name) !== '', ARRAY_FILTER_USE_KEY);

    // Check the keys concurrently.
    $promises = [];
    foreach (array_keys($keys) as $name) {
      $promises[$name] = $this->client->requestAsync('GET', 'https://video.bunnycdn.com/library/' . $library_id . '/videos', [
        'headers' => [
          'AccessKey' => (string) $form_state->getValue($name),
          'accept' => 'application/json',
        ],
      ]);
    }

    foreach (Utils::settle($promises)->wait() as $name => $result) {
      if ($result['state'] !== PromiseInterface::FULFILLED || $result['value']->getStatusCode() !== 200) {
        $form_state->setErrorByName($name, $this->t('Please, check that %key is correct and library ID @library_id exists.', [
          '%key' => $keys[$name],
          '@library_id' => $library_id,
        ]));
      }
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
        default => throw new \LogicException(),
      }
    );
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

}
