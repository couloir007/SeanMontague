# Claude Code Next Steps

Reference files (all at project root):
- `CLAUDE.md` — theme architecture, Twig rules, JS standards
- `public_html/themes/custom/surface/CLAUDE.md` — deep-dive theme reference

---

## Step 1 — Update CLAUDE.md: single BlogPosting bundle (PENDING)

The `trail_report` bundle has been removed. All content is now a single
`article` (BlogPosting) bundle. Category and activity_type taxonomy drive
display. CLAUDE.md still references the old two-bundle model.

```
Read CLAUDE.md.

Update CLAUDE.md to reflect the current single-bundle content model:

1. Remove all references to `trail_report` bundle from the Content Types
   table and Field Relationships section.

2. Update the `article` (BlogPosting) field list to include all fields
   that were previously split across both bundles:
   - body, title, schema_date_published
   - schema_category → DefinedTerm (trails, drupal, permaculture…)
   - schema_activity_type → DefinedTerm (bike, hike, ski…)
   - schema_trip → TouristTrip (optional — nests article under trip)
   - schema_place → Place (optional — map center, not plotted as marker)
   - schema_geoshape → File (GeoJSON/GPX — map track + elevation)
   - schema_distance → decimal (miles)
   - schema_elev_gain, schema_elev_loss, schema_elev_min, schema_elev_max → integer (ft)
   - schema_difficulty → list: Easy / Intermediate / Hard / Expert
   - schema_audio → AudioObject (optional — ElevenLabs TTS)
   - field_image → ImageObject

3. Remove the "Why two BlogPosting bundles" explanation — it no longer applies.

4. Add a note: stats fields (distance, elevation) are optional — only
   relevant for trail/ski articles. Category and activity_type drive
   which fields are relevant editorially.

5. Update the Mapping Sets section — remove the separate `trails` mapping
   set entry if it duplicated `writing`. Both were node:BlogPosting.

Show the full diff before writing.
```

---

## Step 2 — field_key on activity_type taxonomy (PENDING)

`activity_type` terms need a `field_key` machine-readable slug, identical
to the existing `field_key` on `schema_category` terms. Used by Pathauto
for URL path generation.

### 2a — Manual: add field via UI

In Drupal admin:
1. Go to Admin → Structure → Taxonomy → Activity Type → Manage fields
2. Add field: Text (plain), machine name `field_key`, label "Key", max 32 chars
3. Save

### 2b — Populate terms

| Term              | field_key |
|-------------------|-----------|
| Mountain Biking   | bike      |
| Hiking            | hike      |
| Skiing            | ski       |
| Telemark          | ski       |
| Permaculture      | permaculture |

### 2c — Export config

```bash
lando drush cex
git add config/sync
git commit -m "Add field_key to activity_type taxonomy"
```

---

## Step 3 — Path alias hook (PENDING)

Implement `hook_pathauto_pattern_alter()` to generate conditional URL
aliases based on content relationships and taxonomy.

Target alias patterns:
- Article with schema_trip → `/trips/[trip-title]/[article-title]`
- Article with activity_type → `/trails/[activity_key]/[article-title]`
- Article with category key `drupal` → `/drupal/[article-title]`
- Article with category key `permaculture` → `/permaculture/[article-title]`
- Article fallback → `/writing/[article-title]`
- TouristTrip → `/trips/[trip-title]`

```
Read CLAUDE.md.

In public_html/modules/custom/trail_mapper/trail_mapper.module, implement
hook_pathauto_pattern_alter() for the article node bundle.

Logic (evaluate in order, use first match):

1. Node has schema_trip set (entity reference to TouristTrip):
   Pattern: /trips/[node:schema_trip:entity:url:brief-title]/[node:title]

2. Node has schema_activity_type set and the term has field_key value:
   Pattern: /trails/[node:schema_activity_type:entity:field_key]/[node:title]

3. Node has schema_category with field_key = 'drupal':
   Pattern: /drupal/[node:title]

4. Node has schema_category with field_key = 'permaculture':
   Pattern: /permaculture/[node:title]

5. Fallback:
   Pattern: /writing/[node:title]

Also add a pattern for the `trip` bundle:
   Pattern: /trips/[node:title]

Requirements:
- Hook goes in trail_mapper.module after existing hooks
- Use $variables['pattern']->setPattern() to set the pattern
- Check $variables['entity']->bundle() === 'article' before processing
- Use optional chaining — fields may be empty
- Show the complete function before writing

After writing:
lando drush cr
```

### 3b — Verify

Visit a few article nodes and check that aliases are generated correctly
at Admin → Configuration → Search and Metadata → URL Aliases.

---

## Step 4 — Verify article.yml Storybook fixture (PENDING)

Ensure the Storybook fixture for the article collection reflects the
current single-bundle model with all required top-level keys.

```
Read CLAUDE.md.
Read source/patterns/collections/article/article.twig.
Read source/patterns/collections/article/article.yml.

Verify article.yml has all required top-level keys and realistic values:
- header: { title, date, category, category_key, difficulty, stats, map_id }
- map_id, map_center, map_zoom, map_markers
- geojson_url: null
- drupal_geo_rendered: null
- tiles: 'usgs-topo'
- elev_gain, elev_loss, elev_min, elev_max
- body (sample HTML prose)
- sidebar_cards: []

Also add a second Storybook story (ArticleNoMap) with:
- No map_id, no geojson_url, no stats
- Simple writing article — just header + body

Show current file contents, then show proposed changes before writing.
```

---

## Order of execution

1. Step 1 — Update CLAUDE.md (context fix, do first)
2. Step 2a-2c — field_key on activity_type (manual UI step + config export)
3. Step 3 — Pathauto hook (requires Step 2 complete)
4. Step 4 — Storybook fixture (independent, can do any time)
