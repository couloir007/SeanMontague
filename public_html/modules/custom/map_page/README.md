Map Page — How to use

Overview
- Test page to render an EDAN media viewer (iframe) from an EDAN record.
- Custom serializer Normalizer for field_edan_record in Views REST exports.

Prerequisites
- Enable modules: Serialization, REST, Views, Map Page, edan_connector and the EDAN provider it depends on.
- Clear caches after enabling or updating (drush cr).

1) EDAN iframe test route
This route renders a full-height iframe using the first media item from an EDAN record.

Paths (admin only):
- /admin/config/development/map-page/edan-test/edanmdm:nmnhanthro_12345
- /admin/config/development/map-page/edan-test?edan_id=edanmdm:nmnhanthro_12345
- /admin/config/development/map-page/edan-test?url=url:edanmdm:nmnhanthro_12345

Parameter precedence
- If route/path {edan_id} is provided, it wins.
- Else, if query edan_id is provided, use it.
- Else, if url is provided, use getObjectByUrl(url).
- Else, a safe sample fallback is used.

Code reference
- Controller: src/Controller/EdanTestController.php
- Service wrapper: src/Service/LeafletEdanObjectService.php
- Routing: map_page.routing.yml

2) Views REST export Normalizer (field_edan_record)
The module registers a serializer normalizer that only acts on the field named field_edan_record. It extends Drupal\serialization\Normalizer\FieldNormalizer and post-processes the value of each field item during serialization.

How to use
- Ensure the Serialization and REST modules are enabled.
- Create or edit a View with a REST export display (e.g., JSON format).
- In the REST export display, set Row style to "Entity" (not "Fields"). Normalizers only run when the serializer processes entities/fields.
- Add the field field_edan_record to the display (it can still be included in the output when using Row: Entity with a suitable serializer output).
- When the view is requested, the normalizer is applied to that field.

Customize processing
- Edit src/Normalizer/EdanRecordNormalizer.php
- Implement your logic in processEdanRecord($value, array $context = []) to transform the output (e.g., decode/encode JSON, filter keys, map to a summary).

Quick test
- After setting up a REST export view, request it with curl:
  curl -H "Accept: application/json" "https://example.org/path/to/your/rest/export"

Troubleshooting
- Iframe route shows error: Confirm the EDAN ID/URL is valid and edan_connector is configured.
- REST output unchanged: Verify the field is named exactly field_edan_record and that REST/Serialization are enabled. Clear caches.
- Permissions: The test routes are restricted to administrators (administer site configuration). Adjust if you need broader access.

Support & Maintenance
- Hook help: Admin > Help > Map Page shows these instructions within Drupal.
- Keep your caches clear after updating code: drush cr.
