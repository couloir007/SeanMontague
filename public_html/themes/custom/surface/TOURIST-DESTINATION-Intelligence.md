# Destination Content Type — TouristDestination

## Status: UI complete — Claude Code prompt ready to run

See `claude-code-destination-content-type.md` for the implementation prompt.

---

## Schema.org type

`TouristDestination` — subtype of `Place`, natively understood by Google's
travel knowledge graph. Correct for named travel stops (Dublin, Galway,
Dingle) as distinct from generic Place nodes (Kingdom Trails, Burke Mountain)
which remain `Place`.

---

## Content type

Machine name: `tourist_destination`
Schema.org mapping: `TouristDestination` (via Schema.org Blueprints)

---

## Fields

| Field | Machine name | Type | Schema.org property | Notes |
|---|---|---|---|---|
| Title | `title` | string | `name` | Destination name — "Galway", "Dingle" |
| Body | `body` | text_with_summary | `description` | Editorial lede + full description |
| Image | `schema_image` | entity_ref → media:ImageObject | `image` | Hero image |
| Geo | `schema_geo` | Geofield | `geo` → GeoCoordinates | lat/lon — replaces geo_entity:destination |
| Address | `schema_address` | Address | `address` | Country + administrative area |
| Parent place | `schema_contained_in_place` | entity_ref → node:place | `containedInPlace` | e.g. Ireland Place node |
| Tourist type | `schema_tourist_type` | text plain, unlimited | `touristType` | e.g. "Adventure Tourism", "Traditional Music" |

`schema_activity_type` taxonomy reference — **skipped**. `schema_tourist_type`
plain text covers the same ground more directly.

---

## Editorial content model

### Body summary as lede

The `body` field is `text_with_summary`. The **summary** is the editorial
lede — one strong sentence that captures the character of the place.

> "Galway is the New Orleans of Irish music, and we had two days."

This renders:
- As the opening line on the destination page
- As the card description in the trip collection
- As `og:description` for social sharing

The full `body` carries the longer editorial — what the place is like,
what we did, what we wished we had done, what we'd do with more time.

### Tone

Destination pages are personal and useful — not a travel brochure, not a
Wikipedia summary. The voice is the same as the trip articles: specific,
first-person, honest about trade-offs.

---

## The planning map — POI visit status

Destination and Place pages feature a map that functions as a
**planning artifact** — showing not just what happened, but the full
scope of what was considered.

### POI field: `field_status`

Add to `geo_entity:poi`:

| Value | Label | Marker style | Meaning |
|---|---|---|---|
| `did` | Did / Saw | Solid `--forest` ring | Visited on this trip |
| `wished` | Wished We Had | Dashed `--muted` ring | Wanted to, ran out of time |
| `next_time` | Next Time | `--sky` ring | Discovered after / for a future trip |

Default: `did` (existing POIs assumed visited unless marked otherwise).

### Map value

The map shows the **full scope of planning** — not just a route record.
A reader with different constraints (more time, different interests, no car)
can scan the map and build their own itinerary from the same research.

Example — Ireland Galway destination map:
- Dún Aonghasa → `did` (took the ferry to Inishmore, rode bikes to the fort)
- Connemara National Park → `wished` (full day needed, didn't have it)
- Kylemore Abbey → `next_time` (discovered it after we left)
- Quay Street pub session → `did`
- Galway Cathedral → `wished`

### Filter UI (future)

Map controls to filter by status and by POI category:

```
[ All ] [ Did ] [ Wished ] [ Next Time ]
```

This makes the map useful to readers with different agendas — a cultural
tourist, a hiker, someone with a weekend vs. a week.

---

## Relationships

```
Place (node)
  e.g. Ireland → /places/ireland
  └── tourist_destination (node)            → /destinations/galway
        ├── schema_geo                       → map center coordinates
        ├── schema_contained_in_place        → node:place (Ireland)
        ├── schema_tourist_type              → "Traditional Music", "Cultural Tourism"
        └── ← geo_entity:poi.schema_place   → POIs on the destination map
              └── field_status               → did / wished / next_time

tourist_trip (TouristTrip)
  └── schema_itinerary → article[]
        └── schema_destination (contentLocation) → node:tourist_destination
```

---

## Schema.org JSON-LD

```json
{
  "@type": "TouristDestination",
  "name": "Galway",
  "description": "Galway is the New Orleans of Irish music, and we had two days.",
  "touristType": ["Traditional Music", "Cultural Tourism", "Adventure Tourism"],
  "containedInPlace": {
    "@type": "Place",
    "name": "Ireland"
  },
  "includesAttraction": [
    { "@type": "TouristAttraction", "name": "Dún Aonghasa" },
    { "@type": "TouristAttraction", "name": "Spanish Arch" }
  ]
}
```

### JSON-LD hook — `seanmontague_schemadotorg`

```php
if ($entity->bundle() === 'tourist_destination') {
  // touristType — plain text, unlimited cardinality
  $tourist_types = [];
  foreach ($entity->schema_tourist_type as $item) {
    if ($item->value) {
      $tourist_types[] = $item->value;
    }
  }
  if ($tourist_types) {
    $data['schemadotorg_jsonld_entity']['touristType'] = $tourist_types;
  }

  // containedInPlace — referenced Place node
  $parent = $entity->schema_contained_in_place->entity;
  if ($parent) {
    $data['schemadotorg_jsonld_entity']['containedInPlace'] = [
      '@type' => 'Place',
      'name'  => $parent->label(),
    ];
  }

  // includesAttraction — geo_entity:poi referencing this destination
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

`templates/content/node--tourist-destination.html.twig`

Sections:
1. Header — title, address region, parent place, tourist type tags
2. Hero image (`schema_image`)
3. Leaflet map — destination marker + POIs with `field_status` styling
4. Body lede (summary) + full editorial
5. Linked articles — View: articles where `schema_destination` = this node

---

## Replaces

`geo_entity:destination` — **retired**. Was a coordinate-only anchor.
`tourist_destination` node carries the same `schema_geo` plus full
editorial content, URL, Schema.org output, and standalone page.

**Migration:** recreate Ireland `geo_entity:destination` records as
`tourist_destination` nodes manually. Update KMZ importer (`trip_import`)
to create `tourist_destination` nodes instead.

---

## URL pattern

`/destinations/[node:title]`

Standalone pages — searchable, linkable, reusable across multiple trips.
A future trip to Ireland can reference the same Galway destination node.

---

## Pending

- [x] Create `tourist_destination` content type in UI
- [x] Set Schema.org mapping to `TouristDestination` in Blueprints
- [x] Add fields
- [x] Set URL pattern `/destinations/[node:title]`
- [x] Change `schema_destination` on article → target `node:tourist_destination`
- [x] Export config (`lando drush cex`)
- [ ] Run Claude Code prompt — templates, trip.twig cards, TEMPLATES.md, JSON-LD hook
- [ ] Add `field_status` to `geo_entity:poi` (did / wished / next_time)
- [ ] Update map.js `surfaceMarker()` — dashed ring style for `wished`, sky ring for `next_time`
- [ ] Recreate Ireland destination records as `tourist_destination` nodes
- [ ] Update KMZ importer to create `tourist_destination` nodes
- [ ] Create Galway destination node — lede: "Galway is the New Orleans of Irish music"
- [ ] Build destination map filter UI (future)
- [ ] Storybook story — `Collections/Destination`
