# AG-UI Travel Itinerary — Session Notes

**Date:** 2026-05-21
**Project:** seanmontague.com — exploratory feature
**Status:** Concept stage, near-term proof of concept

---

## The Concept

An AI-led travel intake conversation paired with human geographer
interpretation. The AI handles intake at scale; the human (Sean) handles
the craft.

**Positioning:** "AI-assisted interview, human-crafted itinerary."

The product isn't AI access — that's free everywhere. The product is
Sean's interpretation as a geographer. The AI gathers, the human
interprets, the user receives an itinerary built by someone who
actually understands place.

---

## Architecture

### Stage 1 — AI conversation (AG-UI)

Chat interface that progressively gathers travel preferences. Not a form
— a conversation that adapts based on previous answers.

Questions worth covering:

- Where they've been and loved (signals taste, not just destination)
- Where they've been and disliked (sharper signal)
- Pace — pack a day full vs. slow mornings
- What "vacation" means to them — recovery, adventure, learning, food
- Travel style — guidebook vs. wandering, planned vs. spontaneous
- Budget tier (not the first question)
- Dates / flexibility
- Group composition
- Anything they want to avoid

### Stage 2 — AI summary

At the end of the conversation, AI produces a structured brief — themes,
preferences, constraints, contradictions. Lands in Sean's inbox.

### Stage 3 — Human geographer

Sean reads the brief, interprets through geographic knowledge, crafts
the itinerary. The AI doesn't know Galway is the New Orleans of Irish
music or that the Conor Pass is better in fog. Sean does.

### Stage 4 — Delivery

Itinerary lands back in user's interface — map view, day-by-day, with
written justifications. Becomes a `TouristTrip` node in Drupal since the
content model already supports it.

---

## Phased Build Plan

### Phase 1 — Intake conversation (1-2 weeks)

- Single page route: `/plan-a-trip`
- Anthropic API for the conversation
- Conversation transcript + AI-generated summary emailed via Brevo
- No user accounts yet — email field at end for follow-up
- Output: structured brief in Sean's inbox

**Goal:** prove intake works and produces usable briefs.

### Phase 2 — Manual itinerary delivery (1-2 weeks)

- Sean crafts itineraries by hand
- Delivered as published `tourist_trip` node with referenced
  `tourist_destination` nodes
- Private URL sent to user (no login, obscure path)

**Goal:** validate the core question — do people pay for this?

### Phase 3 — User accounts + dashboard

- Drupal user registration
- Authenticated `/my-itineraries` route
- List of trips owned by user
- Full map view with destinations
- Reuses existing Surface theme map components

---

## Pricing

### Market reference points

| Tier | Examples | Price |
|---|---|---|
| Etsy / Fiverr | Generic itinerary write-ups | $50-150 |
| Travel advisors | Commission or consultation | $250-500 |
| Boutique trip designers | Wendy Perrin WOW List, Jaclyn Sienna India | $500-2000+ |
| AI-only | Layla, Mindtrip | Free or $10-30/mo |

### Recommended starting price

**$150 per itinerary** — flat fee, free intake conversation.

Logic:
- Low enough that someone planning a $4,000 trip thinks "obviously yes"
- High enough not to undercut the value
- Free conversation = low friction lead magnet
- Paid itinerary = the actual product

**Scaling:**
- 2 bookings/week = $1,200/month → hobby
- 10 bookings/week = $6,000/month → real side income

Bump to $250 if demand is strong. $400 + waitlist if overwhelming.

### Time-per-itinerary expectations

- First 5 itineraries: 4-6 hours each (over-delivery, refining process)
- Itinerary 15-20: drops as templates, destination research, saved POI
  library, reusable maps accumulate
- Effective hourly rate climbs with reuse

The margin isn't in time-per-itinerary. The margin is in **scale enabled
by AI intake**. Pure manual consulting can't do 10/week. AI intake can.

---

## Proof of Concept — Greece

Test case picked because:

- Rich enough to demand interpretation (mainland vs. islands, which
  islands, ferry vs. fly, season matters enormously)
- Sean doesn't have personal saturation the way he does with Ireland —
  will experience research process the way a client would
- Surfaces real planning questions:
  - Ferry logistics between islands (non-obvious to first-timers)
  - Athens timing (1 day vs. 3 days is a real question)
  - Cyclades vs. Dodecanese vs. Sporades vs. Ionian — different vibes
  - Crete is its own country, basically
  - Shoulder season (May, September) vs. peak (July-August)
  - Mainland off-the-beaten-path (Meteora, Peloponnese) vs. island-only

### Demonstrates the value

A generic AI says "visit Santorini and Mykonos."

A geographer says: "skip Mykonos unless you want clubs, go to Milos or
Folegandros instead. Santorini is worth one sunset but you'll be glad to
leave after two days because it's a Disneyland version of the Cyclades."

That's the differentiation.

### Workflow questions to answer on the self-test

1. How long does intake take running yourself through the conversation?
2. How much research time with **no** prior destination knowledge?
3. What format does the deliverable take?
   - `tourist_trip` node with referenced destinations
   - PDF
   - Private map URL
   - All three?
4. What's reusable for the next Greece booking?
   - Destination nodes
   - POI lists
   - Ferry route data
   - Templates

### Build approach

Build Greece 2026 as a real `tourist_trip` on seanmontague.com — 6-8
`tourist_destination` nodes, each with planning-map POI status field
(did / wished / next time, even hypothetically). Use it as both
portfolio piece and template for next Greece booking.

---

## Open Questions

- Pricing — start at $150, adjust based on demand
- Whether to stay on seanmontague.com forever or eventually split out
- Subscription vs. transactional model — start transactional
- What "viability" means — bookings per month? hours per booking?
- How the geographer's voice comes through in deliverables — written
  justifications? voice notes? ElevenLabs TTS via existing audio
  infrastructure?

---

## Next Steps When Resuming

1. **Decide:** start building Phase 1 intake conversation, or first
   self-test the Greece workflow to understand the deliverable shape?
2. **If intake first:** scaffold the chat interface — Anthropic API
   integration, conversation state, summary generation, Brevo email
   delivery
3. **If self-test first:** run yourself through the intake questions
   above, build a Greece TouristTrip node, time the research, decide
   format

---

## Related Existing Infrastructure

These already exist on seanmontague.com and reduce the build effort:

- `tourist_trip` content type (TouristTrip schema.org)
- `tourist_destination` content type (TouristDestination schema.org)
- `geo_entity:poi` for points of interest
- Leaflet mapping with USGS tiles + per-article tile override
- Brevo integration (domain authenticated, SPF/DKIM in place)
- AcuGIS email hosting
- Schema.org Blueprints — full structured data
- Surface theme — modified atomic design system
- Storybook — design system documentation
- Drupal user system — ready for authenticated dashboards
- Persistent audio player (planned) — for ElevenLabs voice notes
- Anthropic API access — for the AI intake conversation
