# UUID
The **UUID** (`uuid`) custom field type plugin is used to store automatically generated 
unique identifiers.

> [!tip] UUIDs in Multivalue Fields
> Using the **UUID** field type in a multivalued Custom Field can be very 
> useful for decoupled frontends (React, Next.js, etc.).
> It gives each item a **stable unique ID**, which prevents hydration errors 
> and serves as a reliable `key` when rendering lists.

## Widgets
| Label                     | Plugin ID | Default |
|---------------------------|-----------|---------|
| [UUID](../widget/uuid.md) | uuid      | &check; |

## Formatters
| Label                                            | Plugin ID      | Default |
|--------------------------------------------------|----------------|---------|
| [Plain text](../formatter/string.md)             | string         | &check; |
| [Hidden](../formatter/hidden.md)                 | hidden         |         |