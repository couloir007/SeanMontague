# Content model

Overview of the content types and the thinking behind them. Field-level detail for existing types lives in `schema-mappings.md` and the Drupal config (`config/sync/`). Route geometry and stats are owned by TrailMapper (see `docs/integrations/trailmapper-interface.md`), never by Drupal fields in this model.

## Philosophy

Places are hubs. Destinations are where you sleep and base from.
POIs are what the day is actually about. A trail report is a
`BlogPosting` with stats. A trip is a `TouristTrip` with an itinerary
of day articles, each anchored to a `TouristDestination`.

Schema.org is worth doing correctly. `contentLocation` for the destination
an article is about. `mentions` for the POIs visited. `containedInPlace`
for Galway within Ireland. `touristType` as plain text because "Traditional
Music" is more honest than a taxonomy term.

The structured data serves two purposes: Google's knowledge graph, and
future-Sean who needs to query across content types.

## Planned: Kingdom Trails content types

Not built. None of these bundles exist in `config/sync/` yet. Source: kingdom-trails-strategy.md (April 2026), technical sections only.

| Drupal content type | Schema.org type | Key properties |
|---|---|---|
| Trail | `SportsActivityLocation` | route stats (TrailMapper), difficulty, surfaceType, seasonalOpenings, geo |
| Route / Ride Guide (`ride_guide`) | `TouristTrip` | itinerary, hasPart (Trail nodes), audience, description; route stats (TrailMapper) |
| Point of Interest | `TouristAttraction` | geo, image, description, nearbyAttractions, openingHours |
| Zone / Area | `Place` + `hasPart` | containsPlace (Trails), description, geo bounding box, image |
| Local Business | `LocalBusiness` | name, address, telephone, openingHours, geo |
| Article / Guide | `Article` + `HowTo` | Step-by-step rides as HowTo; editorial as Article (HowTo: open, revisit as the project evolves) |
| Season / Condition | `Event` + `openingHoursSpecification` | Seasonal trail openings, grooming updates |

POIs and Local Businesses need indexable pages; whether they stay geo_entity or become node bundles is pending the geo_entity canonical-route check.

The `hasPart` / `isPartOf` relationship between Zone, Trail and Segment gives a machine-readable hierarchy of the whole network. See `docs/reference/darling-hill-technical-spec.md` for a historical worked example of the field model.

## Planned build sequence

1. **Ride guide content types first.** They validate the Schema.org modeling before any trail-level nodes. A `TouristTrip` node with a handful of `SportsActivityLocation` `hasPart` relationships, a Leaflet map and a narrative targets the beginner-guide query first.
2. **Zone pages** as `Place` + `containsPlace` (trails). They become the navigation backbone.
3. **Trail-level nodes**, seeded from TrailMapper data, with AI-assisted descriptions fact-checked against firsthand experience.
4. **Area guide + POI nodes** (`TouristAttraction`, `LocalBusiness`, `FoodEstablishment`).
5. **Conditions / seasonal content** as `Article` nodes that reference Trail and Zone nodes via entity reference.

## Technical stack notes (from the strategy file)

| Layer | Tool | Role |
|---|---|---|
| CMS | Drupal + Schema.org Blueprints | Content architecture; JSON:API; JSON-LD output |
| Geodata | GPX / GeoJSON | Rendered via the Leaflet stack; stats from TrailMapper |
| Templates | Twig with JSON-LD injection | Trail cards, route summaries, zone pages |
| Philosophy | Permaculture zone design | Build from the center outward: ride guides and map, then trail profiles, then area guide, then conditions and history |

The strategy file named the Drupal Leaflet module for maps. That is superseded: maps use `map.js` in the Surface theme, and the Leaflet module formatter must not be used on article pages (see `CLAUDE.md`, "Mapping").
