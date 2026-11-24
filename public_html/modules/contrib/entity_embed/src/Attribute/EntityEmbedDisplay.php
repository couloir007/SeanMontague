<?php

declare(strict_types=1);

namespace Drupal\entity_embed\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines an Entity Embed Display plugin attribute object.
 *
 * Plugin Namespace: Plugin/entity_embed/EntityEmbedDisplay.
 *
 * For a working example, see
 * \Drupal\entity_embed\Plugin\entity_embed\EntityEmbedDisplay\FileFieldFormatter
 *
 * @see \Drupal\entity_embed\EntityEmbedDisplay\EntityEmbedDisplayBase
 * @see \Drupal\entity_embed\EntityEmbedDisplay\EntityEmbedDisplayInterface
 * @see \Drupal\entity_embed\EntityEmbedDisplay\EntityEmbedDisplayManager
 * @see plugin_api
 *
 * @ingroup entity_embed_api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class EntityEmbedDisplay extends Plugin {

  /**
   * Constructs an EntityEmbedDisplay attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|string $label
   *   The human-readable name of the Entity Embed Display plugin.
   * @param array|false $entity_types
   *   (optional) The entity types the Entity Embed Display plugin can apply
   *   to. To make the plugin valid for all entity types, set this value to
   *   FALSE.
   * @param bool $no_ui
   *   (optional) Hides the plugin in the UI if TRUE.
   * @param bool $supports_image_alt_and_title
   *   (optional) Whether the plugin supports per-embed alt and title
   *   overrides for media entities with an image source.
   * @param string|null $field_type
   *   (optional) The field type whose formatters are reused by this plugin.
   *   Required by field formatter based plugins and their derivers.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   * @param string|null $provider
   *   (optional) The module providing the plugin. When set, the plugin is only
   *   discovered if that module is installed. Defaults to the module that
   *   contains the plugin class.
   */
  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup|string $label = '',
    public readonly array|false $entity_types = FALSE,
    public readonly bool $no_ui = FALSE,
    public readonly bool $supports_image_alt_and_title = FALSE,
    public readonly ?string $field_type = NULL,
    public readonly ?string $deriver = NULL,
    ?string $provider = NULL,
  ) {
    if ($provider !== NULL) {
      $this->setProvider($provider);
    }
  }

}
