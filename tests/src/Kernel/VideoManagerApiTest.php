<?php

declare(strict_types=1);

namespace Drupal\Tests\bunny_stream\Kernel;

use Drupal\bunny_stream\Bunny\DTO\BunnyCollection;
use Drupal\bunny_stream\Bunny\DTO\BunnyCollectionList;
use Drupal\bunny_stream\Bunny\DTO\BunnyVideo;
use Drupal\bunny_stream\Bunny\VideoManager;
use Drupal\bunny_stream\BunnyStreamManagerFactoryInterface;
use Drupal\bunny_stream\Entity\BunnyStreamLibrary;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\bunny_stream\Traits\ApiFixtureTrait;
use Drupal\Tests\bunny_stream\Traits\HttpClientMockTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Bunny Stream API responses.
 */
#[CoversClass(VideoManager::class)]
#[Group('bunny_stream')]
#[RunTestsInSeparateProcesses]
final class VideoManagerApiTest extends KernelTestBase {

  use ApiFixtureTrait;
  use HttpClientMockTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'bunny_stream',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->mockHttpClient();

    BunnyStreamLibrary::create([
      'id' => '12345',
      'label' => 'Test library',
      'api_key' => '22222222-2222-4222-8222-222222222222',
      'read_only_api_key' => '33333333-3333-4333-8333-333333333333',
      'cdn_hostname' => 'vz-12345.b-cdn.net',
    ])->save();
  }

  /**
   * API responses are deserialized.
   *
   * @param string $method
   *   The video manager method.
   * @param array $arguments
   *   Arguments of the method.
   * @param class-string $class
   *   The expected result class.
   * @param array<string, mixed> $expected
   *   Expected property values of the result.
   */
  #[DataProvider('responseProvider')]
  public function testResponse(string $method, array $arguments, string $class, array $expected): void {
    $this->mockHttpClient($this->getFixtureResponse($method));

    $manager = $this->container->get(BunnyStreamManagerFactoryInterface::class)
      ->getVideoManager((string) '12345');
    $this->assertInstanceOf(VideoManager::class, $manager);

    $result = $manager->$method(...$arguments);

    $this->assertInstanceOf($class, $result);
    foreach ($expected as $property => $value) {
      $this->assertSame($value, $result->$property, $property);
    }
  }

  /**
   * Data provider for testResponse().
   */
  public static function responseProvider(): array {
    return [
      'get video' => [
        'getVideo', ['video-guid'], BunnyVideo::class,
        [
          'guid' => 'video-guid',
          'title' => 'Kissa leikkii',
          'framerate' => 29.97,
          'collectionId' => 'collection-guid',
          'smartGenerateFeaturesStatus' => ['title' => 3, 'description' => NULL],
          'hasOriginal' => TRUE,
        ],
      ],
      'create video' => [
        'createVideo', ['Kissa leikkii'], BunnyVideo::class,
        [
          'guid' => 'video-guid',
          'description' => '',
          'rotation' => NULL,
          'availableResolutions' => NULL,
          'thumbnailUrl' => '',
          'hasOriginal' => NULL,
        ],
      ],
      'get collection' => [
        'getCollection', ['collection-guid', TRUE], BunnyCollection::class,
        [
          'guid' => 'collection-guid',
          'name' => 'Kissat',
          'videoCount' => 2,
          'previewImageUrls' => [
            'https://vz-12345.b-cdn.net/video-guid/thumbnail.jpg',
            'https://vz-12345.b-cdn.net/other-video-guid/thumbnail.jpg',
          ],
        ],
      ],
      'create collection' => [
        'createCollection', ['Kissat'], BunnyCollection::class,
        [
          'guid' => 'collection-guid',
          'previewVideoIds' => '',
          'previewImageUrls' => [],
        ],
      ],
      'list collections' => [
        'listCollections', [], BunnyCollectionList::class,
        [
          'totalItems' => 2,
          'currentPage' => 1,
          'itemsPerPage' => 100,
        ],
      ],
    ];
  }

}
