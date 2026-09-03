# Linkit URL formatter
The **Linkit URL** (`linkit_url`) formatter extends the [uri_link](../formatter/uri-link.md)
formatter by providing an additional *Linkit* profile setting for building the
link URL with additional *Linkit* attributes.

## Settings

| Setting        | Label                            | Description                                                                                                     | Default |
|----------------|----------------------------------|-----------------------------------------------------------------------------------------------------------------|---------|
| link_text      | Link text                        | An optional input to display text as the instead of the url                                                     |         |
| trim_length    | Trim link text length            | An optional to trim displayed link text to desired length                                                       | 80      |
| url_plain      | Show URL as plain text           | An option to display the url as plain text                                                                      | FALSE   |
| rel            | Add rel="nofollow" to links      | An option to set the `nofollow` rel attribute                                                                   |         |
| target         | Open external link in new window | An option to set the `target="_blank"` attribute to external links                                              |         |
| noopener       | Add rel="noopener" to links      | An option to set the `noopener` attribute to external links when **Open external link new window** is checked   |         |
| noreferrer     | Add rel="noreferrer" to links    | An option to set the `noreferrer` attribute to external links when **Open external link new window** is checked |         |
| title          | Title                            | An optional input to add a `title` attribute to the link                                                        |         |
| aria-label     | ARIA label                       | An optional input to add an `aria-label` attribute to the link                                                  |         |
| class          | Class                            | An optional input to add classes to the link                                                                    |         |
| id             | ID                               | An optional input to add an `id` attribute to the link                                                          |         |
| name           | Name                             | An optional input to add a `name` attribute to the link                                                         |         |
| accesskey      | Access key                       | An optional input to add an `accesskey` attribute to the link                                                   |         |
| linkit_profile | Linkit profile                   | An option to select a *Linkit* profile to use for building the link URL                                         | default |

### Style settings
--8<-- "formatter/global.md:formatter_settings_wrapper"

## Dependencies
- Custom Field - Linkit integration[`custom_field_linkit`] sub-module
- [Linkit](https://www.drupal.org/project/linkit){target="_blank"}

## Field types
- [url](../type/uri.md)