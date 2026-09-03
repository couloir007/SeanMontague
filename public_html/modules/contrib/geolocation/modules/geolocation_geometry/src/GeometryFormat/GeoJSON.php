<?php

namespace Drupal\geolocation_geometry\GeometryFormat;

use Drupal\geolocation_geometry\GeometryType\GeometryCollection;
use Drupal\geolocation_geometry\GeometryType\GeometryTypeInterface;
use Drupal\geolocation_geometry\GeometryType\LineString;
use Drupal\geolocation_geometry\GeometryType\LinearRing;
use Drupal\geolocation_geometry\GeometryType\MultiLineString;
use Drupal\geolocation_geometry\GeometryType\MultiPoint;
use Drupal\geolocation_geometry\GeometryType\MultiPolygon;
use Drupal\geolocation_geometry\GeometryType\Point;
use Drupal\geolocation_geometry\GeometryType\Polygon;

/**
 * GeoJSON support class.
 */
class GeoJSON implements GeometryFormatInterface {

  /**
   * {@inheritdoc}
   */
  public static function geometryByText(string $text = ''): ?GeometryTypeInterface {

    if (!$text) {
      return NULL;
    }

    $json = json_decode($text);

    if (is_object($json->geometry ?? FALSE)) {
      return static::geometryByText((string) $json->geometry);
    }

    if (!is_string($json->type ?? FALSE)) {
      throw new \Exception("Could not parse Geometry");
    }

    switch ($json->type) {

      case 'Point':
        return new Point($json->coordinates);

      case 'MultiPoint':
        $points = [];
        foreach ($json->coordinates as $coordinates) {
          $points[] = new Point($coordinates);
        }
        return new MultiPoint($points);

      case 'LineString':
        $points = [];
        foreach ($json->coordinates as $coordinates) {
          $points[] = new Point($coordinates);
        }
        return new LineString($points);

      case 'MultiLineString':
        $line_strings = [];
        foreach ($json->coordinates as $coordinates) {
          $points = [];
          foreach ($coordinates as $point) {
            $points[] = new Point($point);
          }
          $line_strings[] = new LineString($points);
        }
        return new MultiLineString($line_strings);

      case 'LinearRing':
        $points = [];
        foreach ($json->coordinates as $coordinates) {
          $points[] = new Point($coordinates);
        }
        return new LinearRing($points);

      case 'Polygon':
        $components = [];
        foreach ($json->coordinates as $coordinates) {
          $points = [];
          foreach ($coordinates as $point) {
            $points[] = new Point($point);
          }
          $components[] = new LinearRing($points);
        }
        return new Polygon($components);

      case 'MultiPolygon':
        $polygons = [];
        foreach ($json->coordinates as $coordinates) {
          $rings = [];
          foreach ($coordinates as $polygon) {
            $points = [];
            foreach ($polygon as $point) {
              $points[] = new Point($point);
            }
            $rings[] = new LinearRing($points);
          }
          $polygons[] = new Polygon($rings);
        }
        return new MultiPolygon($polygons);

      case 'GeometryCollection':
        $components = [];
        foreach ($json->geometries as $geometry) {
          $components[] = static::geometryByText($geometry);
        }
        return new GeometryCollection($components);

      default:
        throw new \Exception("Unknown GeoJSON Type encountered.");
    }
  }

}
