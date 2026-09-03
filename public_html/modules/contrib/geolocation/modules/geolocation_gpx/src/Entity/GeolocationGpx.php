<?php

namespace Drupal\geolocation_gpx\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\geolocation_geometry\GeometryType\GeometryTypeBase;

/**
 * Defines the GPX entity.
 *
 * @ingroup geolocation_gpx
 *
 * @property \Drupal\Core\Field\EntityReferenceFieldItemListInterface<\Drupal\geolocation_gpx\Entity\GeolocationGpxLink> $link
 * @property \Drupal\Core\Field\EntityReferenceFieldItemListInterface<\Drupal\geolocation_gpx\Entity\GeolocationGpxRoute> $routes
 * @property \Drupal\Core\Field\EntityReferenceFieldItemListInterface<\Drupal\geolocation_gpx\Entity\GeolocationGpxWaypoint> $waypoints
 * @property \Drupal\Core\Field\EntityReferenceFieldItemListInterface<\Drupal\geolocation_gpx\Entity\GeolocationGpxTrack> $tracks
 */
#[ContentEntityType(
  id: 'geolocation_gpx',
  label: new \Drupal\Core\StringTranslation\TranslatableMarkup('Geolocation GPX'),
  entity_keys: ['id' => 'id', 'uuid' => 'uuid'],
  handlers: ['views_data' => ''],
  admin_permission: 'administer geolocation_gpx',
  base_table: 'geolocation_gpx'
)]
class GeolocationGpx extends ContentEntityBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Route entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Route entity.'))
      ->setReadOnly(TRUE);

    $fields['version'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Version'))
      ->setDescription(t('You must include the version number in your GPX document.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE);

    $fields['creator'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Creator'))
      ->setDescription(t('You must include the name or URL of the software that created your GPX document.  This allows others to inform the creator of a GPX instance document that fails to validate.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('GPS name of route.'))
      ->setTranslatable(TRUE);

    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Description'))
      ->setDescription(t('Text description of route for user. Not sent to GPS.'))
      ->setTranslatable(TRUE);

    $fields['author'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Author'))
      ->setDescription(t('The person or organization who created the GPX file.'))
      ->setTranslatable(TRUE);

    $fields['copyright'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Copyright'))
      ->setDescription(t('Copyright and license information governing use of the file.'))
      ->setTranslatable(TRUE);

    $fields['link'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Links'))
      ->setDescription(t('Links to external information about the route.'))
      ->setSetting('target_type', 'geolocation_gpx_link')
      ->setSetting('handler', 'default')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    $fields['time'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Time'))
      ->setDescription(t('Creation/modification timestamp for element. Date and time in are in Univeral Coordinated Time (UTC), not local time! Conforms to ISO 8601 specification for date/time representation. Fractional seconds are allowed for millisecond timing in tracklogs.'));

    $fields['keywords'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Keywords'))
      ->setDescription(t('Keywords associated with the file.  Search engines or databases can use this information to classify the data.'))
      ->setTranslatable(TRUE);

    $fields['waypoints'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Waypoints'))
      ->setDescription(t('A list of waypoints.'))
      ->setSetting('target_type', 'geolocation_gpx_waypoint')
      ->setSetting('handler', 'default')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    $fields['routes'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Routes'))
      ->setDescription(t('A list of routes.'))
      ->setSetting('target_type', 'geolocation_gpx_route')
      ->setSetting('handler', 'default')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    $fields['tracks'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tracks'))
      ->setDescription(t('A list of tracks.'))
      ->setSetting('target_type', 'geolocation_gpx_track')
      ->setSetting('handler', 'default')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    return $fields;
  }

  /**
   * Get name to display for element.
   *
   * @return string
   *   Name.
   */
  public function getDisplayName(): string {
    return $this->name->value ?? $this->author->value ?? $this->creator->value ?? '';
  }

  /**
   * Get waypoints.
   *
   * @return \Drupal\geolocation_gpx\Entity\GeolocationGpxWaypoint[]
   *   Waypoints.
   */
  public function getWaypoints(): array {
    return $this->get('waypoints')->referencedEntities();
  }

  /**
   * Get routes.
   *
   * @return \Drupal\geolocation_gpx\Entity\GeolocationGpxRoute[]
   *   Routes.
   */
  public function getRoutes(): array {
    return $this->get('routes')->referencedEntities();
  }

  /**
   * Get tracks.
   *
   * @return \Drupal\geolocation_gpx\Entity\GeolocationGpxTrack[]
   *   Tracks
   */
  public function getTracks(): array {
    return $this->get('tracks')->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities): void {
    /** @var \Drupal\geolocation_gpx\Entity\GeolocationGpx[] $entities */
    foreach ($entities as $gpx) {

      foreach ($gpx->link as $link) {
        $link->entity?->delete();
      }

      foreach ($gpx->waypoints as $waypoint) {
        $waypoint->entity?->delete();
      }

      foreach ($gpx->routes as $route) {
        $route->entity?->delete();
      }

      foreach ($gpx->tracks as $track) {
        $track->entity?->delete();
      }
    }
    parent::preDelete($storage, $entities);
  }

  /**
   * Get elevation chart render array.
   *
   * @return array
   *   Render array.
   */
  public function renderedTracksElevationChart(): array {

    $continuous_distance = 0;

    $last_longitude = $last_latitude = NULL;

    $elevation_points = [];
    foreach ($this->getTracks() as $track) {
      foreach ($track->getSegments() as $segment) {
        foreach ($segment->getWaypoints() as $index => $waypoint) {
          if (is_null($waypoint->getElevation())) {
            continue;
          }

          if (!is_null($last_latitude) && !is_null($last_longitude)) {
            $continuous_distance = $continuous_distance + GeometryTypeBase::distanceByCoordinates(
              $last_latitude,
              $last_longitude,
              $waypoint->getLatitude(),
              $waypoint->getLongitude(),
            );
          }

          $last_latitude = $waypoint->getLatitude();
          $last_longitude = $waypoint->getLongitude();

          $elevation_points[] = [
            'index' => $index,
            'elevation' => $waypoint->getElevation(),
            'distance' => $continuous_distance,
            'time' => $waypoint->getFormattedTime(),
          ];
        }
      }
    }

    return [
      'table' => [
        '#type' => 'table',
        '#header' => [
          $this->t('Index'),
          $this->t('Elevation'),
          $this->t('Continuous Distance'),
          $this->t('Time'),
        ],
        '#rows' => $elevation_points,
        '#attributes' => [
          'class' => [
            'geolocation-gpx-elevation-table',
          ],
        ],
      ],
      'chart' => [
        '#type' => 'html_tag',
        '#tag' => 'canvas',
        '#attributes' => [
          'class' => [
            'geolocation-gpx-elevation-chart',
          ],
        ],
      ],
      '#attached' => [
        'library' => [
          'geolocation_gpx/elevation-chart',
        ],
      ],
    ];
  }

  /**
   * Get summary table render array.
   *
   * @return array
   *   Render array.
   */
  public function renderedSummaryTable(): array {
    $element = [];

    $element['title'] = [
      '#weight' => -10,
      '#prefix' => $this->t('<b>Summary</b> <br/>'),
      '#markup' => $this->t('Name/Author/Creator (ID): %author (%id)', [
        '%author' => $this->getDisplayName(),
        '%id' => $this->id(),
      ]),
    ];

    $element['table'] = [
      '#type' => 'table',
      '#header' => [
        'waypoints' => $this->t('Waypoints'),
        'routes' => $this->t('Routes / Waypoints'),
        'tracks' => $this->t('Tracks / Segments / Waypoints'),
      ],
      '#rows' => [],
    ];

    $route_waypoints = 0;
    foreach ($this->getRoutes() as $route) {
      $route_waypoints = $route_waypoints + count($route->waypoints);
    }

    $track_segments = $track_waypoints = 0;
    foreach ($this->getTracks() as $track) {
      $track_segments = $track_segments + count($track->getSegments());
      foreach ($track->getSegments() as $segment) {
        $track_waypoints = $track_waypoints + count($segment->track_points);
      }
    }

    $element['table']['#rows'][] = [
      count($this->waypoints),
      count($this->getRoutes()) . ' / ' . $route_waypoints,
      count($this->getTracks()) . ' / ' . $track_segments . ' / ' . $track_waypoints,
    ];

    return $element;
  }

}
