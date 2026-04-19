<?php

namespace Drupal\trail_mapper\Service;

/**
 * Computes elevation gain/loss and distance from GPS coordinates.
 *
 * Pure PHP — no Drupal dependencies. Safe to use in standalone test scripts.
 *
 * Algorithm:
 *   1. Resample track to one point per SAMPLE_INTERVAL_M meters (linear Z interp)
 *   2. Accumulate vertical + horizontal while direction is consistent
 *   3. When direction reverses, evaluate the accumulated segment:
 *      - grade (vertical/horizontal) must meet MIN_GRADE threshold
 *      - total vertical must meet MIN_VERTICAL threshold
 *      Only segments passing both tests are counted as real climbing/descending.
 *
 * Z values are expected in meters. Output stats are in meters and miles.
 */
class GeoElevationCalculator {

  /** Resample interval in meters. */
  const SAMPLE_INTERVAL_M = 30.0;

  /** Minimum grade (rise/run) to count as real climbing. 0.005 = 0.5% */
  const MIN_GRADE = 0.005;

  /** Minimum vertical (meters) for a sustained segment to count. */
  const MIN_VERTICAL_M = 15.0; // ~49ft

  /** Earth radius in miles for Haversine. */
  const EARTH_RADIUS_MI = 3958.8;

  /**
   * Compute stats from raw coordinate array.
   *
   * @param array $coords
   *   Array of [lon, lat, ele_m] arrays.
   *
   * @return array|null
   *   Array with keys: distance_mi, elev_gain_m, elev_loss_m,
   *   elev_min_m, elev_max_m, elev_gain_ft, elev_loss_ft,
   *   elev_min_ft, elev_max_ft.
   *   Returns null if fewer than 2 points.
   */
  public static function compute(array $coords): ?array {
    if (count($coords) < 2) {
      return NULL;
    }

    $resampled = static::resample($coords);
    if (count($resampled) < 2) {
      return NULL;
    }

    // Use raw coords for min/max — resampling clips true peaks and valleys.
    $rawMinMax = static::rawMinMax($coords);

    $stats = static::calculateStats($resampled);
    $stats['elev_min_m']  = $rawMinMax['min'];
    $stats['elev_max_m']  = $rawMinMax['max'];
    $stats['elev_min_ft'] = (int) round($rawMinMax['min'] * 3.28084);
    $stats['elev_max_ft'] = (int) round($rawMinMax['max'] * 3.28084);

    return $stats;
  }

  /**
   * Parse a GPX file and return coordinate array.
   *
   * @param string $path
   *   Absolute path to GPX file.
   *
   * @return array
   *   Array of [lon, lat, ele_m] arrays.
   */
  public static function parseGpx(string $path): array {
    libxml_use_internal_errors(TRUE);
    $gpx = simplexml_load_file($path);
    if (!$gpx) {
      return [];
    }

    $coords = [];
    foreach ($gpx->trk as $trk) {
      foreach ($trk->trkseg as $seg) {
        foreach ($seg->trkpt as $pt) {
          $lon = (float) $pt['lon'];
          $lat = (float) $pt['lat'];
          $ele = isset($pt->ele) ? (float) $pt->ele : NULL;
          $coords[] = [$lon, $lat, $ele];
        }
      }
    }
    foreach ($gpx->rte as $rte) {
      foreach ($rte->rtept as $pt) {
        $lon = (float) $pt['lon'];
        $lat = (float) $pt['lat'];
        $ele = isset($pt->ele) ? (float) $pt->ele : NULL;
        $coords[] = [$lon, $lat, $ele];
      }
    }

    return $coords;
  }

  /**
   * Resample coordinates to a fixed horizontal interval.
   *
   * Walks the track point-by-point, accumulating horizontal distance.
   * When accumulated distance reaches SAMPLE_INTERVAL_M, inserts an
   * interpolated point and resets. Z is linearly interpolated.
   *
   * @param array $coords
   *   Raw [lon, lat, ele_m] coordinate array.
   *
   * @return array
   *   Resampled coordinate array at ~SAMPLE_INTERVAL_M spacing.
   */
  protected static function resample(array $coords): array {
    $result = [];
    $result[] = $coords[0];

    $accumulated = 0.0;
    $prev = $coords[0];

    for ($i = 1; $i < count($coords); $i++) {
      $curr = $coords[$i];
      $segDist = static::haversineM($prev[1], $prev[0], $curr[1], $curr[0]);

      if ($segDist == 0) {
        $prev = $curr;
        continue;
      }

      $remaining = $segDist;
      $fraction = 0.0;

      while ($accumulated + $remaining >= static::SAMPLE_INTERVAL_M) {
        $needed = static::SAMPLE_INTERVAL_M - $accumulated;
        $fraction += $needed / $segDist;

        $iLon = $prev[0] + $fraction * ($curr[0] - $prev[0]);
        $iLat = $prev[1] + $fraction * ($curr[1] - $prev[1]);
        $iEle = ($prev[2] !== NULL && $curr[2] !== NULL)
          ? $prev[2] + $fraction * ($curr[2] - $prev[2])
          : NULL;

        $result[] = [$iLon, $iLat, $iEle];

        $remaining -= $needed;
        $accumulated = 0.0;
      }

      $accumulated += $remaining;
      $prev = $curr;
    }

    // Always include the last raw point.
    $last = end($coords);
    $resultLast = end($result);
    if ($last !== $resultLast) {
      $result[] = $last;
    }

    return $result;
  }

  /**
   * Calculate stats from a (resampled) coordinate array.
   *
   * @param array $coords
   *   [lon, lat, ele_m] coordinate array.
   *
   * @return array
   *   Stats array.
   */
  protected static function calculateStats(array $coords): array {
    $distanceMi = 0.0;
    $gainM      = 0.0;
    $lossM      = 0.0;
    $minEle     = PHP_FLOAT_MAX;
    $maxEle     = -PHP_FLOAT_MAX;

    // Segment accumulators for the grade+vertical filter.
    $segH = 0.0; // horizontal distance accumulated in current direction run
    $segV = 0.0; // vertical accumulated in current direction run (signed)

    for ($i = 1; $i < count($coords); $i++) {
      $prev = $coords[$i - 1];
      $curr = $coords[$i];

      // Accumulate min/max from raw resampled elevations.
      if ($curr[2] !== NULL) {
        if ($curr[2] < $minEle) $minEle = $curr[2];
        if ($curr[2] > $maxEle) $maxEle = $curr[2];
      }
      if ($i === 1 && $prev[2] !== NULL) {
        if ($prev[2] < $minEle) $minEle = $prev[2];
        if ($prev[2] > $maxEle) $maxEle = $prev[2];
      }

      // Haversine distance in miles (for total distance).
      $distanceMi += static::haversineMi($prev[1], $prev[0], $curr[1], $curr[0]);

      if ($prev[2] === NULL || $curr[2] === NULL) {
        continue;
      }

      $dV = $curr[2] - $prev[2]; // meters
      $dH = static::haversineM($prev[1], $prev[0], $curr[1], $curr[0]);

      if ($dH == 0) {
        continue;
      }

      // Direction changed — evaluate the accumulated segment.
      if ($segV != 0 && (($dV >= 0) !== ($segV >= 0))) {
        static::evaluateSegment($segV, $segH, $gainM, $lossM);
        $segH = 0.0;
        $segV = 0.0;
      }

      $segH += $dH;
      $segV += $dV;
    }

    // Flush final segment.
    if ($segH > 0) {
      static::evaluateSegment($segV, $segH, $gainM, $lossM);
    }

    return [
      'distance_mi' => round($distanceMi, 1),
      'elev_gain_m' => round($gainM, 1),
      'elev_loss_m' => round($lossM, 1),
      'elev_min_m'  => $minEle === PHP_FLOAT_MAX  ? NULL : round($minEle, 1),
      'elev_max_m'  => $maxEle === -PHP_FLOAT_MAX ? NULL : round($maxEle, 1),
      'elev_gain_ft' => (int) round($gainM * 3.28084),
      'elev_loss_ft' => (int) round($lossM * 3.28084),
      'elev_min_ft'  => $minEle === PHP_FLOAT_MAX  ? NULL : (int) round($minEle * 3.28084),
      'elev_max_ft'  => $maxEle === -PHP_FLOAT_MAX ? NULL : (int) round($maxEle * 3.28084),
    ];
  }

  /**
   * Evaluate a completed directional segment against grade + vertical filters.
   *
   * Modifies $gain and $loss by reference.
   *
   * @param float $segV  Total vertical in meters (signed).
   * @param float $segH  Total horizontal in meters.
   * @param float $gain  Running gain accumulator (modified by reference).
   * @param float $loss  Running loss accumulator (modified by reference).
   */
  protected static function evaluateSegment(
    float $segV,
    float $segH,
    float &$gain,
    float &$loss,
  ): void {
    if ($segH == 0) {
      return;
    }
    $grade = abs($segV) / $segH;
    if ($grade >= static::MIN_GRADE && abs($segV) >= static::MIN_VERTICAL_M) {
      if ($segV > 0) {
        $gain += $segV;
      }
      else {
        $loss += abs($segV);
      }
    }
  }

  /**
   * Compute min/max elevation from raw coordinates.
   *
   * @param array $coords  Raw [lon, lat, ele_m] arrays.
   * @return array  ['min' => float, 'max' => float] in meters.
   */
  protected static function rawMinMax(array $coords): array {
    $min = PHP_FLOAT_MAX;
    $max = -PHP_FLOAT_MAX;
    foreach ($coords as $c) {
      if ($c[2] === NULL) continue;
      if ($c[2] < $min) $min = $c[2];
      if ($c[2] > $max) $max = $c[2];
    }
    return [
      'min' => $min === PHP_FLOAT_MAX  ? NULL : round($min, 1),
      'max' => $max === -PHP_FLOAT_MAX ? NULL : round($max, 1),
    ];
  }

  /**
   * Haversine distance in meters.
   */
  protected static function haversineM(
    float $lat1, float $lon1,
    float $lat2, float $lon2,
  ): float {
    return static::haversineMi($lat1, $lon1, $lat2, $lon2) * 1609.344;
  }

  /**
   * Haversine distance in miles.
   */
  protected static function haversineMi(
    float $lat1, float $lon1,
    float $lat2, float $lon2,
  ): float {
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a    = sin($dLat / 2) ** 2
      + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
      * sin($dLon / 2) ** 2;
    return static::EARTH_RADIUS_MI * 2 * atan2(sqrt($a), sqrt(1 - $a));
  }

}
