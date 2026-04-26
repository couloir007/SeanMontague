# Sean Montague

## Who I am

Web developer at the Smithsonian National Museum of Natural History.
Based in Burke, Vermont. This is the town, the mountain, and the trail
network — not incidental geography.

The personal site (seanmontague.com) is the outlet. Not a portfolio,
not available for hire. A place to put the things that matter:
Kingdom Trails rides, Burke Mountain ski days, the food forest, maps
of places I've been and things I wanted to see.

---

## How I work

Thorough before fast. The content model gets thought through before
the template gets written. Schema.org properties get looked up, not
guessed. A field named `schema_place` means something specific and
consistent across the whole codebase — naming collisions are a form
of technical debt.

Documentation is part of the work, not a separate step. CLAUDE.md,
TEMPLATES.md, TOURIST-DESTINATION.md — if a decision was worth making,
it's worth recording so it doesn't have to be made again.

The planning is visible in the output. The Ireland trip map shows not
just what we did but what we wanted to do and ran out of time for.
That's useful to someone else. That's the point.

---

## Technical stack

**Primary:** Drupal 10/11, PHP 8.3, PostgreSQL 15 + PostGIS  
**Theme:** Custom Surface theme — Modified Atomic Design, Vite, Twig, BEM  
**Mapping:** Leaflet.js, USGS National Map tiles, GeoJSON, KMZ/KML parsing  
**Frontend:** Storybook, ES6+, CSS custom properties  
**Infrastructure:** Lando (local), Pantheon (hosting), Git  
**IDE:** PhpStorm + Claude Code  

---

## The site

**Kingdom Trails** — mountain biking. Northeast Kingdom, Vermont. One of
the best trail networks in the country. First-timer guides, trail reports,
seasonal conditions. The kind of content that would have helped me when
I started.

**Burke Mountain** — skiing and telemark. Home mountain. The resort and
the backcountry around it.

**Permaculture / food forest** — long-term land work. Slower content cycle
than the trail reports but part of the same sensibility: working with
systems, observing before acting, documenting what works.

**Travel** — Ireland April 2024. Nine days, eight destinations, a lot of
stone walls and Atlantic light. Galway is the New Orleans of Irish music
and we only had two days. That's a reason to go back.

**Maps** — everything gets mapped. POIs, routes, destinations, places
I wanted to see and didn't get to. The map is a planning artifact as
much as a record.

---

## Content model philosophy

Places are hubs. Destinations are where you sleep and base from.
POIs are what the day is actually about. A trail report is a
`BlogPosting` with stats. A trip is a `TouristTrip` with an itinerary
of day articles, each anchored to a `TouristDestination`.

Schema.org is worth doing correctly. `contentLocation` for the destination
an article is about. `mentions` for the POIs visited. `containedInPlace`
for Galway within Ireland. `touristType` as plain text because "Traditional
Music" is more honest than a taxonomy term.

The structured data serves two purposes: Google's knowledge graph, and
future-Sean who needs to query across content types.

---

## Sensibility

The site is personal and useful — not a travel brochure, not a gear review
aggregator. The voice is first-person and specific. "Galway is the New
Orleans of Irish music, and we had two days" is the right kind of sentence.
So is "the Aran Islands day was one of those travel days that earns its
place in the permanent record."

The map showing what we wished we'd seen is more valuable than the map
showing only what we did. Honesty about trade-offs is part of what makes
a resource worth reading.

---

## Current work

- `seanmontague.com` — Drupal 10, Surface theme
- `tourist_destination` content type — TouristDestination Schema.org type,
  replacing geo_entity:destination, destination hub pages with editorial
  lede, planning map with POI visit status (did / wished / next time)
- Ireland 2024 trip — first major TouristTrip content, KMZ import, 8
  destination nodes to create
- Persistent audio player — Turbo Drive + `data-turbo-permanent`, ElevenLabs
  integration via AudioObject media type
- Kingdom Trails first-timer guide — first trail article with full POI map
