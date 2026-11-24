# Image
The **Image** (`image`) custom field type plugin is used to store an entity id and 
additional properties (height, width, alt) associated with an image upload.

## Field Storage
| Setting    | Label              | Description                            | Default |
|------------|--------------------|----------------------------------------|---------|
| uri_scheme | Upload destination | Where the final files should be stored | public  |

## Field Settings
| Setting              | Label                   | Description                                               | Default                           |
|----------------------|-------------------------|-----------------------------------------------------------|-----------------------------------|
| file_directory       | File directory          | Optional subdirectory where files will be stored          | \[date:custom:Y]-\[date:custom:m] |
| file_extensions      | Allowed file extensions | Extensions separated by comma or space                    | png gif jpg jpeg webp             |
| max_filesize         | Maximum upload size     | Restricts the file upload size in KB or MB                |                                   |
| max_resolution       | Maximum width/height    | Restricts upload by max image width & height              |                                   |
| min_resolution       | Minimum width/height    | Restricts upload by min image width & height              |                                   |
| alt_field            | Enable *Alt* field      | Enables input field for *Alt text*                        | 1                                 |
| alt_field_required   | *Alt* field required    | Makes *Alt text* required when `alt_field` is enabled     | 1                                 |
| title_field          | Enable *Title* field    | Enables input field for *Title text*                      | 0                                 |
| title_field_required | *Title* field required  | Makes *Title text* required when `title_field` is enabled | 0                                 |

## Widgets
| Label                             | Plugin ID   | Default |
|-----------------------------------|-------------|---------|
| [Image](../widget/image-image.md) | image_image | &check; |
| [Hidden](../widget/hidden.md)     | hidden      |         |

## Formatters
| Label                                         | Plugin ID      | Default |
|-----------------------------------------------|----------------|---------|
| [Image](../formatter/image.md)                | image          | &check; |
| [Generic file](../formatter/file-default.md)  | file_default   |         |
| [URL to file](../formatter/file-url-plain.md) | file_url_plain |         |
| [URL to image](../formatter/image-url.md)     | image_url      |         |
| [Hidden](../formatter/hidden.md)              | hidden         |         |
