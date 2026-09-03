# Multilingual support

The **Custom Field** module provides full subfield-level translation support.
When the field is marked as translatable, each subfield can be translated
independently, giving you fine-grained control.

## Configuration

1. Enable the `content_translation` module.
2. Navigate to the **Content language and translation** settings page
   `/admin/config/regional/content-language`.
3. Enable the entity types you want to translate.
4. Enable the fields you want to translate.
5. **Save** the settings.
6. Navigate to the field settings page for your field.
7. Enable the **Users may translate this field** setting.
8. In the **Field settings** section, enable the **Users may translate this 
   field** setting for each subfield. Leaving this setting disabled will 
   save the default language value and hide the subfield in the translation
   form.
9. **Save** the field settings.
