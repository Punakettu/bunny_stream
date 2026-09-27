<?php

declare(strict_types=1);

namespace Drupal\Tests\bunny_stream\Kernel;

use Drupal\bunny_stream\Plugin\Field\FieldWidget\BunnyStreamWidget;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\Media;
use Drupal\Tests\bunny_stream\Traits\BunnyMediaTestTrait;
use Drupal\Tests\bunny_stream\Traits\HttpClientMockTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the mode setting of the Bunny Stream widget.
 */
#[CoversClass(BunnyStreamWidget::class)]
#[Group('bunny_stream')]
#[RunTestsInSeparateProcesses]
final class BunnyStreamWidgetTest extends KernelTestBase {

  use BunnyMediaTestTrait;
  use HttpClientMockTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'bunny_stream',
    'field',
    'file',
    'image',
    'media',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Services keep the client they were created with, so mock it first.
    $this->mockHttpClient();
    $this->setUpBunnyMedia();
  }

  /**
   * The form shows either the title or the UUID field, never a choice.
   */
  #[DataProvider('providerMode')]
  public function testMode(?string $mode, bool $new, bool $expect_upload, bool $allow_upload = TRUE, bool $expect_disabled = FALSE): void {
    if (!$allow_upload) {
      $this->disallowUploads();
    }
    $settings = $mode === NULL ? [] : ['mode' => $mode];
    EntityFormDisplay::load('media.video.default')?->setComponent($this->getSourceFieldName(), [
      'type' => 'bunny_stream_textfield',
      'settings' => $settings,
    ])->save();

    $media = Media::create(['bundle' => 'video', 'name' => 'Video']);
    if (!$new) {
      $media->set($this->getSourceFieldName(), 'existing-guid')->save();
    }
    $form = $this->container->get('entity.form_builder')->getForm($media, $new ? 'add' : 'edit');
    $element = $form[$this->getSourceFieldName()]['widget'][0];

    $this->assertArrayNotHasKey('mode', $element);
    $this->assertSame($expect_upload, isset($element['title']));
    $this->assertSame(!$expect_upload, $element['value']['#access'] ?? TRUE);
    $this->assertSame($expect_disabled, $element['value']['#disabled'] ?? FALSE);
  }

  /**
   * Data provider for testMode().
   */
  public static function providerMode(): array {
    return [
      'default setting' => [NULL, TRUE, TRUE],
      'upload' => ['upload', TRUE, TRUE],
      'upload when editing' => ['upload', FALSE, FALSE, TRUE, TRUE],
      'existing' => ['existing', TRUE, FALSE],
      'existing when editing' => ['existing', FALSE, FALSE],
      'upload not allowed by source' => ['upload', TRUE, FALSE, FALSE],
      'upload not allowed by source when editing' => ['upload', FALSE, FALSE, FALSE],
    ];
  }

}
