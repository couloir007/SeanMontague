<?php

namespace Drupal\geolocation_search_api\Attribute;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\search_api\Attribute\SearchApiDataType;

/**
 * Defines a Search API data type carrying a backend field prefix.
 *
 * The Search API data type attribute cannot express the "prefix" key, which
 * search_api_solr reads off the plugin definition to map a data type onto its
 * dynamic Solr fields "{PREFIX}s_*" and "{PREFIX}m_*". Without it, Solr
 * neither reports the data type as supported nor prefixes its field names.
 * Plugin definitions are built from the attribute object properties, so this
 * subclass adds the key back while staying a Search API data type attribute.
 *
 * @see \Drupal\search_api\Attribute\SearchApiDataType
 * @see search_api_solr_hook_search_api_data_type_info()
 * @see \Drupal\Component\Plugin\Attribute\AttributeBase::get()
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class GeolocationSearchApiDataType extends SearchApiDataType {

  /**
   * Constructs a new class instance.
   *
   * @param string $id
   *   The data type plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $label
   *   (optional) The human-readable name of the data type plugin.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $description
   *   (optional) The data type description.
   * @param bool $default
   *   (optional) Whether this is one of the default data types provided by the
   *   Search API. Internal use only.
   * @param string $fallback_type
   *   (optional) The ID of the fallback data type for this data type.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   * @param bool $no_ui
   *   (optional) TRUE to hide the plugin in the UI.
   * @param string|null $prefix
   *   (optional) Backend field name prefix, read by search_api_solr.
   */
  public function __construct(
    string $id,
    ?TranslatableMarkup $label = NULL,
    ?TranslatableMarkup $description = NULL,
    bool $default = FALSE,
    string $fallback_type = 'string',
    ?string $deriver = NULL,
    bool $no_ui = FALSE,
    public readonly ?string $prefix = NULL,
  ) {
    parent::__construct($id, $label, $description, $default, $fallback_type, $deriver, $no_ui);
  }

}
