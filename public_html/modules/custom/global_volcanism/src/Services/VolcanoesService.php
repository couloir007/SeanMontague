<?php

namespace Drupal\global_volcanism\Services;

use Drupal\taxonomy\Entity\Term;

class VolcanoesService extends NoaaApiClient {

  protected function getOrCreateTermId(string $name, string $vocabulary): int {
    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties([
        'name' => $name,
        'vid' => $vocabulary,
      ]);
    $term = reset($terms);
    if (!$term) {
      $term = Term::create([
        'name' => $name,
        'vid' => $vocabulary,
      ]);
      $term->save();
    }
    return $term->id();
  }

  public function importVolcanoDataFromFile(string $filepath): int {
    $count_imported = 0;

    if (!file_exists($filepath)) {
      throw new \Exception("File not found: $filepath");
    }

    $handle = fopen($filepath, 'r');
    if ($handle === FALSE) {
      throw new \Exception("Unable to open the file $filepath");
    }

    $headers = fgetcsv($handle, 0, "\t");
    if (!$headers) {
      fclose($handle);
      throw new \Exception("Failed reading the header line.");
    }

    $storage = \Drupal::entityTypeManager()
      ->getStorage('si_mapping');

    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('bundle', 'world_volcanoes');

    $ids = $query->execute();

    $existing_volcanoes = [];
    if (!empty($ids)) {
      $entities = $storage->loadMultiple($ids);
      foreach ($entities as $entity) {
        $volcano_id = $entity->get('field_valcano_id')->value ?? NULL;
        if ($volcano_id !== NULL) {
          $existing_volcanoes[$volcano_id] = $entity;
        }
      }
    }

    while (($data = fgetcsv($handle, 0, "\t")) !== FALSE) {
      $row = array_combine($headers, $data);
      if (!$row) {
        continue;
      }
      $volcano_number = $row['Volcano Number'] ?? NULL;
      if (!$volcano_number) {
        continue;
      }

      if (isset($existing_volcanoes[$volcano_number])) {
        $entity = $existing_volcanoes[$volcano_number];
      } else {
        $entity = $storage->create([
          'bundle' => 'world_volcanoes',
        ]);
      }

      if ($row['Volcano Name'] ?? NULL) {
        $entity->set('label', $row['Volcano Name']);
      }

      $entity->set('field_valcano_id', $volcano_number);

      if ($row['Elevation (m)'] ?? NULL) {
        $entity->set('field_elevation', $row['Elevation (m)']);
      }

      if (isset($row['Longitude'], $row['Latitude'])) {
        $location_geojson = json_encode([
          "type" => "FeatureCollection",
          "features" => [
            [
              "type" => "Feature",
              "properties" => new \stdClass(),
              "geometry" => [
                "type" => "Point",
                "coordinates" => [
                  (float)$row['Longitude'],
                  (float)$row['Latitude'],
                ],
              ],
            ],
          ],
        ]);
        $entity->set('field_location', $location_geojson);
      }

      if (!empty($row['Country'])) {
        $country_tid = $this->getOrCreateTermId($row['Country'], 'country');
        $entity->set('field_country', ['target_id' => $country_tid]);
      }

      if (!empty($row['Location'])) {
        $locality_tid = $this->getOrCreateTermId($row['Location'], 'locality');
        $entity->set('field_locality', ['target_id' => $locality_tid]);
      }

      if (!empty($row['Type'])) {
        $type_tid = $this->getOrCreateTermId($row['Type'], 'volcano_type');
        $entity->set('field_type', ['target_id' => $type_tid]);
      }

      if (!empty($row['Status'])) {
        $status_tid = $this->getOrCreateTermId($row['Status'], 'volcano_status');
        $entity->set('field_status', ['target_id' => $status_tid]);
      }

      if (!empty($row['Last Known Eruption'])) {
        $eruption_tid = $this->getOrCreateTermId($row['Last Known Eruption'], 'last_known_eruption');
        $entity->set('field_last_known_eruption', ['target_id' => $eruption_tid]);
      }

      $entity->save();
      $count_imported++;
    }

    fclose($handle);

    return $count_imported;
  }

}
