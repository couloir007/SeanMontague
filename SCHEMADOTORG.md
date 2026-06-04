# Schema.org Blueprints — SeanMontague.com Field Model Reference

> Read this before creating any content type, field, or JSON-LD customisation
> on this site. The patterns here are consistent with the BRE VT buildout —
> do not shortcut them.

---

## What Schema.org Blueprints does

Schema.org Blueprints (`drupal/schemadotorg`) is the engine behind every
content type on this site. It does three things simultaneously:

1. **Generates fields** — when you map a content type to a Schema.org type,
   Blueprints creates (or reuses) fields whose machine names, storage types,
   and cardinality match the schema property.

2. **Creates shared field storages** — fields like `schema_address`,
   `schema_image`, `schema_geo`, `schema_identifier` are stored once at the
   node level and shared across every content type that uses them. `article`,
   `trip`, `place`, and `event` all share the same `field.storage.node.schema_image`
   storage. There is one storage, many bundle instances.

3. **Outputs JSON-LD automatically** — the `schemadotorg_jsonld` submodule
   reads the mapping config and emits a `<script type="application/ld+json">`
   block on every mapped node. No custom code needed for standard properties.

---

## The shared storage rule — the most important thing

**Never create a new field storage for a property that already exists as a
shared `schema_*` storage.**

When Blueprints maps `article` → `BlogPosting` and creates `schema_image`,
it reuses `field.storage.node.schema_image` — the same storage that `trip`,
`place`, and `event` already use. The storage is defined once; each content
type gets its own `field.field.node.<bundle>.schema_image` bundle instance.

**What goes wrong if you ignore this:**

```
# WRONG — creates a duplicate storage
field.storage.node.field_hero_image   ← new storage, different machine name
field.field.node.article.field_hero_image

# RIGHT — reuses the shared storage
field.storage.node.schema_image       ← already exists, shared
field.field.node.article.schema_image ← bundle instance only
```

Duplicating the storage breaks JSON-LD output (Blueprints doesn't know about
your custom field), wastes a database column, and produces a content type
out of pattern with the rest of the site.

**How to check if a storage already exists:**

```bash
lando drush php-eval "
  \$storages = \Drupal::entityTypeManager()
    ->getStorage('field_storage_config')
    ->loadByProperties(['entity_type' => 'node']);
  foreach (\$storages as \$s) {
    if (str_starts_with(\$s->getName(), 'schema_')) {
      echo \$s->getName() . ' (' . \$s->getType() . ')' . PHP_EOL;
    }
  }
"
```

Or check `config/field.storage.node.schema_*.yml` files directly.

---

## `schema_*` vs `field_*` — when to use each

| Situation | Prefix | How to create |
|---|---|---|
| Maps to a Schema.org property | `schema_` | Via Blueprints mapping UI — reuses or creates shared storage |
| Purely editorial, no schema equivalent | `field_` | Via Structure → Content types → Manage fields after Blueprints has run |

**Examples from `article`:**

| Field | Prefix | Reason |
|---|---|---|
| `schema_image` | `schema_` | Maps to `schema:image` — shared storage, reused |
| `schema_distance` | `schema_` | Maps to `schema:distance` — Blueprints |
| `schema_elev_gain` | `schema_` | Maps to `schema:elevation` family — Blueprints |
| `schema_geo` | `schema_` | Maps to `schema:geo` (Geofield) — Blueprints |
| `field_map_tiles` | `field_` | No schema equivalent — per-article tile override |
| `field_route_type` | `field_` | No schema equivalent — drives map line style |

The `body` field is an exception — Drupal core field, maps to `articleBody`
in the Blueprints mapping, but uses the Drupal default name.

---

## The correct workflow for adding a content type

**Always in this order:**

1. **Go to `/admin/config/schemadotorg/mappings/add`**
   - Select entity type: `node`
   - Select schema type (e.g. `BlogPosting`)
   - Set the content type label and machine name
   - Review proposed field mapping — accept what fits, uncheck what doesn't

2. **Save the mapping** — Blueprints creates the content type, all `schema_*`
   fields, and registers JSON-LD output in one step.

3. **Add non-schema fields manually** at Structure → Content types →
   [type] → Manage fields — only for fields with no schema equivalent.

4. **Export config:**
   ```bash
   lando drush cex -y
   ```

5. **Commit** — config ships with the feature.

**Never** create a content type at Structure → Content types → Add first,
then try to wire Blueprints to it after. Blueprints expects to own the
creation. To add schema properties to an existing type, use the Blueprints
mapping edit screen, not Manage fields.

---

## Adding a schema property to an existing content type

Go to `/admin/config/schemadotorg/mappings` → find the mapping → Edit →
add the property in the mapping form. Blueprints will create or reuse the
field storage and add the bundle instance.

**Do not** go to Structure → Manage fields and create a `schema_*` field
manually. You will create a new storage instead of reusing the shared one,
and the JSON-LD wiring will be missing.

---

## JSON-LD — what's automatic vs what needs custom code

### Automatic (do nothing)

For any field wired in the Blueprints mapping, `schemadotorg_jsonld` outputs
it in the JSON-LD block automatically. No PHP required.

```json
{
  "@type": "BlogPosting",
  "headline": "South Kinsman via Lonesome Lake",
  "articleBody": "...",
  "image": { "@type": "ImageObject", "url": "https://..." },
  "datePublished": "2026-04-15"
}
```

### Custom code needed — three cases only

**Case 1: Override the `@type`**

If a node needs a more specific subtype based on field values, use:

```php
function seanmontague_schemadotorg_schemadotorg_jsonld_schema_type_entity_alter(
  array &$data,
  FieldableEntityInterface $entity
): void {
  if ($entity->bundle() !== 'article') return;
  // Alter $data['@type'] based on field values.
}
```

Note: this hook fires **twice** for nodes — once as the entity type, once
as the `WebPage` wrapper. Always check `$data['@type']` to target the
correct pass.

**Case 2: Add properties not in the mapping**

```php
// Add computed or derived properties to $data.
// Example: add spatialCoverage from a GPX track bounding box.
```

**Case 3: Suppress or transform an auto-output property**

Alter `$data` in the same hook to unset or rewrite what Blueprints emits.

### What NOT to do

- **Do not call `hook_node_view()`** to output JSON-LD for mapped content
  types. `schemadotorg_jsonld` already handles it — double-output creates
  duplicate `<script>` blocks.
- **Do not build a custom JSON-LD builder class** for standard content types
  unless you have Case 1/2/3 requirements. `seanmontague_schemadotorg`
  module has dedicated `JsonLd` classes only because of complex geo/map
  requirements. A standard `Event` or `Place` type needs none of that.
- **Do not output `sameAs` or `about` links manually** — Blueprints handles
  cross-references via `schema_about` and `schema_same_as` fields if wired
  in the mapping.

### Breadcrumb data location

Breadcrumb data in JSON-LD lives at:
```php
$data['schemadotorg_jsonld_breadcrumb_schemadotorg_jsonld']
// NOT at $data['breadcrumb']
```

---

## Drupal config files — what Blueprints creates

When Blueprints maps `node:article` → `BlogPosting`, it creates or updates:

```
config/
├── node.type.article.yml
├── schemadotorg.schemadotorg_mapping.node.article.yml  ← source of truth for JSON-LD
├── field.storage.node.schema_distance.yml    ← created if not already shared
├── field.field.node.article.schema_distance.yml        ← bundle instance
├── field.storage.node.schema_image.yml       ← reused (already exists)
├── field.field.node.article.schema_image.yml           ← new bundle instance
├── core.entity_form_display.node.article.default.yml
└── core.entity_view_display.node.article.default.yml
```

The `schemadotorg_mapping` config file is the source of truth for JSON-LD
output. If a field is in `schema_properties` here, it's in the JSON-LD.
If it's not, it isn't — regardless of what fields exist on the bundle.

---

## Checking what a mapping outputs

```bash
lando drush config:get schemadotorg.schemadotorg_mapping.node.article
```

The `schema_properties` section maps Drupal field names to Schema.org
property names:

```yaml
schema_properties:
  body: articleBody
  schema_date_published: datePublished
  schema_image: image
  schema_distance: distance
  schema_elev_gain: elevation
  schema_geo: geo
  title: headline
```

To add a property to the JSON-LD output, add it to this mapping via the
Blueprints UI (Edit mapping → add property). Do not hand-edit the YAML —
Blueprints validates the mapping on save and will reject invalid properties.

---

## Geo field patterns — CRITICAL

Two different geo patterns are used on this site. Never mix them up.

### Article — `schema_geo` is a Geofield

```twig
{% set has_geo = not node.schema_geo.isEmpty() %}
{% set map_center = node.schema_geo.lat.value ~ ',' ~ node.schema_geo.lon.value %}
```

`schema_geo.lat` and `schema_geo.lon` return `FloatData` TypedData objects —
**not** raw PHP floats. Always append `.value` before string concatenation or
`json_encode`.

### Place — separate decimal fields

`place` uses `schema_latitude` and `schema_longitude` (no Geofield):

```twig
node.schema_latitude.value
node.schema_longitude.value
```

### Reading Place geo from a referenced Place on an article

```twig
{% set place = node.schema_place.entity %}
{% set has_place = place and place.schema_latitude.value is not empty %}
{% set map_center = place.schema_latitude.value ~ ',' ~ place.schema_longitude.value %}
```

The common mistake is `place.schema_geo.value.lat` — Place has no
`schema_geo`, so `has_place` silently evaluates false.

### geo_entity (poi / destination)

```twig
{# lat/lon: always .value #}
'lat': poi.schema_geo.lat.value,
'lon': poi.schema_geo.lon.value,

{# label: geo_entity uses "label" in entity keys — returns FieldItemList, not string #}
'label': '<strong>' ~ poi.label.value ~ '</strong>',
```

### schema_geoshape file entity

```twig
{# filename is a FieldItemList — always .value for string ops #}
{% set is_gpx = geo_file and geo_file.filename.value matches '/\\.gpx$/i' %}
```

Full traversal for `schema_geoshape` (a `data_download` media reference):
```twig
{% set geo_media = node.schema_geoshape.entity %}
{% set geo_file  = geo_media ? geo_media.field_media_file.entity : null %}
{% set is_gpx    = geo_file and geo_file.filename.value matches '/\\.gpx$/i' %}
```

---

## Verifying fields exist on a bundle

```bash
lando drush php-eval "
  \$fields = \Drupal::service('entity_field.manager')
    ->getFieldDefinitions('node', 'article');
  foreach (\$fields as \$name => \$def) {
    if (str_starts_with(\$name, 'schema_') || str_starts_with(\$name, 'field_')) {
      echo \$name . ' (' . \$def->getType() . ')' . PHP_EOL;
    }
  }
"
```

---

## Known shared storages on this site

These `schema_*` storages exist and are shared across content types.
**Reuse them — never duplicate.**

| Storage | Type | Used by |
|---|---|---|
| `schema_image` | entity_reference (media) | All content types |
| `schema_audio` | entity_reference (media) | `article`, `trip` |
| `schema_geo` | geofield | `article`, `geo_entity` |
| `schema_latitude` | decimal | `place` |
| `schema_longitude` | decimal | `place` |
| `schema_address` | address | `place` |
| `schema_distance` | decimal | `article` |
| `schema_elev_gain` | integer | `article` |
| `schema_elev_loss` | integer | `article` |
| `schema_elev_min` | integer | `article` |
| `schema_elev_max` | integer | `article` |
| `schema_difficulty` | list (text) | `article` |
| `schema_geoshape` | entity_reference (media) | `article` |
| `schema_place` | entity_reference (node:place) | `article` |
| `schema_trip` | entity_reference (node:trip) | `article` |
| `schema_category` | entity_reference (taxonomy) | `article` |
| `schema_activity_type` | entity_reference (taxonomy) | `article` |
| `schema_destination` | entity_reference (geo_entity) | `trip` |
| `schema_itinerary` | entity_reference (node) | `trip` |
| `schema_date_published` | datetime | `article` |
| `schema_telephone` | telephone | `place` |
| `schema_same_as` | link | Multiple types |
| `schema_about` | entity_reference | Multiple types |

---

## Views and `schema_*` fields

Schema.org fields behave identically to `field_*` fields in Views. Two
rules learned empirically:

**Rule 1 — No `_value` suffix on field column names** (except datetime sorts)

```yaml
# CORRECT
schema_distance:
  table: node__schema_distance
  field: schema_distance
  plugin_id: field

# WRONG — _value suffix causes Views to silently fail
schema_distance:
  field: schema_distance_value
```

Exception: datetime fields used as sort criteria need `_value`:
```yaml
field: schema_date_published_value   # sort only
```

**Rule 2 — Taxonomy term filters use `taxonomy_index_tid`**

```yaml
filter:
  schema_category_target_id:
    plugin_id: taxonomy_index_tid
    vid: category
```

Never use `entity_reference` or `numeric` plugin for term filters.

**Rule 3 — `node_path` plugin requires `absolute: true`**

For path fields in Views to return usable URLs:

```yaml
fields:
  path:
    plugin_id: node_path
    absolute: true
```

---

## seanmontague_schemadotorg module

The `seanmontague_schemadotorg` custom module contains dedicated `JsonLd`
classes for cases requiring custom JSON-LD output:

| Class | Bundle | Reason |
|---|---|---|
| `ArticleJsonLd` | `article` | Geo/map data, spatialCoverage, isPartOf trip |
| `TouristTripJsonLd` | `trip` | Itinerary, destination cross-refs |
| `PointOfInterestJsonLd` | `geo_entity:poi` | TouristAttraction subtype |
| `LodgingJsonLd` | (where applicable) | Lodging cross-refs in trip context |

**Do not use this module as the pattern for new content types** unless they
have complex geo or cross-referencing requirements. Standard types like
`event` and `web_page` are handled automatically by Blueprints +
`schemadotorg_jsonld`.

---

## Quick reference — do / don't

| Do | Don't |
|---|---|
| Create content types via Blueprints mapping UI | Create content types at Structure → Content types → Add |
| Reuse existing `schema_*` shared storages | Create new storages for schema-mapped properties |
| Add non-schema fields manually after Blueprints | Create `schema_*` fields manually via Manage fields |
| Extend JSON-LD via `hook_schemadotorg_jsonld_schema_type_entity_alter` | Add `hook_node_view()` JSON-LD output for mapped types |
| Export config with `lando drush cex` after every UI change | Hand-edit Blueprints mapping YAML |
| Check existing storages before adding a field | Assume a storage doesn't exist |
| Check `$data['@type']` in the alter hook (fires twice) | Target node alter hook output without type guard |
| Access geo values with `.value` suffix | Use `schema_geo.lat` directly in string context |
