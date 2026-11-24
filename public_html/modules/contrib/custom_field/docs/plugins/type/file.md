# File
The **File** (`file`) custom field type plugin is used to store an entity id associated
with a file upload.

## Field Storage
| Setting    | Label              | Description                            | Default |
|------------|--------------------|----------------------------------------|---------|
| uri_scheme | Upload destination | Where the final files should be stored | public  |

## Field Settings
| Setting         | Label                   | Description                                      | Default                           |
|-----------------|-------------------------|--------------------------------------------------|-----------------------------------|
| file_directory  | File directory          | Optional subdirectory where files will be stored | \[date:custom:Y]-\[date:custom:m] |
| file_extensions | Allowed file extensions | Extensions separated by comma or space           | txt                               |
| max_filesize    | Maximum upload size     | Restricts the file upload size in KB or MB       |                                   |

## Widgets
| Label                             | Plugin ID    | Default |
|-----------------------------------|--------------|---------|
| [File](../widget/file-generic.md) | file_generic | &check; |
| [Hidden](../widget/hidden.md)     | hidden       |         |

## Formatters
| Label                                         | Plugin ID      | Default |
|-----------------------------------------------|----------------|---------|
| [Generic file](../formatter/file-default.md)  | file_default   | &check; |
| [URL to file](../formatter/file-url-plain.md) | file_url_plain |         |
| [Hidden](../formatter/hidden.md)              | hidden         |         |
