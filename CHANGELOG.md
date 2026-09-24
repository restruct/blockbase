# Changelog

## 2.0.0 (2026-09-25)

**Silverstripe 5 and 6.** One line, `main`, supports both. Silverstripe 4 stays on the `1.0.x` tags;
nothing here is backported. Upgrade guide: [UPGRADING.md](UPGRADING.md).

Requires PHP `^8.1`, `silverstripe/framework ^5 || ^6`, `dnadesign/silverstripe-elemental ^5.4 || ^6`
and `restruct/silverstripe-admintweaks ^3 || ^4`.

### Changed

- **Block descriptions use `class_description`.** Elemental 5.4 no longer reads the `description`
  config and elemental 6 removed it, together with `BaseElement::getDescription()`. The module's own
  config (`BlockContent`, `BaseElement`, `ElementVirtual`) now sets `class_description`; project blocks
  must do the same (see UPGRADING). `BlockBase::getType()` returns the class description, or the
  singular name when a class has none.
- New public method `getBlockDescription()` on every block (from `ElementBlockBaseExtension`): the class
  description, falling back to the type name. It replaces the `getDescription()` calls in the default
  block title and the editor summary.
- The extensions now extend `SilverStripe\Core\Extension` instead of `DataExtension` (removed in
  Silverstripe 6).
- The block-type icon preview (`admin/blocktypeicons`) renders from
  `templates/Restruct/Silverstripe/BlockBase/Dev/BlockIconsPreview.ss` instead of an inline template
  string, and lists every `BaseElement` subclass. It is no longer a CMS menu item, and outside dev it
  answers 404 (before, the admin routed it at `admin/admin/blocktypeicons` in every environment and
  the menu listed it under its class name).
- `composer.json`: PHP floor, `suggest` entries for `dnadesign/silverstripe-elemental-virtual` and
  `silverstripe/subsites`, a `funding` entry, a `2.x-dev` branch alias.
- Licence copyright line reads "Restruct".

### Fixed

- **Every flush fataled on Silverstripe 5 and 6**: `BlockIconsPreviewController::index()` was not
  compatible with `LeftAndMain::index(HTTPRequest $request): HTTPResponse`, so `dev/build` and
  `?flush=1` died before building anything.
- **The icon preview listed nothing**: it asked for subclasses of `Block::class`, which resolved to the
  non-existent `Restruct\Silverstripe\BlockBase\Dev\Block`.
- **`BlockContent::FieldEnabled()` answered crossed**: `'Content'` returned `has_image` and `'Image'`
  returned `has_content`.
- **`ElementContentMigrationExtension` had no effect**: `updateIsMigratable()` and
  `updatePageShouldSkip()` took their first argument by value, so excluding page types and skipping
  pages that already had blocks never reached `MigrateContentToElement`.
- **Opening a block in the Content Blocks admin threw a TypeError on Silverstripe 6**:
  `addFieldsToTab()` was given a `FieldList` instead of an array.
- `ElementVirtualExtension::LinkedElementRelation()` threw an `InvalidArgumentException` on a clone
  without a linked block yet (it passed `NULL` to `UnsavedRelationList::add()`). Only reachable through
  a custom field that uses `LinkedElementRelation`; the module's own linked-block field writes
  `LinkedElementID` directly.
- `getExtraData()` passed `NULL` to `json_decode()` for a block without ExtraData (deprecated since
  PHP 8.1).
- On Silverstripe 6: the page edit link in the block admin uses `getCMSEditLink()`, and the
  `ArrayList` class is picked per major.

### Added

- Test suite (54 tests, identical on Silverstripe 5 and 6) with a regression test for each fix above,
  and a GitHub Actions matrix: Silverstripe 5 on PHP 8.1 and 8.3, Silverstripe 6 on PHP 8.3 and 8.4,
  with elemental-virtual and subsites installed.
- README: what installing the module configures, every config option, the public API, the optional
  integrations, running the tests, and a version compatibility table.

### Notes

- No open GitHub issues or pull requests existed for this repository at release time.
- Consumers counted locally only: no local project requires this package; `restruct/silverstripe-newsgrid`
  suggests it (its `BlockNewsItems` block extends `BlockContent`). No hosting-wide inventory was made.
