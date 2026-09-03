<?php

namespace Drupal\flags_country\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\country\Plugin\Field\FieldWidget\CountryDefaultWidget;
use Drupal\flags\Mapping\Country;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'country_select_menu' widget.
 *
 * @FieldWidget(
 *   id = "country_select_menu",
 *   label = @Translation("Country select options with flags"),
 *   field_types = {},
 *   weight = 5
 * )
 */
class CountrySelectMenuWidget extends CountryDefaultWidget {

  /**
   * The flags mapping country service.
   *
   * @var \Drupal\flags\Mapping\Country
   */
  protected $flagsMappingCountry;

  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    Country $flags_mapping_country,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->flagsMappingCountry = $flags_mapping_country;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ) {
    return new static($plugin_id, $plugin_definition, $configuration['field_definition'],
      $configuration['settings'], $configuration['third_party_settings'],
      $container->get('flags.mapping.country')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // OptionsSelectWidget::formElement() returns the select directly on
    // $element (no 'value' sub-element), so set the type, attributes and
    // attached library at the top level.
    $element['#type'] = 'select_icons';
    $element['#options_attributes'] = $this->flagsMappingCountry->getOptionAttributes(
      array_keys($element['#options'] ?? [])
    );
    $element['#attached']['library'][] = 'flags/flags';

    return $element;
  }

}
