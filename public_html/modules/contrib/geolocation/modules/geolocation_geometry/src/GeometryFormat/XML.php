<?php

namespace Drupal\geolocation_geometry\GeometryFormat;

use Drupal\geolocation_geometry\GeometryType\GeometryCollection;
use Drupal\geolocation_geometry\GeometryType\GeometryTypeInterface;

/**
 * XML support class.
 */
abstract class XML implements GeometryFormatInterface { // phpcs:ignore

  /**
   * {@inheritdoc}
   */
  public static function geometryByText(?string $text = NULL): ?GeometryTypeInterface {
    $xml = simplexml_load_string($text);

    return static::geometryByXML($xml);
  }

  /**
   * Get geometry by XML.
   *
   * @param \SimpleXMLElement|null $xml
   *   JSON.
   *
   * @return \Drupal\geolocation_geometry\GeometryType\GeometryTypeInterface|null
   *   GeometryType.
   */
  public static function geometryByXML(?\SimpleXMLElement $xml = NULL): ?GeometryTypeInterface {
    return NULL;
  }

  /**
   * Child Elements.
   *
   * @param \SimpleXMLElement $xml
   *   XML.
   * @param string $nodename
   *   Node type.
   *
   * @return array
   *   Child elements.
   */
  protected static function childElements(\SimpleXMLElement $xml, string $nodename = ""): array {
    $nodename = strtolower($nodename);
    $res = [];
    foreach ($xml->children() as $child) {
      if ($nodename) {
        if (strtolower($child->getName()) == $nodename) {
          $res[] = $child;
        }
      }
      else {
        $res[] = $child;
      }
    }
    return $res;
  }

  /**
   * Parse children.
   *
   * @param \SimpleXMLElement $xml
   *   XML.
   *
   * @return \Drupal\geolocation_geometry\GeometryType\GeometryTypeInterface|null
   *   Geometry.
   */
  protected static function parseChildren(\SimpleXMLElement $xml): ?GeometryTypeInterface {
    $components = [];
    foreach (static::childElements($xml) as $child) {
      $geometry = static::geometryByXML($child);
      $components[] = $geometry;
    }

    switch (count($components)) {
      case 0:
        return NULL;

      case 1:
        return $components[0];

      default:
        return new GeometryCollection($components);
    }
  }

}
