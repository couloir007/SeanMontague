# Field configuration
A Custom Field is a "field of fields" — one Drupal field whose value is made up of any
number of independently typed [subfields](../plugins/type/index.md) (a `string`, an `entity_reference`, a
`datetime`, and so on, all stored together). Storage and field settings share a single
field edit form, but the settings on that form still fall into two categories:

- **[Field storage settings](#field-storage-settings)** — shared across every bundle the
  field is used on. This is where subfields are added, removed, and given their
  underlying data type.
- **[Field settings](#field-settings)** — configured per bundle. This is where each
  subfield's own widget-facing settings live.

## Adding a subfield
1. On the field's edit form, in the [field storage settings](#field-storage-settings)
   section, click **Add sub-field**.
2. Enter a **Machine name** for the new subfield and choose its **Type**. Configure any
   additional storage settings that appear for the selected type (length, precision,
   target type, and so on).
3. This triggers an AJAX rebuild of the form, which adds a matching entry for the new
   subfield down in the [field settings](#field-settings) section further down the same
   form.
4. In that new entry, configure the subfield's field settings — its widget-related
   options and the **Check empty** toggle, if applicable.
5. Repeat for any additional subfields, then save the form to persist the storage schema
   change and the field settings together.

Because storage settings are shared across every bundle the field is used on, adding a
subfield here makes it available everywhere the field appears — but its field settings
(step 4) only apply to the bundle you're currently editing.

## Field storage settings
Because each subfield becomes a real database column, this section behaves like defining
a small table, and is subject to the same one-way constraints Drupal applies to field
storage.

Each subfield is listed as its own **Custom field item** with:

- **Machine name** — a unique, sanitized name for the subfield. This becomes its column
  name in the database.
- **Type** — the subfield's data type, chosen from Custom Field's supported types (see
  an overview of available [subfield types](../plugins/type/index.md)).
- Additional settings that appear only for certain types — for example, **length** for a
  `string`, or **precision** and **scale** for a `decimal`.

Use **Add sub-field** to add more subfields, and the **Remove** button on a subfield to
delete it (hidden while only one subfield remains, since a Custom Field always needs at
least one).

### Cloning settings from another field
If you're creating a brand-new field, and other Custom Fields already exist on the site,
a **Clone settings from** option lets you copy an existing field's full subfield
configuration — both storage and field settings — instead of rebuilding it manually.

### Modifying subfields after data exists
**Once the field contains data, its subfield structure is locked.** You can no longer
add, remove, rename, retype, or change any type-specific storage setting for an existing
subfield — the same restriction Drupal applies to any field storage schema change after
data exists. Adding or removing columns from a table that already has data is normally a
challenging, error-prone task, so Custom Field provides an updater service that handles
it for you programmatically. See
[Add/remove columns using the updater service](../documentation/updater-service.md).

## Field settings
These settings are still scoped per bundle even though they now live on the same form as
the storage settings above — if the field is used on more than one bundle, each bundle keeps
its own copy.

- **Add another button label** — only editable when the field allows multiple values;
  sets the label for the "Add another item" button on the entity edit form.
- Each subfield gets its own collapsible section (titled with its machine name)
  containing that subfield's own field settings — mainly widget-related options specific
  to its data type. These are covered on each subfield type's own documentation page
  rather than here, since they vary by type.

    > [!note]
    > **UUID** subfields have no configurable field settings and are omitted from this
    > list entirely.

- A **Required** toggle marks the subfield as required. It stays disabled until the
  field itself is marked as required.
- When the field itself is translatable, each subfield gets a **Users may translate
  this field** toggle controlling whether it can be translated independently or
  falls back to the default language value. See [Multilingual](multilingual.md) for full setup steps.
- Most subfields include a **Discard other values if this field is empty** toggle, which controls whether that subfield
  counts toward the Custom Field as a whole being considered "empty". This
  toggle is hidden and forced off for subfield types such as `boolean` that opt
  out of empty-checking entirely, since emptiness isn't meaningful for them.

## Next steps
With your subfields configured, next set up the widgets used to edit their values and
the formatters used to display them:

- [Field widgets](../field-widgets/index.md)
- [Field formatters](../field-formatters/index.md)