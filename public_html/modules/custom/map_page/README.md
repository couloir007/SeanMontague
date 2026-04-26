# Map Page

Provides full-page Leaflet mapping functionality for seanmontague.com,
driven by `geo_entity` bundles. Exposes a configurable GeoJSON endpoint
and wires Views display extender template suggestions for map layouts.

---

## Overview

- **GeoJSON endpoint** — `/map-page/map-items/{bundle}` serves any
  `geo_entity` bundle as a GeoJSON FeatureCollection for client-side
  Leaflet rendering.
- **Views integration** — a `map_page_display_extender` drives template
  selection (`legend` or `no_legend`) per Views display.
- **Settings form** — `/admin/config/map-page/settings` configures the
  default bundle, geo field name, and field map for the endpoint.

---

## Prerequisites

- Enable modules: Field, Views, Geofield, Taxonomy, Paragraphs,
  Serialization, `geo_entity`, `map_page`.
- Clear caches after enabling or updating: `lando drush cr`.

---

## GeoJSON endpoint

Returns all published `geo_entity` records for the given bundle as a
GeoJSON FeatureCollection. Used by `map.js` to load map markers.

```
GET /map-page/map-items/{bundle}
GET /map-page/map-items          ← uses configured default bundle
```

**Response:**
```json
{
  "type": "FeatureCollection",
  "features": [
    {
      "type": "Feature",
      "id": "42",
      "geometry": { "type": "Point", "coordinates": [-71.91, 44.59] },
      "properties": { "title": "Kingdom Trails HQ" }
    }
  ]
}
```

Configure the default bundle, geo field, and additional field mappings
at `/admin/config/map-page/settings`.

---

## Views display extender

Add the `Map Page Display Extender` to any Views page display. Set the
`template` option to `legend` or `no_legend` to select the map layout:

- **legend** — sidebar list + map panel (`views-view--map-page--legend.html.twig`)
- **no_legend** — full-width map (`views-view--map-page--no_legend.html.twig`)

The `map-interaction.js` script reads `drupalSettings.map_page.itemsEndpoint`
to fetch GeoJSON and render markers after page load.

---

## Commands

```bash
lando drush cr          # clear cache after template or code changes
lando drush cex         # export config after settings changes
```
