# Image formatter
The **Image** (`image`) formatter displays an image with configurable *Image
style*.

## Settings
| Label                   | Setting                 | Description                                                           | Default |
|-------------------------|-------------------------|-----------------------------------------------------------------------|---------|
| Image style             | image_style             | An optional image style to apply                                      |         |
| Link image to           | image_link              | Option to link image to `content` or `file`                           |         |
| Image loading attribute | image_loading.attribute | Option to lazily load images with attribute `lazy`[^1] or `eager`[^2] | lazy    |

### Style settings
--8<-- "formatter/global.md:formatter_settings_wrapper"

## Field types
- [image](../type/image.md)

[^1]: **Lazy** *(loading="lazy")* – Delays loading the image until that section 
of the 
page is 
visible in the browser. When in doubt, lazy loading is recommended.
[^2]: **Eager** *(loading="eager")* – Force browser to download an image as 
soon as possible. 
This is the browser default for legacy reasons. Only use this option when the 
image is always expected to render.
