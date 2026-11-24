# Entity Embed

## Related projects

Entity Embed builds on the [Embed](https://www.drupal.org/project/embed) module, which provides embed buttons (the `EmbedButton` config entity and form), embed type plugins, the CKEditor 5 button deriver (`EmbedCKEditor5PluginDeriver`), `DomHelperTrait`, `EmbedInsertCommand` and the editor access check. Before changing one of these areas here, check whether all or part of the change belongs in Embed instead, such as a fix any embed type would need. If it does, find or file an issue in [Embed's issue queue](https://www.drupal.org/project/issues/embed) and link it from the Entity Embed issue or a `@todo`.

## Supported versions

- 8.x-1.x requires Drupal `^11.3 || ^12`. Code must work on Drupal 11.3.
- CKEditor 4 support stays in 8.x-1.x: the commercial [CKEditor 4 LTS](https://www.drupal.org/project/ckeditor_lts) module provides the `ckeditor` module on Drupal 11. Keep `src/Plugin/CKEditorPlugin/DrupalEntity.php` and its JavaScript working, even though the contributed CKEditor 4 module doesn't support Drupal 11.
- Drupal 11 core has no CKEditor 4 to 5 upgrade path, so that code is unused ([#3639510](https://www.drupal.org/project/entity_embed/issues/3639510)).
- Removals that break backwards compatibility, such as CKEditor 4 support, the `@EntityEmbedDisplay` annotation and legacy embed attributes like `data-langcode`, wait for 2.0.x. They're tracked in [#3639495](https://www.drupal.org/project/entity_embed/issues/3639495). In 8.x-1.x, deprecate instead, with a change record.
