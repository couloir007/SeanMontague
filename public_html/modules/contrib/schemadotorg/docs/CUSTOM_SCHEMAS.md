# Custom and external schemas

Schema.org Blueprints can import a complete replacement CSV source using the
**Schema.org data file/URL** setting at
`/admin/config/schemadotorg/settings/general`. The configured path or URL must
contain the required `[TABLE]` token, which is replaced with `types` and
`properties` during import.

For incremental changes to the bundled Schema.org data, implement
`hook_schemadotorg_schema_data_alter()` in a custom module. The hook receives
each data table as records keyed by the canonical URI from its `id` column.

- Add a type or property by assigning a new ID-keyed record.
- Replace a bundled record by assigning an existing ID key.
- Remove a record using `unset($records['https://schema.org/Example'])`.

Each added or replaced record must include an `id` value that exactly matches
its array key. Values for omitted CSV columns are imported as empty strings.
When adding a custom subtype, set `sub_type_of` to its parent type URI. Add
custom properties during the `properties` import and include their URIs in the
custom type's `properties` value.

The importer does not repair or reject references to removed types or
properties. Review dependent type, property, and mapping data when removing a
record. Keep custom alterations in version control. Use an external CSV source
when replacing the complete schema dataset is more appropriate.
