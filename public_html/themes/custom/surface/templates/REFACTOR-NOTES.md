# Homepage views templates — verified against config

Checked against `config/sync/views.view.homepage_writing.yml` and
`views.view.homepage_places.yml`. Corrections from the previous pass:

## URL field differs per view (now matched to actual config)

- **Writing** has `view_node` (entity_link), **no `path` field**. → template
  uses `fields.view_node`. (My earlier switch to `fields.path` was wrong for
  this view — reverted.)
- **Places** has `path` (node_path), **no `view_node` field**. → the original
  `fields.view_node` reference was dead. Removed (places-card has no link
  anyway; `path` is `exclude: true` and unused).

So the two field templates correctly use *different* URL field IDs. Don't
"align" them — they match their own views.

### One config fix needed (writing)

`view_node` in homepage_writing has `exclude: false`, so besides feeding card's
`url` it also renders a stray visible link in each row. Set that field's
**Exclude from display = true** in the view, then `lando drush cex`. Places
already has its `path` field `exclude: true`.

## Coords — formatter verified, no change needed

homepage_places `schema_geo` uses `geofield_latlon`, separator `", "`,
`decimal_degrees` → renders `"44.5965, -71.9105"`. The coords parse in the
template is correct for this. (The earlier WKT/`POINT(...)` worry does not
apply — formatter is lat/lon.) Keep the formatter as-is.

## category_key — still the one real gap

homepage_writing has **no `field_key` field** (confirmed). `schema_category`
uses `entity_reference_label` (link:true); the template striptags it to the
plain label for the pill text, which is fine. But the pill COLOR needs
`trails|garden|maps|tech`, and there is no key field to supply it — so the
label->keyword fallback in the template is currently the only thing coloring
pills.

To make it robust (recommended), add `field_key` to the category terms and
expose it in the view:

1. Add a plain text field `field_key` (max 32) to the `category` vocabulary;
   set values `trails` / `garden` / `maps` / `tech` on the terms.
2. In homepage_writing, add the term's `field_key` as a view field
   (via the schema_category relationship or a term field), `exclude: true`.
3. `lando drush cex` and commit.

The template already prefers `fields.field_key.content` when present and only
falls back to keyword matching when it's empty — so once step 2 lands, the
fallback stops running with no template change.

## view--* wrappers

`@layouts/view/view--homepage.twig` is `{{- rows -}}` (bare). The
`views-view--homepage-*` templates correctly delegate to it, emitting cards
straight into `.section__grid`. Only the stale `homepage__*-grid` comments were
updated; logic unchanged.

## Verify after applying

- Writing: no stray link above each card (after the `exclude: true` fix); pills
  colored; cards link to articles.
- Places: coords read `44.5965° N, 71.9105° W`; tags render; not links.
