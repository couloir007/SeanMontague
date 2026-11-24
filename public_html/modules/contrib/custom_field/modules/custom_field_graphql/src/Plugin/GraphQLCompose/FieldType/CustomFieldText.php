<?php

declare(strict_types=1);

namespace Drupal\custom_field_graphql\Plugin\GraphQLCompose\FieldType;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\custom_field\Plugin\DataType\CustomFieldStringLongInterface;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerItemInterface;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerTrait;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeFieldType(
 *   id = "custom_field_text",
 *   type_sdl = "Text",
 * )
 */
class CustomFieldText extends GraphQLComposeFieldTypeBase implements FieldProducerItemInterface {

  use FieldProducerTrait;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->configFactory = $container->get('config.factory');
    $instance->renderer = $container->get('renderer');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function resolveFieldItem(FieldItemInterface $item, FieldContext $context) {
    $property = (string) $context->getContextValue('property_name');
    $settings = $context->getContextValue('settings');
    $fallback_format = $this->configFactory->get('filter.settings')->get('fallback_format');
    $processed = $item->get($property);
    assert($processed instanceof CustomFieldStringLongInterface);
    $processed_text = $processed->getProcessed();

    if ($processed_text instanceof MarkupInterface) {
      $format = $settings['default_format'] ?? $fallback_format;
      // Formatted text has been filtered by its text format, and its cache
      // metadata is only known once getProcessed() has run. Unformatted text
      // is never processed, so this object reports a max-age of 0, which would
      // make the whole response uncacheable; only formatted text adds it.
      $context->addCacheableDependency($processed);
    }
    else {
      // Unformatted text is untrusted plain text. Filter it through the
      // fallback format, as core's processed_text element does when it is
      // given no format, and collect that format's cache metadata.
      $format = $fallback_format;
      if ($processed_text !== NULL) {
        $build = [
          '#type' => 'processed_text',
          '#text' => (string) $processed_text,
          '#format' => $format,
          '#langcode' => $item->getLangcode(),
        ];
        $processed_text = $this->renderer->renderInIsolation($build);
        $context->addCacheableDependency(CacheableMetadata::createFromRenderArray($build));
      }
    }

    return [
      'format' => $format,
      'value' => $item->{$property},
      'processed' => $processed_text,
    ];
  }

}
