# How to migrate data from external sources
An easy way to import content into a multi-value **Custom Field** with unlimited values is
from data in JSON format, using core's
[`sub_process`](https://www.drupal.org/docs/8/api/migrate-api/migrate-process-plugins/migrate-process-overview#s-nested-values){target="_blank"}
Migrate process plugin.

## Requirements
Install [Migrate Plus](https://www.drupal.org/project/migrate_plus){target="_blank"} to get the
[`url`](https://www.drupal.org/docs/8/api/migrate-api/migrate-source-plugins/overview-of-migrate-source-plugins#available%20migrate%20plugin%20process){target="_blank"}
migrate source plugin, which is used to fetch and parse the JSON source file below.

## Example
For this example, create a **Custom Field** field called "Hyperlink" with machine name
`hyperlink` on the Article content type, with two Custom Field items (subfields):

- "Link text" with machine name `link_text`
- "URL" with machine name `link_url`

### The source file
```json
[
  {
    "id": 299,
    "title": "Custom Field hyperlink import",
    "hyperlink": [
      {
        "link_url_source": "https://drupal.org",
        "link_text_source": "Drupal"
      },
      {
        "link_url_source": "https://www.example.com",
        "link_text_source": "Example Website"
      },
      {
        "link_url_source": "https://gitlab.com/",
        "link_text_source": "GitLab"
      }
    ]
  }
]
```

### The migration module
Create a custom migration module called `migrations` with this structure:

```text
migrations/
├── custom_field_hyperlink.json
├── migrations
│   └── custom_field_hyperlink.yml
└── migrations.info.yml
```

`custom_field_hyperlink.yml` contains the migration definition:

```yaml
id: custom_field_hyperlink
label: Custom field import
source:
  plugin: url
  data_fetcher_plugin: file
  data_parser_plugin: json
  urls: modules/custom/migrations/custom_field_hyperlink.json
  fields:
    -
      name: id
      selector: id
    -
      name: title
      selector: title
    -
      name: hyperlink
      selector: hyperlink
  ids:
    id:
      type: integer
process:
  nid: id
  title: title
  field_hyperlink:
    plugin: sub_process
    source: hyperlink
    process:
      link_url: link_url_source
      link_ext: link_text_source
destination:
  plugin: entity:node
  default_bundle: article
```

Run the migration, and each JSON entry's `hyperlink` array will be imported as a set of
items in the unlimited-value `hyperlink` Custom Field.