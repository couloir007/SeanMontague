# seanmontague_schemadotorg — JSON-LD enrichment & correction

Custom module that alters the JSON-LD that Schema.org Blueprints emits for
seanmontague.com. Blueprints produces a correct-ish baseline; this module fixes
its quirks and adds the properties Blueprints can't infer (mentions, trip times,
knowsAbout, geo on referenced entities, etc.).

**It does NOT define content types or fields** — that's Blueprints (see
`SCHEMADOTORG.md`). This module only touches the rendered JSON-LD.

---

## Two hooks — know which fires when

| Hook | Fires | Use for |
|---|---|---|
| `..._jsonld_schema_type_entity_alter()` | once **per entity** Blueprints resolves — the routed node, the WebPage wrapper, AND every referenced entity (geo_entity POIs, the web_site anchor) | per-bundle entity enrichment |
| `..._jsonld_alter()` | once **per page**, on the fully assembled data array | page-level corrections + anything that needs the whole tree |

### Gotcha 1 — the per-entity hook fires TWICE for the routed node
Blueprints resolves the routed node once as its Schema.org type (`Article`,
`TouristTrip`, …) and again as the `WebPage` wrapper it adds around every node
page. **Always guard on `$data['@type']`** so enrichment lands on the right pass:

```php
if ($bundle === 'article' && ($data['@type'] ?? NULL) === 'Article') { … }
```

### Gotcha 2 — `@type` can be an ARRAY
A person rendered as the homepage mainEntity arrives as
`['Person','ProfilePage']`. Use `in_array('Person', (array) $data['@type'], TRUE)`,
**never** `=== 'Person'`.

### Gotcha 3 — referenced mainEntity stubs skip the per-entity hook
Blueprints emits a referenced `mainEntity` (e.g. the homepage Person via
`schema_main_entity`) as a minimal stub (type/name/url) and does **not** run it
through the per-entity alter hook. So enrichment for it has to happen in the
**page-level** hook, applied to
`$data['schemadotorg_jsonld_entity']['mainEntity']`. This is why
`PersonJsonLd::alter()` is called from BOTH hooks (per-entity for a routed
person; page-level for the referenced-stub case).

### Gotcha 4 — normalizers run LAST, at page level
`_seanmontague_normalize_url_keys()` (`@url` → `url`; `@id` left alone — it's a
valid JSON-LD keyword) and `_seanmontague_normalize_iso_dates()` (date keys →
strict ISO 8601 via `date('c')`) sweep the **whole assembled tree** and must run
after every other correction in `..._jsonld_alter()`. Add new page-level fixes
ABOVE these two calls.

---

## JsonLd classes (`src/JsonLd/`)

One class per Schema.org type, each a static `alter(array &$data, $entity,
$bubbleable_metadata)`. The `.module` decides which to call; the class only knows
how to enrich its own `$data`.

| Class | Applied to | Adds |
|---|---|---|
| `ArticleJsonLd` | article node (Article pass) | `mentions`, `contentLocation` (spatial) |
| `TouristTripJsonLd` | tourist_trip (TouristTrip pass) | `arrivalTime` / `departureTime`, `copyrightYear` |
| `PersonJsonLd` | person (Person pass + referenced mainEntity stub) | `knowsAbout` (← schema_knows_about), `description` (← body, plain-text) |
| `PointOfInterestJsonLd` | geo_entity:poi | sets `@type: TouristAttraction`, `@id`, `geo`, `containedInPlace`, `image` |
| `LodgingJsonLd` | geo_entity:lodging | sets `@type: LodgingBusiness`, `@id`, `geo`, `address`, `additionalProperty` |

geo_entity POIs/lodging are handled directly in the per-entity hook (they're not
nodes) and `return` early before the node logic.

---

## Page-level corrections (in `..._jsonld_alter()`)

- **copyrightYear** on the mainEntity from `schema_date_published` (Blueprints
  defaults to node created time — wrong for content dated to a past trip).
- **Front page**: `@url` set to the site root for the entity + WebPage wrapper.
- **Breadcrumb**: fills the empty Home `@id`, drops duplicate empty-`@id` crumbs,
  resequences `position`, and removes the breadcrumb entirely on the front page.
- **isPartOf WebSite** `@url`/`@id` → site root (the web_site node is a data
  anchor at `/node/NNN`, not a routable page). Done in
  `_seanmontague_schema_is_part_of()`, on the WebPage pass.

---

## Planned — landing-page mainEntity (NOT built yet)

The `page` bundle will be reused for listing pages, each a `CollectionPage` whose
`mainEntity` is an `ItemList`:

- Homepage → `mainEntity` = **Person** (done, via `schema_main_entity`).
- Trips landing → `CollectionPage`, `mainEntity` = `ItemList` of `TouristTrip`s.
- Trail-reports landing → `CollectionPage`, `mainEntity` = `ItemList` of article
  (BlogPosting) nodes.

Intended approach: generate the `ItemList` from the **listing view** (not
hand-set fields) in `..._jsonld_schema_type_entity_alter()`, via new
`CollectionPageJsonLd` / `ItemListJsonLd` classes following the static-`alter`
pattern above. Remember Gotcha 4 — the url/date normalizers already run last, so
new keys those classes emit get normalized for free.

---

## Verified accessor

geo_entity geofield: `$entity->get('schema_geo')->lat` / `->lon` returns the
float directly. `->lat->value` is NULL; `.0.value.lat` is NULL. (Same rule the
theme/services use — see `MODULES-trail_mapper-vs-seanmontague_map.md`.)
