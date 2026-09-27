<?php

declare(strict_types=1);

namespace Drupal\bunny_stream\Hook;

use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Requirements for bunny_stream.
 */
final class BunnyStreamLibraryHooks {

  use StringTranslationTrait;

  /**
   * Path of the TUS client library.
   */
  public const string TUS_LIBRARY_PATH = 'libraries/tus-js-client/dist/tus.min.js';

  public function __construct(
    private readonly LibraryDiscoveryInterface $libraryDiscovery,
    #[Autowire(param: 'app.root')]
    private readonly string $root,
  ) {}

  /**
   * Implements hook_library_info_alter().
   */
  #[Hook('library_info_alter')]
  public function libraryInfoAlter(array &$libraries, string $extension): void {
    if ($extension !== 'bunny_stream' || !isset($libraries['tus-js-client']) || file_exists($this->root . '/' . self::TUS_LIBRARY_PATH)) {
      return;
    }

    // Loads tus-js-client from a CDN if it is not installed locally.
    $libraries['tus-js-client']['js'] = [
      self::tusJsLink($libraries['tus-js-client']['version']) => [
        'type' => 'external',
        'minified' => TRUE,
      ],
    ];
  }

  /**
   * Implements hook_runtime_requirements().
   */
  #[Hook('runtime_requirements')]
  public function runtime(): array {
    $expected = $this->libraryDiscovery->getLibraryByName('bunny_stream', 'tus-js-client')['version'] ?? '';
    $library_dir = dirname(self::TUS_LIBRARY_PATH, 2);
    $requirement = [
      'title' => $this->t('Bunny Stream upload library'),
    ];

    if (!file_exists($this->root . '/' . self::TUS_LIBRARY_PATH)) {
      $requirement['value'] = $this->t('Not installed, loaded from CDN');
      $requirement['severity'] = RequirementSeverity::Warning;
      $requirement['description'] = $this->t('The tus-js-client library is loaded from <a href=":cdn">jsDelivr</a>. To serve it locally, install tus-js-client @version so that %path exists.', [
        ':cdn' => self::tusJsLink($expected),
        '@version' => $expected,
        '%path' => '/' . self::TUS_LIBRARY_PATH,
      ]);
      return ['bunny_stream_tus' => $requirement];
    }

    $package = @file_get_contents($this->root . '/' . $library_dir . '/package.json');
    $installed = $package ? (json_decode($package, TRUE)['version'] ?? NULL) : NULL;

    $requirement['value'] = $installed ?? $this->t('Installed, unknown version');
    $requirement['severity'] = RequirementSeverity::OK;
    if (
      !$installed ||
      version_compare($installed, (string) ((int) $expected + 1), '>=') ||
      version_compare($installed, $expected, '<')
    ) {
      $requirement['severity'] = RequirementSeverity::Warning;
      $requirement['description'] = $this->t('Bunny Stream is tested with tus-js-client @expected, installed version is @version.', [
        '@expected' => $expected,
        '@version' => $installed,
      ]);
    }

    return ['bunny_stream_tus' => $requirement];
  }

  /**
   * TUS library jsDeliv CDN link.
   */
  private static function tusJsLink(string $version): string {
    return sprintf('https://cdn.jsdelivr.net/npm/tus-js-client@%s/dist/tus.min.js', $version);
  }

}
