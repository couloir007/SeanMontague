<?php

namespace Drupal\custom_field_entity_browser\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides hooks related to config schemas.
 */
class ConfigSchemaHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_config_schema_info_alter().
   */
  #[Hook('config_schema_info_alter')]
  public function configSchemaInfoAlter(array &$definitions): void {
    $definitions['custom_field.field.*']['mapping'] += [
      'entity_browser' => [
        'type'  => 'string',
        'label' => $this->t('Entity browser'),
      ],
      'field_widget_display' => [
        'type'  => 'string',
        'label' => $this->t('Field widget display'),
      ],
      'field_widget_edit' => [
        'type'  => 'boolean',
        'label' => $this->t('Field widget edit'),
      ],
      'field_widget_remove' => [
        'type'  => 'boolean',
        'label' => $this->t('Field widget remove'),
      ],
      'field_widget_replace' => [
        'type'  => 'boolean',
        'label' => $this->t('Field widget replace'),
      ],
      'open' => [
        'type'  => 'boolean',
        'label' => $this->t('Open'),
      ],
      'field_widget_display_settings' => [
        'type' => 'entity_browser.field_widget_display.[%parent.field_widget_display]',
      ],
    ];
  }

}
