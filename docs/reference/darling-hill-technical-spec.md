# Ride guide: The Darling Hill Signature

> **Historical spec.** The distance and elevation fields and hard-coded JSON-LD values were proposed, never built. Route stats come from TrailMapper by route ID (see `docs/integrations/trailmapper-interface.md`) and follow the metric-storage decision in `docs/architecture/decisions.md`.
>
> Editorial copy (tagline, intro, section prose, practical info) was removed from this file; the editorial version lives in `docs/trail-guides/trail-guides-few-shot.md` (Example 1).

> Working example for the ride_guide content type. Covers page content, Drupal content model, entity relationships, and Schema.org JSON-LD output.

---

## Route sequence

| # | Segment | Type | Distance | Notes |
|---|---------|------|----------|-------|
| S | East Burke Village | Start | 0 mi | Park at East Burke Sports |
| 1 | East Darling Hill Road | Paved climb | 2.5 mi · 600 ft gain | 6–8% grade; consistent spin |
| 2 | Darling Hill ridge | Singletrack | — | Meadow traverse, open views of Burke Mountain |
| K | Kitchel | Black descent | 1.5 mi · ~800 ft | Berms, whoops, rhythm sections |
| 3 | VAST connector | Doubletrack | 0.5 mi | Flat recovery |
| 4 | Herbs | Blue descent | 1.2 mi | Hardwood forest; chunkier lower third |
| F | East Burke Village | Finish | — | Swim hole behind East Burke Sports |

---

## Drupal content model

### Content type: `ride_guide` → `schema:TouristTrip`

| Field name | Drupal type | Schema.org property | Notes |
|------------|-------------|---------------------|-------|
| `title` | String | `schema:name` | Auto-mapped by Schema.org Blueprints |
| `field_difficulty` | List (text) | `schema:amenityFeature` | beginner / intermediate / advanced |
| `field_distance_miles` | Decimal | `schema:distance` | Store in miles; output with unit text |
| `field_elevation_gain_ft` | Integer | `schema:additionalProperty` | Not native to Schema — use PropertyValue |
| `field_duration_min` | Integer (hrs) | `schema:duration` | ISO 8601 e.g. PT3H30M |
| `field_trails` | Entity ref (ordered) → Trail | `schema:hasPart` | Ordered Trail nodes; each becomes SportsActivityLocation in JSON-LD |
| `field_intro` | Text (formatted) | `schema:description` | First 2–3 sentences feed meta description |
| `field_route_body` | Text (long, formatted) | — | Section-structured narrative; not schema-mapped directly |
| `field_gpx` | File (GPX) | `schema:geo` | GPX → Leaflet render; GeoJSON conversion for geo property |
| `field_geofield` | Geofield (line) | `schema:geo` | GeoField module; stores route geometry |
| `field_season` | List (multiple) | `schema:availableAtOrFrom` | spring / summer / fall / winter |
| `field_start_location` | Entity ref → Place | `schema:touristType` | References Place node (East Burke Sports) |
| `field_parking` | Entity ref → Place | — | References Place node for parking area |
| `field_shuttle_required` | Boolean | `schema:amenityFeature` | Surfaces in practical info block |
| `field_related_pois` | Entity ref → TouristAttraction | `schema:subjectOf` | Swim hole, post-ride spots, etc. |
| `field_featured_image` | Image | `schema:image` | Used in OG tags and JSON-LD image property |
| `field_conditions_note` | Text (formatted) | — | Editable seasonal / current conditions blurb |

### Content type: `trail` → `schema:SportsActivityLocation`

| Field name | Drupal type | Schema.org property | Notes |
|------------|-------------|---------------------|-------|
| `title` | String | `schema:name` | Trail name e.g. "Kitchel" |
| `field_trail_difficulty` | List | `schema:amenityFeature` | green / blue / black / double-black |
| `field_trail_length` | Decimal | `schema:distance` | Miles |
| `field_surface_type` | List | `schema:amenityFeature` | singletrack / doubletrack / pavement / gravel |
| `field_direction` | List | — | one-way / two-way |
| `field_features` | List (multiple) | `schema:amenityFeature` | berms / jumps / rock gardens / drops / flow / technical |
| `field_zone` | Entity ref → Zone | `schema:isPartOf` | References Zone/Place node; inverse of containsPlace |
| `field_trail_gpx` | File (GPX) | `schema:geo` | Individual trail geometry for Leaflet render |
| `field_description` | Text (long) | `schema:description` | Character, features, what to expect |

### Entity relationships

```
Zone (Place)
└─ containsPlace → Trail (SportsActivityLocation) ×many
     └─ isPartOf → Zone [reverse]

RideGuide (TouristTrip)
└─ hasPart → Trail (SportsActivityLocation) ×ordered
└─ field_start_location → Place
└─ field_parking → Place
└─ field_related_pois → TouristAttraction ×many

Trail (SportsActivityLocation)
└─ isPartOf → Zone (Place)
└─ subjectOf → RideGuide [reverse ref via Views]
```

> **Key note:** `field_trails` (ordered entity reference) is the most important field — that ordered sequence drives both the route timeline UI *and* the `hasPart` + `itinerary` JSON-LD output simultaneously. One field, two outputs. Get the Twig template right and every ride guide page generates correct structured data automatically.

---

## Schema.org JSON-LD

Injected in `<head>` via the `schemadotorg_jsonld` sub-module.

```json
{
  "@context": "https://schema.org",
  "@type": "TouristTrip",
  "@id": "https://yoursite.com/rides/darling-hill-signature",
  "name": "The Darling Hill Signature",
  "description": "Intermediate mountain bike loop in East Burke, VT. Sustained paved climb earns Darling Hill ridge, then Kitchel — 800 ft of berms and rhythm sections back to the village.",
  "touristType": "Mountain biker",
  "duration": "PT4H",
  "image": "https://yoursite.com/files/darling-hill-hero.jpg",

  "itinerary": {
    "@type": "ItemList",
    "itemListElement": [
      {
        "@type": "ListItem",
        "position": 1,
        "item": {
          "@type": "Place",
          "name": "East Burke Village",
          "geo": { "@type": "GeoCoordinates", "latitude": 44.5893, "longitude": -71.9239 }
        }
      },
      {
        "@type": "ListItem",
        "position": 2,
        "item": { "@id": "https://yoursite.com/trails/kitchel" }
      },
      {
        "@type": "ListItem",
        "position": 3,
        "item": { "@id": "https://yoursite.com/trails/herbs" }
      }
    ]
  },

  "hasPart": [
    {
      "@type": "SportsActivityLocation",
      "@id": "https://yoursite.com/trails/east-darling-hill-road",
      "name": "East Darling Hill Road",
      "amenityFeature": [
        { "@type": "LocationFeatureSpecification", "name": "surface", "value": "pavement" }
      ],
      "additionalProperty": [
        { "@type": "PropertyValue", "name": "elevationGain", "value": "600 ft" },
        { "@type": "PropertyValue", "name": "distance", "value": "2.5 miles" }
      ]
    },
    {
      "@type": "SportsActivityLocation",
      "@id": "https://yoursite.com/trails/kitchel",
      "name": "Kitchel",
      "description": "Black-rated descent on Darling Hill. 800 ft, 1.5 miles of berms, whoops, and rhythm sections.",
      "amenityFeature": [
        { "@type": "LocationFeatureSpecification", "name": "difficulty", "value": "black" },
        { "@type": "LocationFeatureSpecification", "name": "surface", "value": "singletrack" },
        { "@type": "LocationFeatureSpecification", "name": "features", "value": "berms, whoops, rhythm sections" }
      ],
      "additionalProperty": [
        { "@type": "PropertyValue", "name": "elevationLoss", "value": "800 ft" },
        { "@type": "PropertyValue", "name": "direction", "value": "one-way" }
      ],
      "isPartOf": { "@id": "https://yoursite.com/zones/darling-hill" }
    },
    {
      "@type": "SportsActivityLocation",
      "@id": "https://yoursite.com/trails/herbs",
      "name": "Herbs",
      "description": "Blue-rated descent through hardwood forest back to East Burke village.",
      "amenityFeature": [
        { "@type": "LocationFeatureSpecification", "name": "difficulty", "value": "blue" },
        { "@type": "LocationFeatureSpecification", "name": "surface", "value": "singletrack" }
      ],
      "isPartOf": { "@id": "https://yoursite.com/zones/darling-hill" }
    }
  ],

  "additionalProperty": [
    { "@type": "PropertyValue", "name": "totalDistance", "value": "15 miles" },
    { "@type": "PropertyValue", "name": "totalElevationGain", "value": "1800 ft" },
    { "@type": "PropertyValue", "name": "difficulty", "value": "intermediate" },
    { "@type": "PropertyValue", "name": "bikeType", "value": "mountain bike, class 1 eMTB" }
  ],

  "provider": {
    "@type": "Organization",
    "name": "Kingdom Trail Association",
    "url": "https://www.kingdomtrails.org"
  },

  "isLocatedIn": {
    "@type": "Place",
    "name": "East Burke, Vermont",
    "geo": { "@type": "GeoCoordinates", "latitude": 44.5893, "longitude": -71.9239 },
    "containedInPlace": {
      "@type": "AdministrativeArea",
      "name": "Northeast Kingdom, Vermont"
    }
  }
}
```

### JSON-LD implementation notes

- The `hasPart` trail nodes each resolve to their own Drupal node URL — Google can crawl and understand the full hierarchy independently of the ride guide page.
- `itinerary` (ItemList) and `hasPart` serve different roles: `itinerary` = ordered narrative sequence for potential rich result display; `hasPart` = structural knowledge graph relationship.
- `elevationGain` is not a native Schema.org property for `SportsActivityLocation` — `additionalProperty` with `PropertyValue` is the correct pattern.
- Validate at [validator.schema.org](https://validator.schema.org) and [Google's Rich Results Test](https://search.google.com/test/rich-results).

---

## Twig implementation sketch

The key template logic — outputting `hasPart` from the ordered `field_trails` entity reference:

```twig
{# ride-guide--full.html.twig #}

{% set trail_parts = [] %}
{% for item in node.field_trails %}
  {% set trail = item.entity %}
  {% set trail_parts = trail_parts|merge([{
    '@type': 'SportsActivityLocation',
    '@id': url('entity.node.canonical', {'node': trail.id()}, {'absolute': true}),
    'name': trail.label(),
    'description': trail.field_description.value,
    'amenityFeature': [
      { '@type': 'LocationFeatureSpecification', 'name': 'difficulty', 'value': trail.field_trail_difficulty.value },
      { '@type': 'LocationFeatureSpecification', 'name': 'surface', 'value': trail.field_surface_type.value }
    ]
  }]) %}
{% endfor %}

{# Merge into the main JSON-LD object and print in <head> via json_encode #}
```

> In practice, use the `schemadotorg_jsonld` module to handle most of this automatically via field mappings. The Twig sketch above covers cases where you need custom nesting or properties not supported by the module's default output.
