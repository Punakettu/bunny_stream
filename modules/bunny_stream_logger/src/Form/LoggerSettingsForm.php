<?php

declare(strict_types = 1);

namespace Drupal\bunny_stream_logger\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Bunny stream logger settings for this site.
 */
final class LoggerSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'bunny_stream_logger_logger_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['bunny_stream_logger.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $row_limits = [100, 1000, 10000];
    $form['row_limit'] = [
      '#type' => 'select',
      '#title' => $this->t('Database log messages to keep'),
      '#default_value' => $this->config('bunny_stream_logger.settings')->get('row_limit'),
      '#options' => [0 => $this->t('All')] + array_combine($row_limits, $row_limits),
      '#description' => $this->t('The maximum number of messages to keep in the database logs.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('bunny_stream_logger.settings')
      ->set('row_limit', $form_state->getValue('row_limit'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
