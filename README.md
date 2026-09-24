# Restruct Silverstripe Block Base module

*Maintained by [Restruct](https://github.com/restruct). If this module saves you time, you can
[support ongoing maintenance](https://github.com/sponsors/restruct).*

This module serves as a base on top of [Elemental](https://github.com/silverstripe/silverstripe-elemental),
for blocks based projects by Restruct: a content block with configurable fields, editor tweaks for every
block type, a CMS admin listing all blocks, and optional support for virtual (clone) blocks and subsites.

## Requirements

* Silverstripe 5 or 6, with `dnadesign/silverstripe-elemental` 5.4+ or 6
* PHP 8.1 or newer (Silverstripe 6 itself needs 8.3)
* `restruct/silverstripe-admintweaks` 3 or 4 (installed automatically)

Optional, and supported when installed:

* `dnadesign/silverstripe-elemental-virtual`: virtual (clone) blocks
* `silverstripe/subsites`: allow or disallow block types per subsite

## Installation

```
composer require restruct/silverstripe-blockbase
```

Then run `dev/build` (Silverstripe 5) or `sake db:build` (Silverstripe 6) with a flush.

## Version compatibility

| Branch | Module version | Silverstripe | Elemental | PHP |
|--------|----------------|--------------|-----------|-----|
| `main` | `2.x` | `^5 \|\| ^6` | `^5.4 \|\| ^6` | `^8.1` |
| `v1` | `1.0.x` | `^4` | `^4.5` | per Silverstripe 4 |

Silverstripe 4 is end of life and is no longer supported or tested here. Projects still on it should
stay on the `1.0.x` tags (branch `v1`), which remain available.

`main` is the only maintained line: it supports every Silverstripe version this module still targets.
`v1` holds the Silverstripe 4 line as it was released and gets no further changes. Another version
branch will be created only when a change cannot be made compatible across the supported range.

**`composer.json` is the source of truth** for exact constraints; this table is a quick reference.

## What installing it does

The module's `_config/config.yml` applies the following to every project that installs it:

* `Page` gets Elemental's `ElementalPageExtension` and this module's `ContentBlocksToggleExtension`
  (below). `UserDefinedForm`, `RedirectorPage`, `VirtualPage`, `SubsitesVirtualPage`, the newsgrid
  holder and the sitemap page are excluded via `ElementalPageExtension.ignored_classes`.
* The page's own `Content` field is kept alongside the blocks (`ElementalAreasExtension.keep_content_fields: true`).
* Elemental's default front-end block styles are not included (`ElementController.include_default_styles: false`).
* Every block (`BaseElement`) is inline editable, does not show its title in the template, renders in
  this module's holder template (`controller_template: BlockHolder`) and gets `ElementBlockBaseExtension`.
* A CMS stylesheet (`client/dist/css/admin-block-tweaks.css`) is added to the admin.
* In `dev` mode only, the route `admin/blocktypeicons` (below). Outside dev the page answers 404 and it
  never appears in the CMS menu.

## Functionality

### `BlockContent`: text/content block

`Restruct\Silverstripe\BlockBase\Blocks\BlockContent` has a `Heading`, `IntroLine`, `Content` (HTML),
`Image` and `BackgroundImage`. Each can be switched off per project or per subclass:

| Config | Default | Effect |
|--------|---------|--------|
| `has_heading` | `true` | `false` removes the `Heading` field from the CMS |
| `has_introline` | `true` | `false` removes `IntroLine` |
| `has_content` | `true` | `false` removes `Content` |
| `has_image` | `true` | `false` removes `Image` |
| `has_bg_image` | `true` | `false` removes `BackgroundImage` |
| `image_upload_dir` | `null` | folder name for both image upload fields; unset uses the upload default |

`FieldEnabled('Heading')` (and `IntroLine`, `Content`, `Image`, `BackgroundImage`) returns the matching
setting, for use in templates.

```yml
Restruct\Silverstripe\BlockBase\Blocks\BlockContent:
  has_bg_image: false
  image_upload_dir: 'block-images'
```

**ExtraData.** Any field named `ExtraData_<Name>` added to a `BlockContent` (sub)class's CMS fields is saved
as `<Name>` in the block's `ExtraDataJSON` column, so project blocks can store extra settings without a
schema change. `getExtraData()` returns them as an array (or `null` when nothing was stored), and
`loadExtraData()` (called from `getCMSFields()`) puts them back on the record so the form shows them. This
works for plain fields (text, number, dropdown); more complex fields probably need additional work.

`BlockBase` is the (hidden) common parent of the module's blocks; project blocks can subclass it. Its
`getRenderTemplates()` adds plain theme template names after Elemental's namespaced ones, so a theme can
use `Blocks/BlockContent.ss`, `BlockContent_<style>.ss` and so on.

### `ElementBlockBaseExtension`: applied to every block

* `BaseElement`, `ElementContent` and `BlockBase` cannot be created (they are hidden from the add-block menu).
* A new block without a title is named `"<description> Block"`; a new block is not globally available
  (a workaround for [elemental-virtual#42](https://github.com/dnadesign/silverstripe-elemental-virtual/issues/42)).
* `getBlockDescription()`: the block's `class_description`, falling back to its type name.
* The editor summary reads `<description> block`, then a dash and the block's summary in curly quotes
  with a trailing ellipsis (the exact characters are in `ElementBlockBaseExtension::updateBlockSchema()`).
* Style and styling fields sit on the Main tab; the `Style` dropdown has no empty option.
* `BlockHolderClasses()` and `getPath()` for templates and pickers.

#### Layout variants (`styles`)

A list of layout variants can be set via YAML or `private static $styles`, the first one being the default.

```yml
Restruct\Silverstripe\BlockBase\Blocks\BlockContent:
  styles:
    single-col-wide: 'Single wide column layout'
    single-col-narrow: 'Single narrow column layout'
    double-col-equal: 'Two equal-width columns layout'
```

The selected `$StyleVariant` is available in templates and is included as class on the wrapper element
(`DNADesign/Elemental/Layout/BlockHolder.ss`). It can also be used to switch between different templates,
eg `BlockType.ss` / `BlockType_single-col-wide.ss` / etc.

#### Styling options (`style_options` / ExtraClass)

Further styling variations can be offered as a dropdown for the block's `ExtraClass`. Without
`style_options` the free-text `ExtraClass` field is removed.

```yml
Restruct\Silverstripe\BlockBase\Blocks\BlockContent:
  style_options:
    light: 'Light'
    dark: 'Dark'
```

### `ContentBlocksToggleExtension`: content, blocks, or both

Adds a `ContentBlocksToggle` dropdown before a page's `Content` field: content followed by blocks (the
default), content only, or blocks only. After saving, the unused editor is hidden.

### Block admin

`Restruct\Silverstripe\BlockBase\Admin\BlockAdmin` ("Content Blocks", `admin/blocks-admin`) lists every
block with its type, icon, title and the page it is used on. Its edit form shows the block's settings on
the main tab and a "Linked to page" picker, which moves the block to another page's (first) elemental area.

### Block type icons preview (dev only)

`admin/blocktypeicons` (CMS access required, `dev` mode only) lists every block type with its icon and
description, to check icon crops. It is not a CMS menu item, and outside dev it answers 404 on every
route, including the `admin/admin/blocktypeicons` route the admin gives every `LeftAndMain`.

#### Show block designs/thumbnails instead of icons in the admin UI

1. copy & adapt below section to specific project code css to show designed block previews instead of icons
2. set `private static $icon` to `'block-design block-section {block-name-offset}'`
3. add stacked blocks img to app/client (`.block-name-offset` sets offset if multiple stacked in one image)

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

### Virtual (clone) blocks

With `dnadesign/silverstripe-elemental-virtual` installed, `ElementVirtualExtension` replaces its block
picker with a dropdown of globally available blocks (labelled with their page and type), explains what to
do when none are available, and removes the style fields that do not apply to a clone. Virtual blocks
use the module's holder template and are inline editable.

### Allow/disallow specific block types on subsites

With `silverstripe/subsites` installed, `SubsitesPageExtension` is applied to `Page`. On a subsite (not on
the main site) it limits the available block types, and removes the block editor when none are allowed and
the page has no blocks left.

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

On Elemental 6 the available types are cached per page class for the rest of the request, so they are
computed for whichever subsite is active first in that request.

### Migrating page content to blocks

`ElementContentMigrationExtension` is not applied by default. Apply it to Elemental's
`MigrateContentToElement` task to migrate only plain `Page` records, skip pages that already have blocks,
and name each migrated block after its page. See Elemental's
[content migration docs](https://github.com/silverstripe/silverstripe-elemental/blob/6/docs/en/09_content_migration.md).

## Notes

### Template relations

The `DNADesign/Elemental/Models/ElementalArea.ss` template loops over each of the element controller
instances. Each controller instance renders `$ElementHolder`, which represents the element contained
within a holder div. This module's holder is `DNADesign/Elemental/Layout/BlockHolder.ss`.

### Unset nested blocks .container padding (for first level nested)

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

### Advanced config

Advanced config pointers: https://github.com/silverstripe/silverstripe-elemental/blob/6/docs/en/02_advanced_setup.md

## Running the tests

The module cannot be tested on its own: it needs a host Silverstripe project. Require it there through a
Composer **path repository with `symlink: true`** - `/tests` is `export-ignore`, so a dist or mirrored
install contains no tests - add its test namespace to the host's `autoload-dev`, and run in `dev` mode.
Install `dnadesign/silverstripe-elemental-virtual` and `silverstripe/subsites` too, or their tests are
skipped. Then:

```bash
# Silverstripe 5 (PHPUnit 9) - the path must come before flush=1
vendor/bin/phpunit vendor/restruct/silverstripe-blockbase/tests flush=1

# Silverstripe 6 (PHPUnit 11) - a flush=1 argument is ignored, use the env var
SS_PHPUNIT_FLUSH=1 vendor/bin/phpunit vendor/restruct/silverstripe-blockbase/tests
```

CI runs the same suite against Silverstripe 5 (PHP 8.1, 8.3) and 6 (PHP 8.3, 8.4) on every push; see
`.github/workflows/ci.yml`.

## Licence

MIT, see [LICENSE](LICENSE).
