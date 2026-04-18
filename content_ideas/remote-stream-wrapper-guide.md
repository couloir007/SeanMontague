# Using Remote Stream Wrapper to Apply Drupal Image Styles to Remote Images

*A practical guide based on real implementation with MLS listing photos*

---

One of the more frustrating gaps in Drupal's contributed module ecosystem is handling remote images — specifically, applying core image styles to images hosted on external CDNs. If you've ever integrated a third-party API that returns photo URLs (MLS feeds, product catalogs, DAM systems), you've hit this wall.

This guide documents exactly how I solved it for a real estate project integrating PrimeMLS listing photos into Drupal 10, using the [Remote Stream Wrapper](https://www.drupal.org/project/remote_stream_wrapper) module.

---

## The Problem

Drupal's image style system generates derivative images (thumbnails, crops, etc.) by processing local files. When your images live on an external CDN and you only have URLs, you have three options:

1. **Download and store locally** — full image style support, but storage grows indefinitely
2. **Render as plain `<img>` tags** — zero storage, but no image styles
3. **Remote Stream Wrapper** — image styles work, derivatives cached locally, originals stay remote

For active listing photos that change frequently and expire when listings sell, option 3 is the right call.

---

## How Remote Stream Wrapper Works

The module registers `http://` and `https://` as Drupal stream wrappers. This means Drupal's file system treats remote URLs exactly like local file URIs.

When Drupal's image toolkit encounters `https://cdn.example.com/photo.jpg`, it reads it through the RSW stream handler — which fetches the remote bytes — and generates a derivative. That derivative is cached locally at:

```
public://styles/listing_card/https/cdn.example.com/photo.jpg
```

Subsequent requests serve the cached derivative. The remote image is only fetched once per style per image.

---

## Installation

```bash
composer require drupal/remote_stream_wrapper
drush en remote_stream_wrapper -y
drush cr
```

Verify the stream wrappers are registered:

```bash
drush php-eval "
  \$wrappers = \Drupal::service('stream_wrapper_manager')->getWrappers();
  echo isset(\$wrappers['https']) ? 'https:// OK' : 'https:// MISSING';
  echo PHP_EOL;
"
```

---

## Creating a Media Type for Remote Images

Create a dedicated media type for your remote images. In this example: `mls_photo`.

The key insight — which is not documented anywhere clearly — is that you use a standard **image field** (not a text/URL field) as the source field. RSW makes `https://` URIs valid for image fields.

Config for the media type (`media.type.mls_photo.yml`):

```yaml
id: mls_photo
label: 'MLS Photo'
source: image
source_configuration:
  source_field: field_media_mls_image
```

Add an image field (`field_media_mls_image`) to this bundle. Standard image field — nothing special required.

For the **form display**, set the widget to `image_remote_stream_wrapper` (provided by the companion [Remote Stream Wrapper Widget](https://www.drupal.org/project/remote_stream_wrapper_widget) module). This gives editors a URL input instead of a file upload button.

---

## Creating Media Entities Programmatically

This is the part most guides skip entirely. For API integrations, you're creating media entities in code, not through the UI.

The pattern is:

1. Create a **file entity** with the remote URL as its URI
2. Reference that file entity in the media entity's image field

RSW's stream wrapper registration is what makes step 1 valid — without it, Drupal rejects `https://` as an invalid URI scheme for managed files.

```php
$file_storage  = \Drupal::entityTypeManager()->getStorage('file');
$media_storage = \Drupal::entityTypeManager()->getStorage('media');

// Step 1: Create a file entity with the remote URI.
// RSW registers https:// as a valid scheme, so this works.
// No file is downloaded — only the URI is stored.
$existing = $file_storage->loadByProperties(['uri' => $url]);

if (!empty($existing)) {
  $file = reset($existing);
}
else {
  $file = $file_storage->create([
    'uri'      => $url,
    'filename' => basename(parse_url($url, PHP_URL_PATH) ?: $url),
    'status'   => 1,
    'uid'      => 1,
  ]);
  $file->save();
}

// Step 2: Create the media entity referencing the file.
$media = $media_storage->create([
  'bundle'                => 'mls_photo',
  'name'                  => 'Property Photo',
  'field_media_mls_image' => [
    'target_id' => $file->id(),
    'alt'        => 'Property photo',
  ],
  'status' => 1,
]);
$media->save();
```

**Important:** Check for existing file entities before creating new ones. On reimport runs you don't want duplicate file entities for the same URL.

---

## Image Styles Work Immediately

Once you have a media entity created this way, image styles work exactly as they do for local images.

Generate a styled URL programmatically:

```php
$file = $media->get('field_media_mls_image')->entity;
$uri  = $file->getFileUri(); // Returns: https://cdn.example.com/photo.jpg

$style = \Drupal::entityTypeManager()
  ->getStorage('image_style')
  ->load('listing_card');

$styled_url = $style->buildUrl($uri);
// Returns: https://yoursite.com/sites/default/files/styles/listing_card/https/cdn.example.com/photo.jpg?itok=xxx
```

The first time that URL is requested, Drupal fetches the remote image via RSW, generates the derivative, and caches it. Fast on every subsequent request.

---

## Adding a URL Visibility Field

One practical addition: add a plain text `field_remote_url` to your media type so editors can see where a photo came from in the admin UI.

Add the field, then wire it up with a presave hook so setting the URL automatically populates the image field:

```php
/**
 * Implements hook_entity_presave().
 */
function mymodule_entity_presave(\Drupal\Core\Entity\EntityInterface $entity): void {
  if ($entity->getEntityTypeId() !== 'media') {
    return;
  }
  if ($entity->bundle() !== 'mls_photo') {
    return;
  }

  $url = $entity->get('field_remote_url')->getString();
  if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
    return;
  }

  // Only populate if image field is currently empty.
  if (!$entity->get('field_media_mls_image')->isEmpty()) {
    return;
  }

  $file_storage = \Drupal::entityTypeManager()->getStorage('file');
  $existing     = $file_storage->loadByProperties(['uri' => $url]);

  $file = !empty($existing) ? reset($existing) : $file_storage->create([
    'uri'      => $url,
    'filename' => basename(parse_url($url, PHP_URL_PATH) ?: $url),
    'status'   => 1,
    'uid'      => 1,
  ]);

  if ($file->isNew()) {
    $file->save();
  }

  $entity->set('field_media_mls_image', [
    'target_id' => $file->id(),
    'alt'        => $entity->label(),
  ]);
}
```

Now an editor or importer can set `field_remote_url` and the image field is populated automatically on save.

---

## Storage Considerations

Derivative images are cached in `public://styles/{style_name}/https/...`. This cache grows over time but can be flushed with `drush image-flush --all`.

The remote originals are never stored. If a remote URL 404s, derivative generation fails silently for that image. For content that needs to outlive the remote CDN (sold property records, for example), download and store the primary image locally using `\Drupal::service('file.repository')->writeData()` instead.

---

## What Doesn't Work

- **Responsive image styles** — there is an open issue for this
- **Plain http://** — only `https://` is supported for security reasons
- **Offline/airgapped environments** — derivative generation requires the remote URL to be reachable at cache-build time

---

## Summary

| | Standard image field | Remote Stream Wrapper |
|---|---|---|
| Storage | Local file | Remote URL, derivatives only |
| Image styles | ✓ | ✓ |
| Manual UI entry | File upload | URL input (with widget module) |
| Programmatic creation | Upload file first | Create file entity with remote URI |
| Derivative cache | Local | Local |
| Original expires | Never | When remote CDN removes it |

Remote Stream Wrapper is the right tool when you have high-volume remote images that change frequently — API feeds, MLS listings, product catalogs — where you want image style support without the storage overhead of downloading every original.
