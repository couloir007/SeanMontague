<?php

namespace Drupal\geolocation\Plugin\geolocation\Location;

use Drupal\geolocation\Attribute\Location;
use Drupal\geolocation\DataProviderManager;
use Drupal\geolocation\LocationBase;
use Drupal\geolocation\LocationInterface;
use Drupal\geolocation\ViewsContextTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Derive center from first row.
 */
#[Location(
  id: 'first_row',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('View first row'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Use geolocation field value from first row.')
)]
class FirstRow extends LocationBase implements LocationInterface {

  use ViewsContextTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected DataProviderManager $dataProviderManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): LocationInterface {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.geolocation.dataprovider')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableLocationOptions(array $context = []): array {
    $options = [];

    if ($displayHandler = self::getViewsDisplayHandler($context)) {
      if ($displayHandler->getPlugin('style')->getPluginId() == 'maps_common') {
        $options['first_row'] = $this->t('First row');
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getCoordinates(string $location_option_id, array $location_option_settings, mixed $context = NULL): ?array {
    if (!($displayHandler = self::getViewsDisplayHandler($context))) {
      return parent::getCoordinates($location_option_id, $location_option_settings, $context);
    }
    /** @var \Drupal\geolocation\Plugin\views\style\GeolocationStyleBase $views_style */
    $views_style = $displayHandler->getPlugin('style');

    if (empty($views_style->options['geolocation_field'])) {
      return parent::getCoordinates($location_option_id, $location_option_settings, $context);
    }

    /** @var \Drupal\geolocation\Plugin\views\field\GeolocationField|null $source_field */
    $source_field = $views_style->view->field[$views_style->options['geolocation_field']];

    if (empty($source_field)) {
      return parent::getCoordinates($location_option_id, $location_option_settings, $context);
    }

    if (empty($views_style->view->result[0])) {
      return parent::getCoordinates($location_option_id, $location_option_settings, $context);
    }

    $data_provider = $this->dataProviderManager->getDataProviderByViewsField($source_field);

    if (empty($data_provider)) {
      return parent::getCoordinates($location_option_id, $location_option_settings, $context);
    }

    $locations = $data_provider->getLocationsFromViewsRow($views_style->view->result[0], $source_field);

    if (!empty($locations[0])) {
      return $locations[0]['#coordinates'];
    }

    return parent::getCoordinates($location_option_id, $location_option_settings, $context);
  }

}
