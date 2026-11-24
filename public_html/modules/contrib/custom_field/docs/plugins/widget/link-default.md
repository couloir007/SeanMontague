# Link widget
The **Link** (`link_default`) widget provides an interface for entering web
addresses, optionally paired with a separate **Link text** field and
additional HTML attributes (target, class, rel, etc).

When the field's **Allowed link type** setting excludes internal links
(i.e. "External links only"), the URL input uses the native HTML
`<input type="url">` element for client-side format validation. When
internal links are allowed — including the default "Both internal and
external links" setting — the URL input instead renders as an entity
autocomplete field, since the browser's URL validation can't accommodate
internal paths. In both cases, the entered value is validated and
normalized server-side regardless of which input element is used.

If **Allow link text** is enabled, a required-together relationship is
enforced between the URL and Link text fields: submitting one without
the other produces a validation error, with the exact requirement
depending on whether **Allow link text** is set to Optional or Required.

## Settings
| Setting           | Label                           | Description                                                 | Default |
|-------------------|---------------------------------|-------------------------------------------------------------|:--------|
| placeholder_url   | Placeholder for URL             | Text shown inside the URL field when empty                  |         |
| placeholder_title | Placeholder for link text       | Text shown inside the link text field when empty            |         |
| maxlength         | Max length for link text        | Maximum amount of characters allowed in the link text field | 255     |
| maxlength_js      | Show max length character count | Display a live character counter below the link text field  | FALSE   |

> [!Note]
> The `maxlength_js` setting is visible when the `title` field is enabled and
> the [MaxLength](https://www.drupal.org/project/maxlength){:target="_blank"} module is installed.

## Field types
- [link](../type/link.md)