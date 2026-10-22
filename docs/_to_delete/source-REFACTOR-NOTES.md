# Contact → shared section frame, inverted variant

Contact is a frame-wearer like About, but on a **dark** frame. It wears
`class="contact section section--invert"` and keeps only its own headline /
sub / email typography. Included directly (not via the embed slot) — it has no
card grid.

## Apply in this order

1. **section.css** — add `.section--invert` (see `section.css.ADD.css`).
   Dark background, centered, 100px padding (`--size-25`), no bottom border.

2. **elements/eyebrow/eyebrow.css** — add `.eyebrow--invert` (see
   `eyebrow.css.ADD.css`). Dim-white color for eyebrows on a dark frame.

3. **contact.twig / contact.css** — replace with the refactored versions here.
   `.contact` and `.contact__label` are gone (now `.section--invert` and
   `.section__eyebrow` + `.eyebrow--invert`).

4. **surface.libraries.yml** — Contact now depends on the frame:

   ```yaml
   surface-contact:
     css:
       component:
         dist/css/contact.css: {}
     dependencies:
       - surface/section          # .section / .section--invert
       - surface/eyebrow          # .eyebrow--invert
   ```

   (Storybook hides a missing dep because styles.css globs everything — the dep
   matters only on the live site.)

## Homepage include — unchanged in shape

```twig
{% include '@components/contact/contact.twig' with {
  label:    contact.label,
  headline: contact.headline,
  sub:      contact.sub,
  email:    contact.email,
} only %}
```

## Why `--invert` lives on the frame, not in contact.css

Contact's dark skin is a *variant of the frame*, not a Contact-only quirk — any
future dark closing section can reuse `.section--invert` + `.eyebrow--invert`.
Putting the dark background on `.contact` would re-fork the frame and lose the
single-source-of-truth you just consolidated.

Color choice: the eyebrow's invert color is a **modifier on the element**, not
a `.section--invert .eyebrow { … }` descendant override. A frame restyling a
child component's bare selector is the wrapper-reaches-into-child anti-pattern
the theme doc warns against — the element owns its own color via its own
modifier.

## Frame-wearers so far

| Component | Frame | Eyebrow | Layout |
|---|---|---|---|
| `about`   | `.section` (light)            | `.eyebrow` (muted)   | `.about__grid` 1fr 1.5fr |
| `contact` | `.section.section--invert`    | `.eyebrow--invert`   | centered |
| writing/places | `section.twig` embed     | element via section  | `section__grid` (card grids) |
