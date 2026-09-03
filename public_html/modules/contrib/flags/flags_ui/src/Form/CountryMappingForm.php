<?php

namespace Drupal\flags_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\flags\Entity\FlagMapping;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for creating and editing country flag mappings.
 */
class CountryMappingForm extends ConfigEntityFormBase {

  /**
   * Array of all available countries.
   *
   * @var string[]
   */
  protected $countries;

  /**
   * Sets array of all available countries.
   *
   * @param string[] $countries
   *   Array of country codes and names.
   */
  protected function setCountries($countries) {
    $this->countries = $countries;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static(
      $container->get('flags.manager')->getList(),
      $container->get('flags.mapping.language'),
      $container->get('module_handler')
    );

    $instance->setCountries($container->get('country_manager')->getList());

    return $instance;
  }

  /**
   * {@inheritDoc}
   */
  protected function getSourceFormItem(FlagMapping $mapping) {
    return [
      '#type' => 'select',
      '#title' => $this->t('Source country'),
      '#options' => $this->countries,
      '#empty_value' => '',
      // Unfortunately countries are indexed with uppercase letters,
      // make sure our ids are correct.
      '#default_value' => strtoupper((string) $mapping->getSource()),
      '#description' => $this->t('Select a target territory flag.'),
      '#required' => TRUE,
    ];
  }

  /**
   * {@inheritDoc}
   */
  protected function getRedirectRoute() {
    return 'entity.country_flag_mapping.list';
  }

  /**
   * {@inheritDoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Countries use uppercase but we want to be consistent and always
    // use lowercase for all mappings.
    /** @var \Drupal\flags\Entity\FlagMapping $mapping */
    $mapping = $this->getEntity();

    // @todo Consider doing this on earlier stage of form submission.
    $mapping->setSource(strtolower($mapping->getSource()));

    return parent::save($form, $form_state);
  }

}
