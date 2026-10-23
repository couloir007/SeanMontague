<?php

namespace Drupal\geolocation\Plugin\views\argument;

use Drupal\views\Attribute\ViewsArgument;
use Drupal\Core\Form\FormStateInterface;
use Drupal\geolocation\BoundaryTrait;
use Drupal\views\Plugin\views\argument\ArgumentPluginBase;
use Drupal\views\Plugin\views\query\Sql;

/**
 * Argument handler for geolocation boundary.
 *
 * Argument format should be in the following format:
 * NE-Lat,NE-Lng,SW-Lat,SW-Lng, so "11.1,33.3,55.5,77.7".
 *
 * @ingroup views_argument_handlers
 */
#[ViewsArgument(id: 'geolocation_argument_boundary')]
class BoundaryArgument extends ArgumentPluginBase {

  use BoundaryTrait;

  /**
   * Parsed boundary, empty array if the argument is unusable.
   *
   * @var array|null
   */
  protected ?array $parsedBoundary = NULL;

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
    $form['description']['#markup'] .= $this->t('<br/>Boundary format should be in a NE-Lat,NE-Lng,SW-Lat,SW-Lng format: <strong>"11.1,33.3,55.5,77.7"</strong> .');
  }

  /**
   * {@inheritdoc}
   *
   * @param int|string|false $group_by
   *   Group by.
   */
  public function query($group_by = FALSE): void {
    if (!($this->query instanceof Sql)) {
      return;
    }

    if ($values = $this->getParsedBoundary()) {
      $this->query->addWhereExpression(
        $group_by,
        self::getBoundaryQueryFragment($this->ensureMyTable(), $this->realField, $values['lat_north_east'], $values['lng_north_east'], $values['lat_south_west'], $values['lng_south_west'])
      );
    }
  }

  /**
   * Processes the passed argument into an array of relevant geolocation data.
   *
   * @return array
   *   The calculated values.
   */
  public function getParsedBoundary(): array {
    // Cache the values so this only gets processed once per handler.
    if (isset($this->parsedBoundary)) {
      return $this->parsedBoundary;
    }

    // Process argument values into an array.
    $matches = [];
    preg_match('/^([0-9\-.]+),+([0-9\-.]+),+([0-9\-.]+),+([0-9\-.]+)(.*$)/', $this->getValue(), $matches);

    // Validate the passed argument.
    $values = [
      'lat_north_east' => (isset($matches[1]) && is_numeric($matches[1]) && $matches[1] >= -90 && $matches[1] <= 90) ? floatval($matches[1]) : FALSE,
      'lng_north_east' => (isset($matches[2]) && is_numeric($matches[2]) && $matches[2] >= -180 && $matches[2] <= 180) ? floatval($matches[2]) : FALSE,
      'lat_south_west' => (isset($matches[3]) && is_numeric($matches[3]) && $matches[3] >= -90 && $matches[3] <= 90) ? floatval($matches[3]) : FALSE,
      'lng_south_west' => (isset($matches[4]) && is_numeric($matches[4]) && $matches[4] >= -180 && $matches[4] <= 180) ? floatval($matches[4]) : FALSE,
    ];

    if (
      $values['lat_north_east'] === FALSE
      || $values['lng_north_east'] === FALSE
      || $values['lat_south_west'] === FALSE
      || $values['lng_south_west'] === FALSE
    ) {
      $values = [];
    }

    $this->parsedBoundary = $values;

    return $this->parsedBoundary;
  }

}
