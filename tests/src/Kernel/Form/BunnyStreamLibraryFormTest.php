<?php

declare(strict_types=1);

namespace Drupal\Tests\bunny_stream\Kernel\Form;

use Drupal\bunny_stream\BunnyStreamLibraryInterface;
use Drupal\bunny_stream\Form\BunnyStreamLibraryForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\bunny_stream\Traits\HttpClientMockTrait;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests submitting the library config entity form.
 */
#[CoversClass(BunnyStreamLibraryForm::class)]
#[Group('bunny_stream')]
#[RunTestsInSeparateProcesses]
final class BunnyStreamLibraryFormTest extends KernelTestBase {

  use HttpClientMockTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'bunny_stream',
  ];

  /**
   * A valid submission saves the library.
   */
  public function testSubmit(): void {
    $this->mockHttpClient(new Response(200, [], '{"items": []}'), new Response(200, [], '{"items": []}'));

    $form_state = $this->submitForm(self::values());

    $this->assertSame([], $form_state->getErrors());

    // Both keys are checked.
    $this->assertSame(
      ['44444444-4444-4444-8444-444444444444', '11111111-1111-4111-8111-111111111111'],
      array_map(fn ($request) => $request->getHeaderLine('AccessKey'), $this->getHttpRequests()),
    );

    $library = $this->container->get(EntityTypeManagerInterface::class)
      ->getStorage('bunny_stream_library')
      ->loadUnchanged('12345');
    $this->assertInstanceOf(BunnyStreamLibraryInterface::class, $library);
    $expected = ['allow_upload' => TRUE] + self::values();
    $actual = array_intersect_key($library->toArray(), $expected);
    ksort($expected);
    ksort($actual);
    $this->assertSame($expected, $actual);
  }

  /**
   * A library without uploads needs and stores only the read-only key.
   */
  public function testSubmitWithoutUploads(): void {
    $this->mockHttpClient(new Response(200, [], '{"items": []}'));

    $form_state = $this->submitForm(['allow_upload' => NULL] + self::values());
    $this->assertSame([], $form_state->getErrors());

    $this->assertSame(['44444444-4444-4444-8444-444444444444'], array_map(fn ($request) => $request->getHeaderLine('AccessKey'), $this->getHttpRequests()));

    $library = $this->container->get(EntityTypeManagerInterface::class)
      ->getStorage('bunny_stream_library')
      ->loadUnchanged('12345');
    $this->assertInstanceOf(BunnyStreamLibraryInterface::class, $library);
    $this->assertFalse($library->get('allow_upload'));
    $this->assertNull($library->get('api_key'));
    $this->assertFalse($library->isUploadAllowed());
  }

  /**
   * Uploads require the read/write key.
   */
  public function testSubmitUploadsWithoutApiKey(): void {
    $this->mockHttpClient(new Response(200, [], '{"items": []}'));

    $form_state = $this->submitForm(['api_key' => ''] + self::values());
    $this->assertArrayHasKey('api_key', $form_state->getErrors());
  }

  /**
   * Rejected credentials fail validation and nothing is saved.
   */
  public function testSubmitInvalidCredentials(): void {
    $this->mockHttpClient(new Response(401), new Response(401));

    $form_state = $this->submitForm(self::values());
    $this->assertNotEmpty($form_state->getErrors());

    $this->assertNull($this->container->get(EntityTypeManagerInterface::class)
      ->getStorage('bunny_stream_library')
      ->loadUnchanged('12345'));
  }

  /**
   * Submits the add form programmatically.
   *
   * @param array<string, string|int|null> $values
   *   The submitted values.
   */
  private function submitForm(array $values): FormStateInterface {
    $entityTypeManager = $this->container->get(EntityTypeManagerInterface::class);
    $entity = $entityTypeManager->getStorage('bunny_stream_library')->create();

    $formObject = $entityTypeManager
      ->getFormObject('bunny_stream_library', 'add')
      ->setEntity($entity);

    $formState = (new FormState())->setValues($values + ['op' => 'Save']);
    $this->container->get(FormBuilderInterface::class)->submitForm($formObject, $formState);
    return $formState;
  }

  /**
   * Valid values for library.
   *
   * @phpstan-return array<string, string|int>
   */
  private static function values(): array {
    return [
      'id' => '12345',
      'label' => 'Test library',
      'description' => 'Library description',
      'read_only_api_key' => '44444444-4444-4444-8444-444444444444',
      'allow_upload' => 1,
      'api_key' => '11111111-1111-4111-8111-111111111111',
      'cdn_hostname' => 'vz-12345.b-cdn.net',
      'pull_zone' => 'vz-12345',
      'token_authentication_key' => '9c8b7a6d-5e4f-4a3b-b2c1-d0e9f8a7b6c5',
    ];
  }

}
