# Trail Guides frameworks

## 1. Workflow

1. **Interview** (input: a topic). 3 to 5 questions per turn, most important first. Stop when there's enough for an outline.
2. **Outline** (input: answers, notes, transcript, or voice memo). Use the matching page structure in section 3.
3. **Sean drafts.**
4. **Refine** (input: Sean's draft). Revised draft, a short list of substantive changes and why, then the extras in section 4.

Claude doesn't write a full draft unless Sean asks.

## 2. Interview question bank

Pick what fits the page; ask the highest-value questions first.

- Who is this for, and who should skip it?
- Where do you start and park, and why there?
- What's the moment that makes this route worth doing?
- Where do newcomers go wrong: missed junctions, wrong direction, wrong time of day?
- What's the hardest part, and how should the target reader handle it?
- Where's the bail option, and when should someone take it?
- When is it at its best, and when should people avoid it (season, drainage, time of day)?
- Water, food, and what's after the ride or hike?
- What would you tell a friend the night before?
- Which TrailMapper route ID covers it? (ask only if not supplied)

## 3. Page structures

All pages: target reader in the first two sentences; numbers only from TrailMapper; status items carry a source and an as-of date.

### Ride guide (canonical example: Darling Hill Signature)
1. Ability badge and title
2. Tagline, one sentence
3. Stats row: TrailMapper distance, climb, estimated time; start point; best season
4. Intro: target reader, why this route, the payoff
5. Route sequence table: segment, trail type, TrailMapper trail ID, notes (no hand-entered numbers)
6. Narrative by phase (for example: the climb, the ridge, the descent, back to town), each with one or two callouts (tip or warning)
7. Optional extension, labeled, with TrailMapper stats
8. Practical info: parking, pass (as-of), best conditions, bail option, water
9. Finish: where it ends and what to do there

### Hike guide (White Mountains; proposed, revise after the first one)
1. Title and target reader (day hiker or backpacker, and experience level)
2. Tagline
3. Stats row: TrailMapper distance, climb, estimated time; trailhead; best season
4. Intro: why this hike, and what's different about the Whites for this reader
5. Route sequence table: segment, TrailMapper trail ID, notes
6. Narrative by phase, with callouts
7. Turnaround and bail points
8. Overnight logistics (backpacking only), each item verified with an as-of date
9. Practical info: trailhead parking, water, conditions, closures (as-of)

### Zone page
1. What the zone is and who it suits
2. Character of the riding: soil, drainage, terrain
3. Where to start
4. Recommended trails by level, linked to trail pages
5. Conditions notes (as-of)

### Trail page
1. Trail name, difficulty, zone
2. Target reader and fit
3. What it rides like, in order
4. Connections: what to ride before and after
5. TrailMapper stats, per direction

### Area guide or point-of-interest page
1. What it is and who it's for
2. Why go: the firsthand reason
3. When to go: season, time of day
4. How to get there from the trail hub
5. Practical info: hours, cost (as-of)
6. Pair it with a nearby ride, hike, or stop

### Seasonal and conditions page
1. As-of date at the top
2. Season by season: what changes and what to ride
3. Drainage guidance by zone, from firsthand observation
4. What to check before you go, with sources

## 4. Extras

The outline returns items 3 and 7. A refinement returns all ten, in this order.

| # | Extra | Default |
|---|---|---|
| 1 | Title tag and meta description | 3 titles (about 60 characters max), 3 meta descriptions (about 155 characters max) |
| 2 | H1 and headline variants | 3 |
| 3 | Keyword and intent note | Primary keyword from the content plan, intent type, one sentence on what the searcher needs |
| 4 | FAQ questions and answers | 3 to 5, answered only from Sean's draft; unanswered questions listed as gaps |
| 5 | Internal links | 3 to 6, each with anchor text and target page |
| 6 | Who this is for and not for | 2 to 3 bullets each: ability level, bike or gear, season; no numbers |
| 7 | TrailMapper data checklist | Route ID, ordered trail IDs, stats needed, status check for the area |
| 8 | Fact-check list | Each claim, where to verify it, as-of date needed |
| 9 | Photo alt text and captions | One per photo supplied; alt text about 125 characters max |
| 10 | Card and Schema summary | 1 to 2 sentences, about 160 characters max, plain text for the SeanMontague.com project to map |

## 5. TrailMapper citation

- Numbers come from the TrailMapper planner: routes from `GET /routes/{id}`, trails and segments from `GET /segments/{id}`, closures from `GET /status/{area}` (see trailmapper-interface.md).
- A number not yet supplied is a placeholder: `[TM: route <id>, distance]`, `[TM: trail <id>, descent]`.
- Stats never appear as typed numbers in guide copy. The site stores meters and converts units at display time (docs/architecture/decisions.md, metric storage), so a typed number can't follow the reader's unit choice. Every stat in copy is a [TM: ...] token for the template to render. The final token syntax belongs to the SeanMontague.com build; until it's set, keep the placeholder format.
  - Never round or restate a TrailMapper figure in prose ("about two hours").
- Generated routes get no guide until Sean has done the route and written it up.

## 6. Fact-check and status

- Status items need a source and an as-of date: passes and prices, closures, eMTB rules, hours, parking, conditions.
- Rankings, awards, and acreage or landowner figures need a named source and year, or they come out.
- Anything unconfirmed is marked [VERIFY]. Never fill a gap with a plausible guess.

## 7. Handoff to SeanMontague.com

When a page needs a field, content type, or markup change, add a "For the SeanMontague.com project" note at the end of the response: what's needed and why, no code.
