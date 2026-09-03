# How to migrate data from one or more existing Drupal fields
**Custom Field** is often positioned as a lighter, more performant alternative to
[Paragraphs](https://www.drupal.org/project/paragraphs){target="_blank"} and entity reference setups. That pitch is easy to accept for a brand-new
field, but much harder to accept for a site that already has years of content living
in those structures. Understandably, teams are reluctant to adopt a new data model
without a clear, low-risk way to move their existing data into it.

## Why this migration is harder than it looks
Copying data from one or more existing fields into a Custom Field isn't a simple
column rename. A few things make it genuinely tricky to hand-roll:

- **Reshaping multiple source fields into one target.** A Custom Field's subfields
  often replace what used to be several separate fields (or a Paragraph's fields), so
  values from more than one source need to be combined into a single set of subfield
  columns, delta by delta.
- **Resolving reference fields.** Paragraphs and entity reference fields don't hold
  plain values — they point at other entities. Migrating them means pulling the
  referenced entity's own field values across, not just copying an ID.
- **Doing it at scale, safely.** Sites with thousands of nodes need this run as a
  batch process rather than a single request, and need it to be repeatable and
  deployable — not a one-off script run by hand on production.
- **Keeping translations intact.** On multilingual sites, the migration needs to
  respect per-language values rather than only migrating the default language.

Handling all of this correctly, in a `hook_update_N()`, from scratch, for every field
you need to migrate, adds up to real engineering effort — effort that becomes a
blocker to adopting **Custom Field** on an existing site.

## Field Updater Service
[Field Updater Service](https://www.drupal.org/project/field_updater_service){target="_blank"} is a
companion module built specifically to remove that blocker. Rather than writing custom
migration code, you define a **field mapping** as a configuration entity — entity
type, bundle, target field, and the source field(s) to copy from — and the module's
batch service does the copying for you.

**Key capabilities:**

- Mappings are defined as configuration entities, so they're exportable and
  deployable like any other Drupal config.
- Can map to virtually any target field type, from multiple source fields on the same
  entity.
- Supports `entity_reference` and `entity_reference_revisions` fields (including
  Paragraphs) as a mapping source, resolving the referenced entity's values.
- Supports custom default values and explicit `NULL` for unmapped or missing data.
- Multilingual-aware.

## Quick start
1. Install the [Field Updater Service](https://www.drupal.org/project/field_updater_service){target="_blank"} module.
2. Add a new field updater at `/admin/config/field-updater`.
3. Select the **Entity type**, **Bundle**, and **Target field** (your Custom Field).
4. If copying from a Paragraphs or entity reference field, select **Entity reference**
   as the field mapping source.
5. Save, then add the individual mappings from your source field(s) to the Custom
   Field's subfields.
6. Run `drush field-updater:update`, choose the field updater you just created, and
   confirm.
7. Copy the example update hook the Drush command generates into your own module's
   `.install` file, so the same migration can be deployed to other environments.

You can also list all configured field updaters at any time with
`drush field-updater:list`.

## Next steps
See the [Field Updater Service project page](https://www.drupal.org/project/field_updater_service){target="_blank"}
for installation instructions, the full list of features, and further examples.