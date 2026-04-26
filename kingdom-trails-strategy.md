# Kingdom Trails — SEO & Content Strategy Blueprint

> Personal site, no advertising. Goal: rank #1 for Kingdom Trails informational queries by filling the content void with firsthand mapped data, curated ride guides, and Schema.org-structured Drupal architecture.

---

## The Opportunity

- **100+ miles** of trails across **7 distinct riding zones**
- **0** authoritative, structured, mapped guides exist online
- KT's own website is thin, unstructured, and generates no rich results
- Ranked among the **top 10 MTB trails in the USA** (Bike Magazine) — only Northeast system to make the list
- KTA divided the network into 7 zones in 2021 — **nobody has published zone-level content**

---

## Content Gap Analysis

### What's missing from every existing source

| Gap | Severity |
|-----|----------|
| No curated first-visit routing — every guide dumps a trail list, no one gives a decision-free first day | 🔴 Critical |
| KT's own website has no structured data, no trail-level pages, no JSON-LD | 🔴 Critical |
| Zero coverage of the 7 riding zones as distinct content entities | 🟡 High |
| No interactive map with narrative context (Trailforks has data, no story) | 🟡 High |
| Area guide content is scattered and dated (TripAdvisor, NewEngland.com) | 🟢 Medium |
| Seasonal/conditions content answered vaguely everywhere | 🟢 Medium |

### Structural advantages

- **Firsthand GPS data of the entire network** — unreplicable, signals E-E-A-T
- **Schema.org Blueprints in Drupal** — every trail becomes a machine-readable `SportsActivityLocation` with difficulty, distance, elevation, `hasPart` relationships, seasonal openings. No competitor has this.
- **No ads, no affiliates** — differentiates from every "guide" site; aligns with KTA's community-first culture

---

## Keyword Targets

### Tier 1 — High value, low competition

| Keyword | Est. Volume | Intent |
|---------|-------------|--------|
| kingdom trails beginner guide | ~500/mo | Informational |
| kingdom trails first time visitor | ~300/mo | Informational |
| kingdom trails best routes | ~400/mo | Informational |
| kingdom trails trail map interactive | ~200/mo | Navigational |
| east burke vermont mountain biking guide | ~350/mo | Informational |
| kingdom trails zones explained | Low vol, zero competition | Informational |

### Tier 2 — Trail-specific long tail

- `sidewinder trail kingdom trails`
- `black bear trail east haven vermont`
- `darling hill kingdom trails`
- `kitchel trail burke vt`
- `moose haven trails east haven vt`

### Tier 3 — Area + trip planning

- `east burke vt what to do besides biking`
- `lake willoughby vermont day trip from burke`
- `best time to ride kingdom trails conditions`
- `kingdom trails eMTB class 1 rules 2025`
- `northeast kingdom vermont mountain biking weekend itinerary`

---

## First-Visit Ride Guides

> These become individual Drupal nodes with `TouristTrip` + `ItemList` schema. Each trail segment gets its own `SportsActivityLocation` node with `hasPart` relationships back to the route.

### 🟢 The Soft Landing — Beginner Half Day

- **Distance:** ~8 miles | **Elevation:** ~600 ft | **Time:** 2.5–3 hrs
- **Start:** Welcome Center
- **Route:** White School → Burnham → Webs → Last Call
- **Why it works:** Flow and rhythm immediately, pastoral landscape crossing for the "wow" moment, nothing scary, ends at the village swim hole (behind East Burke Sports)

### 🔵 The Darling Hill Signature — Intermediate Day

- **Distance:** ~15 miles | **Elevation:** ~1,800 ft | **Time:** 3.5–4.5 hrs
- **Start:** Village
- **Route:** East Darling Hill Rd (pavement climb) → Kitchel → VAST → Herbs → village
- **Optional add:** Pisgah for exposed ridge views
- **Why it works:** Summit views of Burke Mountain as payoff; Kitchel is the fastest descent on the network (berms, whoops); this is the "classic KT experience" every local recommends

### 🔴 The East Haven Expedition — Advanced Full Day

- **Distance:** ~25+ miles | **Elevation:** ~3,200 ft | **Time:** 5–7 hrs
- **Start:** East Haven parking (shuttle recommended)
- **Route:** Black Bear (Trailforks #2 rated trail in the US) → Moose Haven network → Upper Victory Rd → Flower Brook → East Burke
- **Why it works:** Black Bear is the bucket-list objective — 800 ft descent with massive berms and rollers. Remote, earned, memorable. Brief riders: carry water, it's a long way from town.

### ⬜ Family / Kids First Day

- **Distance:** ~4 miles | **Elevation:** ~150 ft | **Time:** 1.5 hrs
- **Start:** Pumptrack / skills area
- **Route:** Pumptrack (20 min confidence building) → White School lower → Burnham meadow crossing → back via road → pumptrack again
- **Finish:** Ice cream at the Country Store
- **Why it works:** The meadow crossing produces the "I want to come back" reaction in kids; pumptrack bookends build confidence

---

## Area Guide — Unique POIs

### Iconic spots (no travel blog covers these properly)

**Darling Hill ridge at golden hour**
Sweeping views of Burke Mountain from open meadow. KT owns and manages 400+ acres here. Walk your bike to the crest. Best 5:30–7pm in summer. Publish GPS coords. KTA and VLT note this as an iconic community landmark.

**The fire ring**
Mid-network rest spot and community meeting point. Trailforks lists it as a POI. Document with `schema:Place` markup.

**Swim hole at East Burke Sports**
River behind the shop, post-ride, free and cold. Every returning visitor knows this; no visitor guide mentions it. Exactly the kind of firsthand detail that builds authority.

**Lake Willoughby**
30-min drive. Glacial lake, 320 ft deep, flanked by Mt Pisgah and Mt Hor. Half-day detour worth a `TouristAttraction` node with driving directions from East Burke.

### Food + culture

| Place | Schema Type | Notes |
|-------|-------------|-------|
| The tiki bar | `FoodEstablishment` | Seasonal, adjacent to trails, beloved. Appears in every TripAdvisor review, nowhere in structured content. |
| Northeast Kingdom Country Store | `Store` | Heart of East Burke village. Coffee, Vermont crafts, local intel, post-ride hangout. |
| East Burke Sports / Village Sports | `SportsActivityLocation` + `LocalBusiness` | Two shops — village level and Darling Hill. Rental, repair, trail beta. |
| Café Lotti | `CafeOrCoffeeShop` | Hosts KTA's monthly community chats. Signals community authenticity. |

### Seasonal conditions (completely open niche)

| Season | Key info |
|--------|----------|
| **Spring (May–Jun)** | Mud season is real. East Haven drains fastest (rocky, elevated). Darling Hill stays wet longest (clay-heavy). Write actual drainage guidance, not just "check trail status." |
| **Summer (Jul–Aug)** | Ideal. Morning rides before 10am avoid heat. Evening rides from 5pm catch golden hour on ridges. |
| **Fall (Sept–Oct)** | Peak season. Foliage by elevation — East Haven first (higher), village last. Book lodging 6+ weeks out. Zero rain = hero dirt. |
| **Winter** | 20 miles groomed fatbike, 12 miles nordic ski, 38 miles snowshoe. Class 1 eMTB allowed year-round as of 2025. |

---

## Drupal Content Architecture

### Content types → Schema.org types

| Drupal Content Type | Schema.org Type | Key Properties |
|--------------------|-----------------|----------------|
| Trail | `SportsActivityLocation` | distance, elevationGain, difficulty, surfaceType, seasonalOpenings, geo |
| Route / Ride Guide | `TouristTrip` | itinerary, hasPart (Trail nodes), duration, audience, description |
| Point of Interest | `TouristAttraction` | geo, image, description, nearbyAttractions, openingHours |
| Zone / Area | `Place` + `hasPart` | containsPlace (Trails), description, geo bounding box, image |
| Local Business | `LocalBusiness` | name, address, telephone, openingHours, geo |
| Article / Guide | `Article` + `HowTo` | Step-by-step rides → HowTo; editorial → Article |
| Season / Condition | `Event` + `openingHoursSpecification` | Seasonal trail openings, grooming updates |

> **The killer structural advantage:** The `hasPart` / `isPartOf` relationship between Zone → Trail → Segment gives Google a full, machine-readable hierarchy of the network. No competitor has this as structured data.

### Schema.org Blueprints sub-modules to enable

| Module | Purpose |
|--------|---------|
| `schemadotorg_jsonld` | Outputs JSON-LD in `<head>` — required for Google rich results. The SEO core. |
| `schemadotorg_mapping` | Maps Drupal fields to Schema.org properties (trail length → `schema:distance`, etc.) |
| `schemadotorg_report` | Admin dashboard for browsing all Schema.org types during content modeling |
| `schemadotorg_descriptions` | Auto-populates field descriptions from Schema.org definitions — speeds up modeling |
| `schemadotorg_subtype` | Allows `SportsActivityLocation` to subtype as `MountainBikeTrail` |

---

## Technical Stack

| Layer | Tool | Role |
|-------|------|------|
| CMS | Drupal + Schema.org Blueprints | Content architecture backbone; JSON:API; JSON-LD output |
| Maps | Leaflet.js (via Drupal Leaflet module) | Interactive maps with custom GPX overlays, zone-colored trail layers, POI markers, elevation profiles |
| Geodata | GPX / GeoJSON from personal rides | Strava/GPS exports → Drupal GeoField → Leaflet render; enables distance/elevation auto-calc |
| Content pipeline | Claude (AI) + personal knowledge | Draft trail descriptions from GPX + notes; personal expertise ensures accuracy; scales content production |
| Templates | Twig with JSON-LD injection | Custom trail cards, route summaries, zone pages; `schemadotorg_jsonld` or hand-crafted JSON-LD in dedicated Twig partials |
| Philosophy | Permaculture zone design | Zone 1 (highest access) = ride guides + map → Zone 2 = trail profiles → Zone 3 = area guide → Zone 4 = conditions + history. Build from center outward. |

---

## E-E-A-T Signals to Build In

**Experience**
State "I rode every trail in this network" on the About page and in author bio on every article. Back it up with embedded GPS traces from your own rides.

**Expertise**
Trail-level detail no blogger has: soil drainage by zone, best time of day per trail, exact trail junctions, elevation profiles from personal data, landowner etiquette context.

**Authoritativeness**
Link to KTA, Trailforks, IMBA. Earn links back from local businesses featured on the site. Participate in KTA community chats and reference them. Cite the Vermont Land Trust partnership and the 102 private landowners.

**Trust**
No ads, no affiliate links, no sponsored content. State this explicitly. It differentiates from every other "guide" site and directly mirrors KTA's community-first, landowner-respecting culture.

---

## Build Sequence

1. **Ride guide content types first** — high-value, immediately useful, validates the Schema.org modeling before building 100+ trail nodes. A `TouristTrip` node with 4–5 `SportsActivityLocation` `hasPart` relationships, a Leaflet map with GPX, and a narrative targets "kingdom trails beginner guide" immediately.

2. **7 zone pages** as `Place` + `containsPlace` (trails) — target mid-volume zone-specific queries and become the navigation backbone of the site.

3. **Trail-level nodes** — seeded from personal ride data, AI-assisted descriptions fact-checked against firsthand experience. ~100 nodes, each a long-tail keyword target.

4. **Area guide + POI nodes** — `TouristAttraction`, `LocalBusiness`, `FoodEstablishment`. Builds area authority and captures trip-planning queries.

5. **Conditions / seasonal content** — evergreen but needs regular updates. Build as `Article` nodes that reference Trail and Zone nodes via entity reference.

---

## Notes

- eMTBs (Class 1 pedal-assist) allowed on ~85% of trails as of June 2025 — new query surface
- Black Bear trail in East Haven rated #2 in the US on Trailforks (2023) — anchor content opportunity
- KTA has 102 private landowners — the stewardship story is a differentiator worth telling deeply
- KTA economic impact: $10M+ in direct spending from out-of-state visitors — useful context for area guide framing
- Vermont outdoor recreation = $2.1B economy, 2nd highest % of GDP in the nation
