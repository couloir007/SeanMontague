# Documentation index

## Ownership

| Area | Owner |
|---|---|
| Content model, Schema.org mappings, templates, code | This repo |
| Trail geometry, distances, climb, generated routes | TrailMapper project |
| Editorial voice, audience, page structures, content plan | `docs/trail-guides/` (master), copied to the Trail Guides Claude project |

## architecture/

| File | What it covers |
|---|---|
| `architecture/content-model.md` | Content-model philosophy and the planned Kingdom Trails types (ride guide, trail, zone, POI) with build sequence. |
| `architecture/schema-mappings.md` | Schema.org Blueprints field model, shared-storage rule, JSON-LD notes, planned sub-modules. |
| `architecture/tourist-destination.md` | The `tourist_destination` node type: fields, JSON-LD, planning map, template. |
| `architecture/decisions.md` | Settled decisions and their reasons (templates, map services, metric storage, git, builds). |

## integrations/

| File | What it covers |
|---|---|
| `integrations/trailmapper-interface.md` | What the Drupal site needs from TrailMapper: ownership, proposed fields, planner API, rules. |

## reference/

| File | What it covers |
|---|---|
| `reference/darling-hill-technical-spec.md` | Historical ride-guide worked example: proposed fields, entity relationships, JSON-LD, Twig sketch. |
| `reference/node-refactor-playbook.md` | Recipe for moving a node template to pure markup plus a preprocess hook and services. |
| `reference/asset-group-taxonomy-proposal.md` | Proposed backend-only taxonomy for filtering media and geo entities by trip. Not built. |
| `reference/persistent-audio-player-spec.md` | Spec for a persistent audio player (Turbo, `data-turbo-permanent`). Not built. |
| `reference/ag-ui-travel-itinerary.md` | Concept notes for an AI-intake, human-crafted itinerary feature. |

## trail-guides/

| File | What it covers |
|---|---|
| `trail-guides/trail-guides-audience.md` | Target readers for the Trail Guides. |
| `trail-guides/trail-guides-content-plan.md` | Editorial positioning and content pillars. |
| `trail-guides/trail-guides-few-shot.md` | Example library showing page structure and voice. |
| `trail-guides/trail-guides-frameworks.md` | Workflow, interview bank, page structures, TrailMapper citation rules. |
| `trail-guides/trail-guides-voice-guide.md` | Tone and copy rules for Kingdom Trails and White Mountains pages. |
| `trail-guides/trail-journal-guidelines.md` | Voice and formatting rules for trail reports and adventure writing. |
| `trail-guides/voice-and-sensibility.md` | Who Sean is and the site's sensibility. |

## _to_delete/

Superseded or merged originals, held for manual removal: `HERO-MAP-FIX.md`, `SEAN.md`, `SESSION-CONTEXT-2026-04-24.md`, `TOURIST-DESTINATION.md`, `kingdom-trails-strategy.md`, `source-REFACTOR-NOTES.md`, `surface-source-CLAUDE.md`.
