# Restruct Silverstripe Block Base module

This module serves as a base on top of Elemental, for blocks based projects by Restruct.

## Unset nested blocks .container padding (for first level nested)
In case your project already wraps blocks inside a .container, the block's .container will have double padding.
Below CSS unsets this (based on a project which wraps all content in a subnav and a content .col, adapt to your specific project):

```css
.blocks-container {
  // unset padding/margin of (second level ) .container if contained within .subnav-slot-* (before/after)
  &.subnav-slot-before,
  &.subnav-slot-after {
    & > .block-item-wrapper > .container > .row > [class*="col"] > .block-item-wrapper > .container {
      padding: 0;
      // .row & .col* should remain unchanged as there may be multiple columns with a block
    }
  }
}
```

## Functionality:
- Various Block (Elemental) tweaks (+ block icon/thumbnail preview route at `admin/blocktypeicons`)
- ...


## Show Block design/thumbnails instead of icons in admin UI (Elemental)

1. copy & adapt below section to specific project code css to show designed block previews instead of icons
2. set private static $icon to 'block-design block-section {block-name-offset}'
3. add stacked blocks img to app/client (.block-name-offset sets offset if multiple stacked in one image)

```scss
i.block-section, button.block-section:before {
  background-image: url(~app/client/imgs/block-group-designs_stacked.png);
  background-position: 0 0;
}
i.block-section, button.block-section {
  &.block-name-offset {&, &:before {
    background-position: 0 -128px;
  }}
  &.block-othername-offset {&, &:before {
    background-position: 0 -28px;
  }}
}
```

## Notes

### Template relations
The DNADesign/Elemental/Models/ElementalArea.ss template loops over each of the element controller instances.
Each controller instance will render $ElementHolder which represents the element contained within a holder div.
The wrapper div is the ElementHolder.ss template.


### ~Style~ layout variants
A list of ~style~ layout variants can be set via YAML or `private static $styles`, the first one being the default.
```yml
Restruct\Silverstripe\BlockBase\Blocks\BlockContent:
  styles:
    single-col-wide: 'Single wide column layout'
    single-col-narrow: 'Single narrow column layout'
    double-col-equal: 'Two equal-width columns layout'
```

The selected $StyleVariant will be available in themplates and is included as class on the wrapper element (DNADesign/Elemental/Layout/BlockHolder.ss).
It can also be used to switch between different templates, eg BlockType.ss / BlockType_single-col-wide.ss / etc.

### Styl(ing) options (ExtraClass)
Further styling variations can be controlled by activating one or multiple additional CSS classes to the block holder.
Options (eg based on theme) can be set via YAML on a project basis, or directly on the block classes (`private static $style_options`).
```yml
Restruct\Silverstripe\BlockBase\Blocks\BlockContent:
  style_options:
    - 'light'
    - 'dark'
```

### Advanced config
Advanced config pointers: https://github.com/silverstripe/silverstripe-elemental/blob/4/docs/en/advanced_setup.md

#### Allow/disallow specific block types on subsites

(Handled by `Restruct\Silverstripe\BlockBase\Extensions\SubsitesPageExtension`)

```yml
Page:

#  # Example: disallow any blocks on subsites
#  subsites_allowed_elements: false

#  # Example: allow specific elements on subsites
#  subsites_allowed_elements:
#    - Restruct\Silverstripe\BlockBase\Blocks\BlockContent

#  # Example: DISallow specific elements on subsites
#  subsites_disallowed_elements:
#    - Restruct\Silverstripe\BlockBase\Blocks\BlockContent
```
