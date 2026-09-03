<?php

namespace Drupal\flags_language\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\LanguageSelectWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\flags\Mapping\Language;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'language_select_menu' widget.
 *
 * @FieldWidget(
 *   id = "language_select_menu",
 *   label = @Translation("Language select with flags"),
 *   field_types = {}
 * )
 */
class LanguageSelectMenuWidget extends LanguageSelectWidget {

  /**
   * The flags.mapping.language service.
   *
   * @var \Drupal\flags\Mapping\Language
   */
  protected $flagsMappingLanguage;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * LanguageSelectMenuWidget constructor.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\flags\Mapping\Language $flags_mapping_language
   *   The flags.mapping.language service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, Language $flags_mapping_language, LanguageManagerInterface $language_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->flagsMappingLanguage = $flags_mapping_language;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_id, $plugin_definition, $configuration['field_definition'],
      $configuration['settings'], $configuration['third_party_settings'],
      $container->get('flags.mapping.language'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    // Add #options to $element if no other module (e.g. content_translation)
    // has populated them yet. Inlined to avoid the deprecated
    // language_process_language_select() helper (removed in Drupal 12).
    if (!isset($element['value']['#options'])) {
      $element['value']['#options'] = [];
      foreach ($this->languageManager->getLanguages($element['value']['#languages']) as $langcode => $language) {
        $element['value']['#options'][$langcode] = $language->isLocked()
          ? $this->t('- @name -', ['@name' => $language->getName()])
          : $language->getName();
      }
    }
    // Change language select to the out type.
    $element['value']['#type'] = 'select_icons';

    $element['value']['#options_attributes'] = $this->flagsMappingLanguage->getOptionAttributes(
      array_keys($element['value']['#options'])
    );

    $element['value']['#attached'] = ['library' => ['flags/flags']];

    // @todo check this language_element_info_alter.
    return $element;
  }

}
