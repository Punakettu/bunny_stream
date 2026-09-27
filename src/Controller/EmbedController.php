<?php

declare(strict_types=1);

namespace Drupal\bunny_stream\Controller;

use Drupal\bunny_stream\BunnyEmbedTrait;
use Drupal\bunny_stream\EmbedUrlGenerator;
use Drupal\bunny_stream\Plugin\Field\FieldType\BunnyStreamVideoItem;
use Drupal\bunny_stream\Plugin\media\Source\BunnyStreamSource;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\media\MediaInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Renders embeds of videos that require a view token.
 *
 * @see \Drupal\bunny_stream\Plugin\Field\FieldFormatter\BunnyStreamEmbedFormatter
 */
final class EmbedController extends ControllerBase {

  use BunnyEmbedTrait;

  public function __construct(
    private readonly EmbedUrlGenerator $embedUrlGenerator,
    private readonly TimeInterface $time,
  ) {}

  /**
   * Checks that the media has a private video and the URL is signed.
   */
  public function access(MediaInterface $media, Request $request): AccessResultInterface {
    $source = $media->getSource();
    $library = $source instanceof BunnyStreamSource ? $source->getLibrary() : NULL;
    $result = AccessResult::allowedIf($library?->isTokenAuthenticationEnabled() && $this->embedUrlGenerator->isValid($media, $request))
      ->addCacheContexts(['url.query_args'])
      ->addCacheableDependency($media)
      ->addCacheableDependency($media->getBundleEntity());

    if ($library) {
      $result->addCacheableDependency($library);
    }

    return $result;
  }

  /**
   * Renders the embed of the media's video with a fresh token.
   */
  public function embed(MediaInterface $media, Request $request): array {
    $source = $media->getSource();
    $library = $source instanceof BunnyStreamSource ? $source->getLibrary() : NULL;
    $videoId = $media->getSource()->getSourceFieldValue($media);
    if (!$library || !$videoId) {
      throw new BadRequestHttpException();
    }

    $options = $this->embedUrlGenerator->getOptions($request);
    $expires = $this->time->getRequestTime() + (int) ($options[EmbedUrlGenerator::LIFETIME_OPTION] ?? 0);

    $query = array_intersect_key($options, array_flip(EmbedUrlGenerator::PLAYER_OPTIONS));
    $query['token'] = $library->createEmbedToken($videoId, $expires);
    $query['expires'] = $expires;

    $sourceField = $source->getSourceFieldDefinition($media->getBundleEntity());

    foreach ($media->get($sourceField->getName()) as $item) {
      assert($item instanceof BunnyStreamVideoItem);

      $render = $this->buildBunnyEmbed($library, $media, $item, $query, !empty($options[EmbedUrlGenerator::FULLSCREEN_OPTION]));
      $render['#cache']['max-age'] = 0;
      return $render;
    }

    throw new BadRequestHttpException();
  }

}
