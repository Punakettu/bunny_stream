<?php

namespace Drupal\bunny_stream_logger\Controller;

use Drupal\bunny_stream\WebhookStates;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Database\Query\TableSortExtender;
use Drupal\Core\Datetime\DateFormatterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for bunny_stream_logger routes.
 */
class BunnyLogController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('database'),
      $container->get('date.formatter')
    );
  }

  /**
   * Constructs a BunnyLogController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter service.
   */
  public function __construct(
    protected Connection $database,
    protected DateFormatterInterface $dateFormatter) {
  }

  /**
   * Displays a listing of database log messages.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return array
   *   A render array as expected by
   *   \Drupal\Core\Render\RendererInterface::render().
   */
  public function overview(Request $request) {

    $rows = [];

    $build['bunny_filter_form'] = $this->formBuilder()->getForm('Drupal\bunny_stream_logger\Form\BunnyLoggerFilterForm');

    $header = [
      [
        'data' => $this->t('Status'),
        'field' => 'b.status',
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      [
        'data' => $this->t('Date'),
        'field' => 'b.bid',
        'sort' => 'desc',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      $this->t('Video'),
      [
        'data' => $this->t('Library'),
        'field' => 'b.library',
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
    ];

    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = $this->database->select('bunny_stream_logger', 'b')
      ->extend(PagerSelectExtender::class)
      ->extend(TableSortExtender::class);

    $query->fields('b', [
      'bid',
      'status',
      'library',
      'video',
      'timestamp',
    ]);

    $session_filters = $request->getSession()->get('bunny_logger_filter', []);

    if (!empty($session_filters)) {
      foreach ($session_filters as $key => $value) {
        if (!empty($value)) {
          $query->condition($key, $value);
        }
      }
    }

    $result = $query
      ->limit(50)
      ->orderByHeader($header)
      ->execute();

    foreach ($result as $register) {
      $status = (string) WebhookStates::STATES[$register->status];
      $rows[] = [
        'data' => [
          $this->t($status),
          $this->dateFormatter->format($register->timestamp, 'short'),
          $register->video,
          $register->library,
        ],
      ];
    }

    $build['bunny_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No log messages available.'),
    ];

    $build['bunny_pager'] = ['#type' => 'pager'];

    return $build;
  }

}
