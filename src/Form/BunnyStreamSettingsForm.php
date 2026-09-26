<?php

declare(strict_types = 1);

namespace Drupal\bunny_stream\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Bunny Stream settings for this site.
 */
final class BunnyStreamSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'bunny_stream_bunny_stream_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['bunny_stream.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['webhook_hash'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Webhook hash'),
      '#config_target' => 'bunny_stream.settings:webhook_hash',
      '#description' => $this->t('Use this hash for the webhook, if the hash is 1234asdf the webhook endpoint will be "/bunny-stream/webhook/1234asdf".'),
    ];

    $form['webhook_value'] = [
      '#type' => 'item',
      '#markup' => '<b>Current webhook endpoint:</b> /bunny-stream/webhook/' . $this->config('bunny_stream.settings')->get('webhook_hash'),
    ];

    return parent::buildForm($form, $form_state);
  }

}
