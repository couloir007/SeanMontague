# GraphQL Compose integration
The **GraphQL Compose: Custom Field** module exposes Custom Field subfields to GraphQL
via the [GraphQL Compose](https://www.drupal.org/project/graphql_compose){:target="_blank"}
module. A Custom Field's subfields aren't standalone Drupal fields — they're columns
packed into a single field — so GraphQL Compose's own built-in field and schema type
plugins have no way to resolve or type them on their own. This submodule fills that gap
with its own set of plugins covering every subfield data type, giving you per-subfield
naming and enablement without any manual schema work.

## Configuration
1. Enable the `custom_field_graphql` submodule.
2. (Recommended) Enable the `graphql_compose_edges` module.
3. Navigate to the **GraphQL Compose Schema** `/admin/config/graphql_compose` configuration page.
4. Enable the entity types and fields you want to expose.
5. Enable/disable the subfields you want to expose or hide.
6. (Optional) Set an alternative **Schema field name** for the exposed subfield.
7. Save the configuration.
8. Navigate to the **Explorer** page `/admin/config/graphql/servers/manage/graphql_compose_server/explorer`
   to test queries with the exposed fields.

## GraphQL Compose plugins
There are two plugin types at work: **FieldType** plugins resolve each subfield's stored
value into the shape GraphQL Compose expects, while **SchemaType** plugins define the
GraphQL object types those resolved values are returned as.

### FieldType plugins
Each plugin maps a Custom Field subfield type to a GraphQL Compose field type, resolving
the raw stored subfield value(s) into the shape GraphQL Compose's schema expects.

- **`custom`** (`CustomFieldItem`) — The entry point for every Custom Field. Iterates over
  the field's enabled subfields, builds a dedicated GraphQL type per field/entity/bundle
  combination, and dispatches each subfield's value to its matching FieldType plugin below
  based on the subfield's data type.
- **`custom_field_datetime`** (`CustomFieldDateTime`) — Resolves [datetime](../plugins/type/datetime.md) subfields into
  a `DateTime` structure containing a timestamp, timezone, UTC offset, and RFC 3339
  formatted time string.
- **`custom_field_daterange`** (`CustomFieldDateRange`) — Extends the datetime plugin to
  resolve [daterange](../plugins/type/daterange.md) subfields into a start/end `DateTime` pair plus duration.
- **`custom_field_time_range`** (`CustomFieldTimeRange`) — Resolves [time_range](../plugins/type/time-range.md) subfields
  into a start/end/duration structure.
- **`custom_field_entity_reference`** (`CustomFieldEntityReference`) — Resolves
  [entity_reference](../plugins/type/entity-reference.md) subfields into the referenced entity, translated for the current
  language context.
- **`custom_field_file`** (`CustomFieldFile`) — Resolves [file](../plugins/type/file.md) subfields into a `File`
  type with URL, filename, size, and MIME type, respecting file access checks.
- **`custom_field_image`** (`CustomFieldImage`) — Resolves [image](../plugins/type/image.md) subfields into an
  `Image` type with URL, dimensions, alt/title text, size, and MIME type. Falls back to
  reading actual image dimensions from disk if width/height aren't stored, and can
  optionally return sanitized inline SVG content.
- **`custom_field_link`** (`CustomFieldLink`) — Resolves [uri](../plugins/type/uri.md) and [link](../plugins/type/link.md) subfields into a
  `CustomFieldLink` type with title, resolved URL, an internal/external flag, and any
  link attributes. Falls back to the linked entity's label when no title is set.
- **`custom_field_viewfield`** (`CustomFieldViewfield`) — Resolves [viewfield](../plugins/type/viewfield.md) subfields
  into an embeddable view reference (view ID, display ID, page size), parsing and
  token-replacing the configured contextual filter arguments and checking view access.
- **`custom_field_text`** (`CustomFieldText`) — Resolves [string_long](../plugins/type/string-long.md) subfields into a
  `Text` type containing the raw value, the text format, and the format-processed
  (filtered) markup.
- **`custom_field_map`** (`CustomFieldMap`) — Resolves [map](../plugins/type/map.md) and [map_string](../plugins/type/map-string.md) subfields as
  `UntypedStructuredData`, passed through without additional processing.

### SchemaType plugins
Each plugin registers one or more GraphQL object types used by the FieldType plugins
above (and by `CustomField` itself) to describe the shape of a resolved subfield value.

- **`CustomField`** (`CustomFieldType`) — Dynamically generates one GraphQL object type
  per field/entity/bundle combination for every enabled Custom Field instance on the
  site, adding one field per enabled subfield, typed according to that subfield's
  FieldType plugin.
- **`CustomFieldUri`** (`CustomFieldUriType`) — Object type for plain [uri](../plugins/type/uri.md) subfields:
  `title`, `url`, and an `internal` flag. Unlike `CustomFieldLink`, it has no
  `attributes` field since plain URIs don't carry link attributes.
- **`CustomFieldLink`** (`CustomFieldLinkType`) — Object type for [link](../plugins/type/link.md) subfields:
  `title`, `url`, `internal` flag, and a nullable `attributes` field of type
  `CustomFieldLinkAttributes`.
- **`CustomFieldLinkAttributes`** (`CustomFieldLinkAttributesType`) — Object type whose
  fields are generated dynamically from whatever link attribute plugins are registered
  with the custom field link attributes plugin manager, each exposed as a camelCase
  string field (e.g. a `target` or `aria-label` attribute plugin becomes a `target` or
  `ariaLabel` field).
- **`CustomFieldTimeRange`** (`CustomFieldTimeRange`) — Object type for [time_range](../plugins/type/time-range.md)
  subfields: integer `start`, `end`, and `duration` (in seconds).
- **`CustomFieldDateRange`** (`CustomFieldDateRange`) — Object type for [daterange](../plugins/type/daterange.md)
  subfields: `start` and `end` fields (each a `DateTime` type), plus an integer
  `duration`.
- **`CustomFieldViewfield`** (`CustomFieldViewfield`) — Object type for [viewfield](../plugins/type/viewfield.md)
  subfields, exposing a `views` field of type `ViewResultUnion` with arguments for
  `page`, `offset`, `filter`, `sortKey`, and `sortDir`. Only registered if the
  `graphql_compose_views` module is enabled, since it depends on that module's result
  union type.

## Dependencies
- [GraphQL](https://www.drupal.org/project/graphql){target="_blank"} (`graphql`)
- [GraphQL Compose](https://www.drupal.org/project/graphql_compose){target="_blank"} (`graphql_compose`)
- GraphQL Compose: Custom Field (`custom_field_graphql`)