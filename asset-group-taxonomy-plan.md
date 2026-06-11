# Asset Group Taxonomy — Backend Filtering for Media & Geo Entities

> **Status:** Planned, not built. Deferred to focus on other work first.
> **Purpose:** A cross-cutting organizational tag so the backend admin can filter
> *everything uploaded for a trip/project* — images, videos, tracks, POIs,
> lodging, destinations — in one filtered admin view. **Backend management only,
> not a public-facing facet.**

---

## What this solves

As tracks, POIs, images, lodging, and destinations accumulate across many trips
and standalone articles, the admin needs to find "everything from the Ireland
trip" or "all standalone trail-report assets" without hunting through an
undifferentiated media library. A single reusable taxonomy tag applied across all
asset types provides one exposed filter per admin listing.

This is **internal management metadata**, like `field_key` and `field_route_type`
— it uses the `field_` prefix (NOT `schema_`) and does **not** appear in JSON-LD.

---

## Why taxonomy (and not entity reference / derivation)

This was debated. For a **public-facing facet**, an entity reference to the
existing `trip` node would be cleaner (no duplication of the trip, which already
exists as a `TouristTrip` node; no drift). But this is **backend-only admin
filtering**, where:

- The lowest-friction tool wins.
- "Drift" (an occasionally-stale tag) is a non-issue — it's just admin
  convenience, no visitor sees it, fix it when noticed.
- A flat taxonomy term + one exposed filter on the admin media view is the
  simplest possible setup.

So for this specific need, **taxonomy is the pragmatic correct choice.** (If a
public "browse by trip" facet is ever wanted, revisit — that would argue for
referencing the `trip` node instead.)

---

## The two entity systems this spans

"Media of all kinds" actually crosses **two different entity types**, so the
field is attached in two places (same vocabulary, same field name, but separate
field *storages* — Drupal field storage is per-entity-type and cannot span
types):

| Entity type  | Bundles to tag                          |
|--------------|------------------------------------------|
| `media`      | `image`, `video`, `data_download` (tracks) |
| `geo_entity` | `poi`, `lodging`, `destination`          |

> **Confirm bundle machine names before building.** Especially the video bundle —
> it may be `video`, `remote_video`, or similar. Run:
> ```
> lando drush php:eval 'foreach (\Drupal::service("entity_type.bundle.info")->getBundleInfo("media") as $b => $i) echo $b . "\n";'
> lando drush php:eval 'foreach (\Drupal::service("entity_type.bundle.info")->getBundleInfo("geo_entity") as $b => $i) echo $b . "\n";'
> ```

---

## Open decisions (resolve before building)

1. **Field name.** Proposed `field_asset_group`. Alternatives: `field_track_group`,
   `field_media_group`, `field_project`. Pick one that reads as "which trip/project
   this belongs to." Must be consistent across all bundles.

2. **Single or multi-value?**
   - **Multi-value (cardinality -1):** an asset can belong to multiple groups —
     e.g. a reusable destination like "Galway" appears in several trips; an image
     reused across posts. **Leaning this way for flexibility.**
   - **Single:** each asset belongs to exactly one trip/group. Simpler.

3. **Vocabulary name.** Proposed `asset_group`. Terms added as you go:
   "Ireland 2025", "South Kinsman", "Standalone Reports", etc.

4. **One vocabulary or two axes?** Earlier discussion noted two possible axes:
   *which trip* (grouping) vs *what kind* (trip-leg vs standalone report). For
   backend convenience, **one flat vocabulary with mixed terms** is simplest.
   Split into two fields only if you need to filter by trip AND kind
   simultaneously. **Default: one vocabulary.**

---

## Build steps (once decisions are made)

### 1. Create the vocabulary
```
lando drush php:eval '\Drupal\taxonomy\Entity\Vocabulary::create(["vid" => "asset_group", "name" => "Asset Group"])->save();'
```
Or via UI: Structure → Taxonomy → Add vocabulary → "Asset Group" (`asset_group`).

### 2. Add terms (as needed, ongoing)
UI: Structure → Taxonomy → Asset Group → Add term. One per trip/group.

### 3. Create the field on each entity type
The field references the `asset_group` vocab. Because media and geo_entity are
different entity types, you create the field **twice** (two storages, same name,
same target vocab):

- **Media bundles** (`image`, `video`, `data_download`): add field
  `field_asset_group` (entity reference → taxonomy_term, target bundle
  `asset_group`). Create storage once on `media`, add instance to each bundle.
- **Geo_entity bundles** (`poi`, `lodging`, `destination`): same field name
  `field_asset_group`, storage on `geo_entity`, instance per bundle.

Cardinality per decision #2 (likely -1 / unlimited).

Easiest via UI (Manage Fields on each bundle, "Re-use an existing field" after
the first to share within each entity type). Then:
```
lando drush cex
git add config/sync && git commit -m "Add field_asset_group across media + geo_entity bundles"
```

> **Shared-storage note (matches your Schema.org discipline):** within each entity
> type, create the storage once and re-use the field instance on subsequent
> bundles. `field.storage.media.field_asset_group` is shared by image/video/
> data_download; `field.storage.geo_entity.field_asset_group` is shared by
> poi/lodging/destination. Do not create duplicate storages per bundle.

### 4. Add exposed filter to admin views
- **Media admin** (`admin/content/media`, view `media`): add `field_asset_group`
  as an **exposed filter** so you can filter the management list by group.
- **Geo_entity admin** (if one exists): same. If geo_entities have no admin
  listing view, consider adding a simple admin view for them with the exposed
  filter — that's arguably the main payoff for tagging POIs/lodging/destinations.

```
lando drush cex
git add config/sync && git commit -m "Expose asset_group filter on media/geo_entity admin views"
```

---

## Verification

- Tag a few assets of different types with the same term ("Ireland 2025").
- On `admin/content/media`, filter by that term → all tagged media of every type
  appear together.
- Confirm the field does NOT leak into JSON-LD (it's `field_`, not `schema_`, so
  it shouldn't — but spot-check an article's `ld+json`).

---

## Notes / gotchas

- **Not Schema.org** — `field_` prefix, no JSON-LD mapping. Internal only.
- **Field storage is per entity type** — you will have two storages (`media` and
  `geo_entity`) with the same field name. This is expected and correct; fields
  cannot span entity types in a single storage.
- **The `trip` node already exists** as a `TouristTrip`. This taxonomy term
  *duplicates* that grouping for admin convenience. Acceptable for backend-only
  use. If you ever build a public "browse by trip" feature, prefer referencing the
  `trip` node over the taxonomy term to avoid maintaining two sources of truth.
- **Confirm bundle machine names** (esp. video) before creating field instances —
  see the bundle-listing commands above.
