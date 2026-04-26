# Tourist Destination — Templates & Trip Collection Update

## Context

Read the following before starting:
- Root `CLAUDE.md`
- `public_html/themes/custom/surface/CLAUDE.md`
- `public_html/themes/custom/surface/templates/TEMPLATES.md`
- `public_html/themes/custom/surface/templates/content/node--tourist-trip.html.twig`
- `public_html/themes/custom/surface/templates/content/node--article.html.twig`
- `public_html/themes/custom/surface/source/patterns/collections/trip/trip.twig`
- `public_html/themes/custom/surface/source/patterns/collections/trip/trip.css`
- `config/sync/field.field.node.article.schema_destination.yml`
- `config/sync/field.field.node.tourist_destination.schema_geo.yml`

---

## What now exists in Drupal (manual UI work already done)

### `node:tourist_destination` content type

Machine name: `tourist_destination`
Schema.org type: `TouristDestination`

Fields:

| Machine name | Type | Schema.org | Notes |
|---|---|---|---|
| `title` | string | `name` | |
| `body` | text_with_summary | `description` | |
| `schema_image` | entity_ref → media:ImageObject | `image` | |
| `schema_geo` | Geofield | `geo` | lat/lon — replaces geo_entity:destination |
| `schema_address` | Address | `address` | |
| `schema_contained_in_place` | entity_ref → node:place | `containedInPlace` | e.g. Ireland Place node |
| `schema_tourist_type` | text plain, unlimited | `touristType` | e.g. "Adventure Tourism" |

URL pattern: `/destinations/[node:title]`

### `schema_destination` on article — updated

Field now targets `node:tourist_destination` instead of `geo_entity:destination`.
Schema.org mapping: `contentLocation` (confirmed in Blueprints).
Cardinality: typically 1–2 per article (the destination(s) where the day
was based — not the POIs visited, which are `schema_poi` → `mentions`).

### geo_entity:destination — retired

The `geo_entity:destination` bundle is no longer used. Existing records
from KMZ imports will be recreated manually as `tourist_destination` nodes.
Do not reference geo_entity:destination in any new code.

---

## Step 1 — Verify config

Show (do not modify) the following config files:
- `config/sync/field.field.node.article.schema_destination.yml`
- `config/sync/field.field.node.tourist_destination.schema_geo.yml`

Confirm:
- `schema_destination` on article targets `node` entity type,
  bundle `tourist_destination`
- `schema_geo` exists on `tourist_destination`

If either file is missing or incorrect, stop and report — do not proceed.

---

## Step 2 — node--tourist-destination.html.twig

Create `public_html/themes/custom/surface/templates/content/node--tourist-destination.html.twig`.

This is a standalone destination hub page. Keep it simple — it will be
iterated once real content exists.

### Field traversal rules (from TEMPLATES.md)

```twig
{# Hero image — two entity hops #}
{% set img_media = node.schema_image.entity %}
{% set image_url = img_media
  ? img_media.field_media_image.entity.fileuri|file_url
  : null %}

{# Geofield on node — use .value for node's own geofield #}
{% set lat = node.schema_geo.lat.value %}
{% set lon = node.schema_geo.lon.value %}
{% set map_center = lat ~ ',' ~ lon %}

{# Map marker for this destination #}
{% set markers = [{
  'lat':   lat,
  'lon':   lon,
  'type':  'destination',
  'label': '<strong>' ~ node.label ~ '</strong>',
}] %}

{# Parent place #}
{% set parent = node.schema_contained_in_place.entity %}

{# Tourist types — loop plain text field #}
{% set tourist_types = [] %}
{% for item in node.schema_tourist_type %}
  {% set tourist_types = tourist_types|merge([item.value]) %}
{% endfor %}
```

### Template sections

1. **Header block** — label ("Destination"), title, address region,
   parent place name if set, tourist type tags if set

2. **Hero image** — full width, from `schema_image`

3. **Map** — use `@components/map/map.twig`
   - `map_id`: `'destination-map-' ~ node.id`
   - `center`: lat ~ ',' ~ lon
   - `zoom`: 11
   - `interactive`: 'true'
   - `markers`: single destination marker
   - `tiles`: 'open-topo'

4. **Body** — `content.body|field_value|render`

5. **Articles placeholder** — HTML comment noting a View will list
   articles where `schema_destination` references this node:
   ```twig
   {# TODO: Views block — articles referencing this destination #}
   {# View: schema_destination = node.id, sort by date desc #}
   ```

Use bare HTML with Surface BEM classes (`destination__header`,
`destination__map`, `destination__body` etc.) — no collection include
needed at this stage. Include `{{ attach_library('surface/destination') }}`
— the library does not need to exist yet, Drupal will silently skip it.

Show proposed template before writing.

---

## Step 3 — Update node--tourist-trip.html.twig

### What changes

The itinerary loop builds `map_markers[]` from `article.schema_destination`.
This previously traversed `geo_entity:destination` — it now traverses
`node:tourist_destination`. The field accessors are identical (`dest.label`,
`dest.schema_geo.lat`, `dest.schema_geo.lon`) but the entity type differs.

Additionally, build a `dest_nodes[]` array of unique destination nodes
for the cards section. Deduplicate by node ID to avoid showing the same
destination twice if multiple articles reference it.

### New loop logic

```twig
{% set dest_seen = [] %}

{% for dest_item in article.schema_destination %}
  {% set dest = dest_item.entity %}
  {% if dest and dest.id not in dest_seen %}
    {% set dest_seen = dest_seen|merge([dest.id]) %}

    {# Geofield on node — .lat/.lon direct floats on referenced nodes #}
    {% set dest_geo = dest.schema_geo %}

    {# Map marker #}
    {% if dest_geo and dest_geo.lat %}
      {% set map_markers = map_markers|merge([{
        'lat':   dest_geo.lat,
        'lon':   dest_geo.lon,
        'type':  'destination',
        'label': '<strong>' ~ dest.label ~ '</strong>',
      }]) %}
    {% endif %}

    {# Destination card data #}
    {% set dest_img = dest.schema_image.entity %}
    {% set dest_nodes = dest_nodes|merge([{
      'name':      dest.label,
      'url':       dest.url,
      'lat':       dest_geo ? dest_geo.lat : null,
      'lon':       dest_geo ? dest_geo.lon : null,
      'region':    dest.schema_address.administrative_area is defined
                   ? dest.schema_address.administrative_area : '',
      'image_url': dest_img
                   ? dest_img.field_media_image.entity.fileuri|file_url
                   : null,
      'summary':   dest.body.summary ?: dest.body.value|striptags|slice(0, 160),
    }]) %}

  {% endif %}
{% endfor %}
```

Initialise `{% set dest_nodes = [] %}` and `{% set dest_seen = [] %}` at
the top of the loop block alongside the other set statements.

Update the `trip.twig` include to pass `dest_nodes`:

```twig
{% include '@collections/trip/trip.twig' with {
  ...existing params...,
  'dest_nodes': dest_nodes,
} only %}
```

Show the current file in full, then the proposed diff, then write.

---

## Step 4 — Update trip.twig and trip.css

### trip.twig — replace cards section

The current cards section iterates `destinations` (article-title entries,
no URLs, no images). Replace entirely with a `dest_nodes` loop.

Replace this block:

```twig
{# ── Destination cards ── #}
{% if destinations %}
  ...
{% endif %}
```

With:

```twig
{# ── Destination cards ── #}
{% if dest_nodes %}
  <div class="trip__cards-section">
    <div class="trip__cards-header">
      <div class="trip__cards-title">Destinations</div>
      <div class="trip__cards-count">{{ dest_nodes|length }}
        {{ dest_nodes|length == 1 ? 'place' : 'places' }}</div>
    </div>
    <div class="trip__cards-grid">
      {% for dest in dest_nodes %}
        <a href="{{ dest.url }}" class="trip__dest-card">
          {% if dest.lat and dest.lon %}
            <div class="trip__card-map"
                 data-lat="{{ dest.lat }}"
                 data-lon="{{ dest.lon }}"
                 data-name="{{ dest.name }}"
                 data-num="{{ loop.index }}">
            </div>
          {% elseif dest.image_url %}
            <div class="trip__card-img">
              <img src="{{ dest.image_url }}" alt="{{ dest.name }}" loading="lazy">
            </div>
          {% else %}
            <div class="trip__card-map-placeholder"></div>
          {% endif %}
          <div class="trip__card-body">
            <div class="trip__card-num">{{ '%02d'|format(loop.index) }}</div>
            <div class="trip__card-name">{{ dest.name }}</div>
            {% if dest.region %}
              <div class="trip__card-region">{{ dest.region }}</div>
            {% endif %}
            {% if dest.summary %}
              <div class="trip__card-desc">{{ dest.summary }}</div>
            {% endif %}
          </div>
          <span class="trip__card-arrow" aria-hidden="true">→</span>
        </a>
      {% endfor %}
    </div>
  </div>
{% endif %}
```

### trip.css — add card desc style

Add after `.trip__card-region`:

```css
.trip__card-desc {
  font-family: var(--font-serif-nek);
  font-size: 13px;
  line-height: 1.5;
  color: var(--muted);
  margin-top: 8px;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.trip__card-img {
  height: 160px;
  overflow: hidden;
  border-bottom: 1px solid var(--border);
}

.trip__card-img img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
```

Show current cards section, then write both files.

---

## Step 5 — Update TEMPLATES.md

Add to the content templates section:

```markdown
### `node--tourist-destination.html.twig` ✅

Destination hub page for `tourist_destination` nodes (Schema.org: TouristDestination).

**Field traversals:**
- Hero image: `node.schema_image.entity.field_media_image.entity.fileuri|file_url`
- Geofield: `node.schema_geo.lat.value` / `node.schema_geo.lon.value` (node's own field — use .value)
- Parent place: `node.schema_contained_in_place.entity.label`
- Tourist types: loop `node.schema_tourist_type` → `item.value`
- Body: `content.body|field_value|render`
```

Update `node--tourist-trip.html.twig` traversal notes:
- `schema_destination` now references `node:tourist_destination`
- `dest.schema_geo.lat` / `.lon` — direct floats on referenced node (no `.value`)
- `dest.url` — available now that destination is a node
- `dest_nodes[]` passed to `trip.twig` for destination cards

Add note: `geo_entity:destination` retired — do not use.

---

## Step 6 — seanmontague_schemadotorg JSON-LD hook

Add `TouristDestination` handling to the existing
`hook_schemadotorg_jsonld_alter` in
`public_html/modules/custom/seanmontague_schemadotorg/seanmontague_schemadotorg.module`.

Show the current hook implementation before modifying. Add after the
existing bundle checks:

```php
// TouristDestination — touristType + containedInPlace.
if ($entity->bundle() === 'tourist_destination') {
  // touristType — plain text field, unlimited cardinality.
  $tourist_types = [];
  foreach ($entity->schema_tourist_type as $item) {
    if ($item->value) {
      $tourist_types[] = $item->value;
    }
  }
  if ($tourist_types) {
    $data['schemadotorg_jsonld_entity']['touristType'] = $tourist_types;
  }

  // containedInPlace — referenced Place node.
  $parent = $entity->schema_contained_in_place->entity;
  if ($parent) {
    $data['schemadotorg_jsonld_entity']['containedInPlace'] = [
      '@type' => 'Place',
      'name'  => $parent->label(),
    ];
  }
}
```

Show the full modified hook before writing.

---

## After all changes

```bash
lando drush cr
lando drush php-eval "\Drupal::service('twig')->invalidate();"
```

Confirm no errors on:
- `/destinations/dublin` (or any tourist_destination node)
- The Ireland 2024 tourist_trip node

Show all changed files before writing any of them.
Proceed step by step — verify Step 1 before continuing to Step 2.
