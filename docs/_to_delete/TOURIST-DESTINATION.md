# Destination Content Type — TouristDestination

## Status: Planned — not yet implemented

Manual UI work required before Claude Code prompt runs.
See `claude-code-destination-content-type.md` for the implementation prompt.

---

## Schema.org type

`TouristDestination` — subtype of `Place`, natively understood by Google's
travel knowledge graph. Correct for named travel stops (Dublin, Galway,
Dingle) as distinct from generic Place nodes (Kingdom Trails, Burke Mountain)
which remain `Place`.

---

## Content type

Machine name: `destination`
Schema.org mapping: `TouristDestination` (via Schema.org Blueprints)

---

## Fields

| Field | Machine name | Type | Schema.org property | Notes |
|---|---|---|---|---|
| Title | `title` | string | `name` | Destination name — "Galway", "Dingle" |
| Body | `body` | text_with_summary | `description` | Editorial description |
| Image | `schema_image` | entity_reference → media:ImageObject | `image` | Hero image |
| Geo | `schema_geo` | Geofield | `geo` → GeoCoordinates | lat/lon — replaces geo_entity:destination |
| Address | `schema_address` | Address field | `address` | Country + administrative area |
| Parent place | `schema_contained_in_place` | entity_reference → node:place | `containedInPlace` | Optional hub — e.g. Ireland, Co. Kerry |
| Activity type | `schema_activity_type` | entity_reference → taxonomy:activity_type | `touristType` | Reuses existing vocabulary — Mountain Biking, Hiking, Skiing, Permaculture… |

---

## Schema.org property notes

### `containedInPlace`
Correct property for "this destination is within this larger place". Galway
is containedInPlace Ireland. Dingle is containedInPlace Co. Kerry.

```json
{
  "@type": "TouristDestination",
  "name": "Dingle",
  "containedInPlace": {
    "@type": "Place",
    "name": "Co. Kerry"
  }
}
```

Field machine name `schema_contained_in_place` avoids collision with
`schema_place` which is already used on other content types to mean
"referenced Place hub node".

### `touristType`
Describes the type of tourist the destination appeals to. Emitted from
`schema_activity_type` taxonomy terms in the `seanmontague_schemadotorg`
hook — no new taxonomy needed, reuses existing `activity_type` vocabulary.

```json
{
  "@type": "TouristDestination",
  "name": "Galway",
  "touristType": ["Cultural Tourism", "Adventure Tourism"]
}
```

### `includesAttraction`
Links destination to its POIs (geo_entity:poi records). Emitted by the
JSON-LD hook by querying POIs that reference this destination via
`schema_place`. Connects destination nodes to the existing POI geo_entity
structure without a direct field reference.

```json
{
  "@type": "TouristDestination",
  "name": "Galway",
  "includesAttraction": [
    { "@type": "TouristAttraction", "name": "Galway Cathedral" },
    { "@type": "TouristAttraction", "name": "Spanish Arch" }
  ]
}
```

---

## Relationships

```
tourist_trip (TouristTrip)
  └── schema_itinerary → article[]
        └── schema_destination → node:destination (TouristDestination)
              ├── schema_geo              → map coordinates
              ├── schema_contained_in_place → node:place (Place)
              ├── schema_activity_type    → activity_type[] (touristType)
              └── ← schema_poi.schema_place → geo_entity:poi[] (includesAttraction)
```

---

## Replaces

`geo_entity:destination` — retired once destination nodes are created.
The geo_entity:destination bundle was a lightweight coordinate anchor only.
The destination node carries the same `schema_geo` field plus editorial
content, a URL, and full Schema.org output.

**Migration:** existing `geo_entity:destination` records from KMZ imports
must be recreated manually as destination nodes. The KMZ importer
(`trip_import` module) must be updated to create `node:destination` records
instead of `geo_entity:destination` entities.

---

## URL pattern

`/destinations/[node:title]`

Destination nodes are standalone pages — a reader can land on
`/destinations/galway` from search and get the full picture: map, editorial
description, activity types, and all articles tagged to it across any trip.

---

## JSON-LD hook

Add to `seanmontague_schemadotorg` module — `hook_schemadotorg_jsonld_alter`:

```php
// TouristDestination — touristType + containedInPlace + includesAttraction
if ($entity->bundle() === 'destination') {
  // touristType from schema_activity_type terms
  $tourist_types = [];
  foreach ($entity->schema_activity_type as $item) {
    $term = $item->entity;
    if ($term) {
      $tourist_types[] = $term->label();
    }
  }
  if ($tourist_types) {
    $data['schemadotorg_jsonld_entity']['touristType'] = $tourist_types;
  }

  // containedInPlace from schema_contained_in_place
  $parent = $entity->schema_contained_in_place->entity;
  if ($parent) {
    $data['schemadotorg_jsonld_entity']['containedInPlace'] = [
      '@type' => 'Place',
      'name'  => $parent->label(),
    ];
  }

  // includesAttraction — query geo_entity:poi that reference this destination
  // via schema_place field
  $poi_ids = \Drupal::entityQuery('geo_entity')
    ->condition('type', 'poi')
    ->condition('schema_place', $entity->id())
    ->accessCheck(FALSE)
    ->execute();
  if ($poi_ids) {
    $pois = \Drupal::entityTypeManager()
      ->getStorage('geo_entity')
      ->loadMultiple($poi_ids);
    $attractions = [];
    foreach ($pois as $poi) {
      $attractions[] = [
        '@type' => 'TouristAttraction',
        'name'  => $poi->label(),
      ];
    }
    $data['schemadotorg_jsonld_entity']['includesAttraction'] = $attractions;
  }
}
```

---

## Template

`templates/content/node--destination.html.twig`

Sections:
1. Hero image (`schema_image`)
2. Leaflet map — single destination marker, zoom 11, interactive
3. Stats bar — activity types, coordinates (optional)
4. Body editorial
5. Linked articles view — articles where `schema_destination` = this node

Collection: TBD — may warrant `@collections/destination/destination.twig`
once the pattern is established.

---

## Storybook story

`source/patterns/collections/destination/destination.stories.jsx`
Fixture: Irish destination with image, geo, activity types, body summary.
Story: `Collections/Destination`

---

## Pending

- [ ] Create content type in UI
- [ ] Set Schema.org mapping to `TouristDestination` in Blueprints
- [ ] Add fields (schema_geo, schema_image, schema_address, schema_contained_in_place, schema_activity_type)
- [ ] Set URL pattern `/destinations/[node:title]`
- [ ] Change `schema_destination` field on article → target `node:destination`
- [ ] Export config (`lando drush cex`)
- [ ] Update `claude-code-destination-content-type.md` prompt to reflect `TouristDestination` type
- [ ] Run Claude Code prompt (templates, trip.twig, TEMPLATES.md)
- [ ] Add JSON-LD hook to `seanmontague_schemadotorg` module
- [ ] Recreate Ireland geo_entity:destination records as destination nodes
- [ ] Update KMZ importer to create destination nodes
- [ ] Create Storybook story
