<?php

namespace Drupal\geolocation_gpx;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\geolocation_gpx\Entity\GeolocationGpx;
use Drupal\geolocation_gpx\Entity\GeolocationGpxLink;
use Drupal\geolocation_gpx\Entity\GeolocationGpxRoute;
use Drupal\geolocation_gpx\Entity\GeolocationGpxTrack;
use Drupal\geolocation_gpx\Entity\GeolocationGpxTrackSegment;
use Drupal\geolocation_gpx\Entity\GeolocationGpxWaypoint;
use phpGPX\Models\GpxFile;
use phpGPX\Models\Link;
use phpGPX\Models\Point;
use phpGPX\Models\Route;
use phpGPX\Models\Segment;
use phpGPX\Models\Track;
use phpGPX\phpGPX;

/**
 * Builds GPX entities from parsed GPX file data.
 *
 * The source file is only ever read, never referenced by the entities created
 * here, so callers are free to discard it afterwards.
 */
class GeolocationGpxImporter {

  /**
   * Constructor.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountInterface $currentUser,
  ) {}

  /**
   * Read GPX data from a file.
   *
   * @param string $path
   *   Path or stream URI to read.
   *
   * @return \phpGPX\Models\GpxFile
   *   Parsed file.
   *
   * @throws \RuntimeException
   *   If the file holds no readable GPX data.
   */
  public function parse(string $path): GpxFile {
    // Malformed input does not make the parser throw, it only makes libxml
    // complain and hands back an empty result. Without both halves of this an
    // unreadable file would raise PHP warnings and then quietly turn into an
    // empty GPX.
    $previous_libxml_errors = libxml_use_internal_errors(TRUE);
    try {
      $data = phpGPX::load($path);
    }
    finally {
      libxml_clear_errors();
      libxml_use_internal_errors($previous_libxml_errors);
    }

    if (!$data->waypoints && !$data->routes && !$data->tracks) {
      throw new \RuntimeException('No waypoints, routes or tracks could be read from the file.');
    }

    return $data;
  }

  /**
   * Transform GPX data to Geolocation format.
   *
   * @param \phpGPX\Models\GpxFile $data
   *   File.
   * @param string $file_name
   *   Name of the uploaded file, used when the GPX metadata carries no name.
   *
   * @return \Drupal\geolocation_gpx\Entity\GeolocationGpx
   *   Geolocation GPX.
   */
  public function import(GpxFile $data, string $file_name): GeolocationGpx {
    /** @var \Drupal\geolocation_gpx\Entity\GeolocationGpx $gpx */
    $gpx = $this->entityTypeManager->getStorage('geolocation_gpx')->create([
      'version' => '1.1',
      'creator' => $data->creator ?? $this->currentUser->getAccountName(),
      'name' => $data->metadata?->name ?: mb_substr(pathinfo($file_name, PATHINFO_FILENAME), 0, 255),
      'description' => $data->metadata->description ?? '',
      'author' => $data->metadata->author->name ?? '',
      'copyright' => $data->metadata->copyright->author ?? '',
      'time' => $data->metadata?->time?->format('Y-m-d H:i:s'),
      'keywords' => $data->metadata?->keywords,
    ]);
    foreach ($data->metadata->links ?? [] as $linkData) {
      $gpx->get('link')->appendItem($this->linkByData($linkData));
    }

    foreach ($data->waypoints as $waypointData) {
      $gpx->get('waypoints')->appendItem($this->waypointByData($waypointData));
    }

    foreach ($data->routes as $routeData) {
      $gpx->get('routes')->appendItem($this->routeByData($routeData));
    }

    foreach ($data->tracks as $trackData) {
      $gpx->get('tracks')->appendItem($this->trackByData($trackData));
    }

    $gpx->save();

    return $gpx;
  }

  /**
   * Transform GPX data to Geolocation format.
   *
   * @param \phpGPX\Models\Point $data
   *   Waypoint.
   *
   * @return \Drupal\geolocation_gpx\Entity\GeolocationGpxWaypoint
   *   Geolocation waypoint.
   */
  protected function waypointByData(Point $data): GeolocationGpxWaypoint {
    /** @var \Drupal\geolocation_gpx\Entity\GeolocationGpxWaypoint $waypoint */
    $waypoint = $this->entityTypeManager->getStorage('geolocation_gpx_waypoint')->create([
      'latitude' => $data->latitude,
      'longitude' => $data->longitude,
      'elevation' => $data->elevation ?? NULL,
      'time' => $data->time?->format('Y-m-d H:i:s') ?? NULL,
      'magnetic_variation' => $data->time ?? NULL,
      'geoidheight' => $data->geoidHeight ?? NULL,
      'name' => $data->name ?? '',
      'comment' => $data->comment ?? '',
      'description' => $data->description ?? '',
      'source' => $data->source ?? '',
      'symbol' => $data->symbol ?? '',
      'type' => $data->type ?? NULL,
      'satellites' => $data->satellitesNumber,
      'horizontal_dilution' => $data->hdop,
      'vertical_dilution' => $data->vdop,
      'position_dilution' => $data->pdop,
      'age_of_dgps_data' => $data->ageOfGpsData,
    ]);

    foreach ($data->links as $linkData) {
      $link = $this->linkByData($linkData);
      $waypoint->get('link')->appendItem($link);
    }
    $waypoint->save();

    return $waypoint;
  }

  /**
   * Transform GPX data to Geolocation format.
   *
   * @param \phpGPX\Models\Link $data
   *   Link.
   *
   * @return \Drupal\geolocation_gpx\Entity\GeolocationGpxLink
   *   Geolocation link.
   */
  protected function linkByData(Link $data): GeolocationGpxLink {
    /** @var \Drupal\geolocation_gpx\Entity\GeolocationGpxLink $link */
    $link = $this->entityTypeManager->getStorage('geolocation_gpx_link')->create([
      'href' => $data->href,
      'type' => $data->type,
      'text' => $data->text,
    ]);
    $link->save();

    return $link;
  }

  /**
   * Transform GPX data to Geolocation format.
   *
   * @param \phpGPX\Models\Route $data
   *   Route.
   *
   * @return \Drupal\geolocation_gpx\Entity\GeolocationGpxRoute
   *   Geolocation link.
   */
  protected function routeByData(Route $data): GeolocationGpxRoute {
    /** @var \Drupal\geolocation_gpx\Entity\GeolocationGpxRoute $route */
    $route = $this->entityTypeManager->getStorage('geolocation_gpx_route')->create([
      'name' => $data->name ?? '',
      'comment' => $data->comment ?? '',
      'description' => $data->description ?? '',
      'source' => $data->source ?? '',
      'number' => $data->type ?? NULL,
      'type' => $data->type ?? NULL,
    ]);

    foreach ($data->points as $waypointData) {
      $route->get('route_points')->appendItem($this->waypointByData($waypointData));
    }

    foreach ($data->links as $linkData) {
      $route->get('link')->appendItem($this->linkByData($linkData));
    }

    $route->save();
    return $route;
  }

  /**
   * Transform GPX data to Geolocation format.
   *
   * @param \phpGPX\Models\Track $data
   *   Track.
   *
   * @return \Drupal\geolocation_gpx\Entity\GeolocationGpxTrack
   *   Geolocation track.
   */
  protected function trackByData(Track $data): GeolocationGpxTrack {
    /** @var \Drupal\geolocation_gpx\Entity\GeolocationGpxTrack $track */
    $track = $this->entityTypeManager->getStorage('geolocation_gpx_track')->create([
      'name' => $data->name ?? '',
      'comment' => $data->comment ?? '',
      'description' => $data->description ?? '',
      'source' => $data->source ?? '',
      'number' => $data->type ?? NULL,
      'type' => $data->type ?? NULL,
    ]);

    foreach ($data->segments as $segmentData) {
      $track->get('track_segments')->appendItem($this->trackSegmentByData($segmentData));
    }

    foreach ($data->links as $linkData) {
      $track->get('link')->appendItem($this->linkByData($linkData));
    }

    $track->save();
    return $track;
  }

  /**
   * Transform GPX data to Geolocation format.
   *
   * @param \phpGPX\Models\Segment $data
   *   Segment.
   *
   * @return \Drupal\geolocation_gpx\Entity\GeolocationGpxTrackSegment
   *   Geolocation segment.
   */
  protected function trackSegmentByData(Segment $data): GeolocationGpxTrackSegment {
    /** @var \Drupal\geolocation_gpx\Entity\GeolocationGpxTrackSegment $track_segment */
    $track_segment = $this->entityTypeManager->getStorage('geolocation_gpx_track_segment')->create();

    foreach ($data->points as $waypointData) {
      $track_segment->get('track_points')->appendItem($this->waypointByData($waypointData));
    }

    $track_segment->save();
    return $track_segment;
  }

}
