<?php

declare(strict_types=1);

namespace Drupal\Tests\bunny_stream\Kernel\Form;

use Drupal\bunny_stream\Form\BunnyStreamMediaLibraryForm;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media_library\MediaLibraryState;
use Drupal\Tests\bunny_stream\Traits\BunnyMediaTestTrait;
use Drupal\Tests\bunny_stream\Traits\HttpClientMockTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the input elements of the Bunny Stream media library add form.
 */
#[CoversClass(BunnyStreamMediaLibraryForm::class)]
#[Group('bunny_stream')]
#[RunTestsInSeparateProcesses]
final class BunnyStreamMediaLibraryFormTest extends KernelTestBase {

  use BunnyMediaTestTrait;
  use HttpClientMockTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'bunny_stream',
    'field',
    'file',
    'filter',
    'image',
    'media',
    'media_library',
    'system',
    'user',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Services keep the client they were created with, so mock it first.
    $this->mockHttpClient();
    $this->setUpBunnyMedia();
    $this->installConfig(['media_library']);
  }

  /**
   * The form shows either the upload or the UUID input, following the widget.
   */
  #[DataProvider('providerMode')]
  public function testMode(?string $mode, string $display, bool $expect_upload, bool $allow_upload = TRUE): void {
    if (!$allow_upload) {
      $this->disallowUploads();
    }
    $form_display = EntityFormDisplay::load("media.video.$display") ?? EntityFormDisplay::create([
      'targetEntityType' => 'media',
      'bundle' => 'video',
      'mode' => $display,
      'status' => TRUE,
    ]);
    $form_display->setComponent($this->getSourceFieldName(), [
      'type' => 'bunny_stream_textfield',
      'settings' => $mode === NULL ? [] : ['mode' => $mode],
    ])->save();

    $form_state = new FormState();
    $form_state->set('media_library_state', MediaLibraryState::create('test', ['video'], 'video', -1));
    $container = $this->container->get('form_builder')->buildForm(BunnyStreamMediaLibraryForm::class, $form_state)['container'];

    $this->assertSame($expect_upload, isset($container['upload_title'], $container['create']));
    $this->assertSame(!$expect_upload, isset($container['id'], $container['submit']));
  }

  /**
   * Data provider for testMode().
   */
  public static function providerMode(): array {
    return [
      'default setting' => [NULL, 'media_library', TRUE],
      'upload' => ['upload', 'media_library', TRUE],
      'existing' => ['existing', 'media_library', FALSE],
      'existing on default display' => ['existing', 'default', FALSE],
      'upload not allowed by source' => ['upload', 'media_library', FALSE, FALSE],
    ];
  }

}
