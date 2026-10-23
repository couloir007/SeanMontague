# TrailMapper interface (knowledge file for the SeanMontague.com project)

Status: **Proposed.** TrailMapper owns trail data and route stats. This site displays them. This file is the master copy of the interface, kept in the repo and copied to the TrailMapper and SeanMontague.com Claude projects.

## Ownership

| Data | Owner | Site's role |
|---|---|---|
| Trail geometry, segments, junctions | TrailMapper | Display |
| Route stats (distance, climb, descent, profile) | TrailMapper planner | Display; never compute in Drupal |
| Estimated times for curated routes | Sean, in Drupal | Authored on the node in seconds; never from TrailMapper |
| Curated routes (`ride_guide` nodes) | Sean, in Drupal | Author content; stats come from TrailMapper |
| Generated routes | TrailMapper | Display only if Sean decides to publish them (open question) |
| Gazetteer places, aliases, Wikidata QIDs | TrailMapper | Use in JSON-LD `sameAs` and place pages |
| Augmentations | TrailMapper | Display labeled, per the augmentation pattern |

## Proposed Drupal fields

- `ride_guide`: a TrailMapper route ID field. Stats are fetched from the planner by route ID and cached.
- `trail`: a TrailMapper trail ID field, mapping each node to its segments.
- Place entities: a Wikidata QID field, filled from the gazetteer.

## Planner API (proposed)

| Endpoint | Returns |
|---|---|
| `GET /routes/{id}` | Route with ordered segments, stats, profile, tier (curated or generated), status |
| `POST /routes/stats` | Stats for an ordered segment list |
| `GET /segments/{id}` | Segment attributes, per-direction stats, aliases |
| `GET /status/{area}` | Closures, with source and as-of date |
| `GET /places/{id}` | Gazetteer place, aliases, QID, geometry, site or area |

Transport is HTTPS and authentication is an API key kept in server-local settings (see `decisions.md`, "TrailMapper stays a separate system"). The base URL depends on where TrailMapper hosts the API, which is still open. Drupal fetches on cron and serves from cache with an as-of date.

## JSON-LD mapping

- **Route** maps to `TouristTrip`. Its trails go in `hasPart` as `SportsActivityLocation`. Distance and elevation gain follow the existing `PropertyValue` convention.
- **Places** get `sameAs` pointing to their Wikidata QID.
- **Generated routes,** if exposed, carry an `additionalProperty` marking them as generated.

## Rules for this project

- Don't compute route stats in Drupal. Display them live from TrailMapper (cached). A reference snapshot (meters, source route ID, as-of date) may be stored for editorial use but is never rendered. Recorded GPX tracks are out of scope for this rule.
- TrailMapper values arrive in meters. If the planner returns other units, the Drupal client converts on fetch so templates only see meters.
- Don't create `ride_guide` nodes from generated routes. A generated route becomes curated only when Sean has done it and written it up.
- Show an as-of date on anything status-related.
- **Thin-content risk:** hundreds of generated route pages would read as thin, near-duplicate content. Until Sean decides otherwise, keep generated routes out of the index: either unpublished, or served through a search interface with `noindex`.
