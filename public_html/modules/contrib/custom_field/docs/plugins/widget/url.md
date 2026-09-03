# Url widget
The **Url** (`url`) widget provides an interface for entering web addresses.

When the field's **Allowed link type** setting excludes internal links (i.e.
"External links only"), the URL input uses the native HTML
`<input type="url">` element for client-side format validation. When
internal links are allowed — including the default "Both internal and
external links" setting — the URL input instead renders as an entity
autocomplete field, since the browser's URL validation can't accommodate
internal paths. In both cases, the entered value is validated and
normalized server-side (converted to an `internal:`, `entity:`, or plain
external URI) regardless of which input element is used.

## Settings
| Setting       | Label                   | Description                                             | Default |
|---------------|-------------------------|---------------------------------------------------------|:--------|
| placeholder   | Placeholder             | Text shown inside the field when empty                  |         |

## Field types
- [uri](../type/uri.md)
