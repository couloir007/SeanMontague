<?php

namespace Drupal\geolocation\Plugin\views\field;

use Drupal\views\Attribute\ViewsField;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\geolocation\LocationManager;
use Drupal\geolocation\ProximityTrait;
use Drupal\views\Plugin\views\field\NumericField;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler for geolocation field.
 *
 * @ingroup views_field_handlers
 */
#[ViewsField(id: 'geolocation_field_proximity')]
class ProximityField extends NumericField implements ContainerFactoryPluginInterface {

  use ProximityTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected LocationManager $locationManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): ProximityField {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.geolocation.location')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions(): array {
    $options = parent::defineOptions();

    $options['center'] = ['default' => []];
    $options['display_unit'] = ['default' => 'km'];

    return $options;
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state): void {
    parent::buildOptionsForm($form, $form_state);
    $form['center'] = $this->locationManager->getLocationOptionsForm($this->options['center'], ['views_field' => $this]);

    $form['display_unit'] = [
      '#title' => $this->t('Distance unit'),
      '#description' => $this->t('Values internally are always treated as kilometers. This setting converts values accordingly.'),
      '#type' => 'select',
      '#weight' => 5,
      '#default_value' => $this->options['display_unit'],
      '#options' => [
        'km' => $this->t('Kilometer'),
        'mi' => $this->t('Miles'),
        'nm' => $this->t('Nautical Miles'),
        'm' => $this->t('Meter'),
        'ly' => $this->t('Light-years'),
      ],
    ];
  }

  /**
   * Get center value.
   *
   * @return array
   *   Center value.
   */
  protected function getCenter(): array {
    return $this->locationManager->getLocation($this->options['center'], ['views_field' => $this]);
  }

  /**
   * {@inheritdoc}
   */
  public function query(): void {
    /** @var \Drupal\views\Plugin\views\query\Sql $query */
    $query = $this->query;

    $center = $this->getCenter();
    if (empty($center)) {
      return;
    }

    // Build the query expression.
    $expression = self::getProximityQueryFragment($this->ensureMyTable(), $this->realField, $center['lat'], $center['lng']);

    // Get a placeholder for this query and save the field_alias for it.
    // Remove the initial ':' from the placeholder and avoid collision with
    // the original field name.
    $this->field_alias = $query->addField(NULL, $expression, substr($this->placeholder(), 1));
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(ResultRow $values, $field = NULL): int|null|float {
    $value = parent::getValue($values, $field);

    // NULL means no proximity calculated, 0 means 0m distance.
    if (is_null($value)) {
      return NULL;
    }

    $value = self::convertDistance((float) $value, $this->options['display_unit'], TRUE);
    // Views empty check only respects int 0, not float 0.
    if ($value === 0.0) {
      return 0;
    }
    return $value;
  }

}
