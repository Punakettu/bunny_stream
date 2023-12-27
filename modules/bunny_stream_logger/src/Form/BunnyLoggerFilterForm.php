<?php

namespace Drupal\bunny_stream_logger\Form;

use Drupal\bunny_stream\WebhookStates;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the database logging filter form.
 *
 * @internal
 */
class BunnyLoggerFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bunny_logger_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $session_filters = $this->getRequest()->getSession()->get('bunny_logger_filter', []);

    $form['filters'] = [
      '#type' => 'details',
      '#title' => $this->t('Filter log messages'),
      '#open' => TRUE,
    ];

    $form['filters']['status'] = [
      '#title' => $this->t('Status'),
      '#type' => 'select',
      '#options' => WebhookStates::STATES,
      '#default_value' => $session_filters['status'] ?? '',
    ];

    $form['filters']['library'] = [
      '#title' => $this->t('Library'),
      '#type' => 'textfield',
      '#default_value' => $session_filters['library'] ?? '',
    ];

    $form['filters']['video'] = [
      '#title' => $this->t('Video UUID'),
      '#type' => 'textfield',
      '#default_value' => $session_filters['video'] ?? '',
    ];

    $form['filters']['actions'] = [
      '#type' => 'actions',
      '#attributes' => ['class' => ['container-inline']],
    ];
    $form['filters']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
    ];
    if (!empty($session_filters)) {
      $form['filters']['actions']['reset'] = [
        '#type' => 'submit',
        '#value' => $this->t('Reset'),
        '#limit_validation_errors' => [],
        '#submit' => ['::resetForm'],
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $filters = [
      'status',
      'library',
      'video',
    ];

    $session_filters = $this->getRequest()->getSession()->get('bunny_logger_filter', []);
    foreach ($filters as $filter) {
      if ($form_state->hasValue($filter)) {
        $session_filters[$filter] = $form_state->getValue($filter);
      }
    }
    $this->getRequest()->getSession()->set('bunny_logger_filter', $session_filters);
  }

  /**
   * Resets the filter form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $this->getRequest()->getSession()->remove('bunny_logger_filter');
  }

}
