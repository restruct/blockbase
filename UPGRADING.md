# Upgrading from 1.x to 2.0

2.0 supports Silverstripe 5 and 6. Silverstripe 4 projects stay on `^1.0`.

1. **Require the new version** alongside Silverstripe 5 or 6 and Elemental 5.4+ or 6:
   `composer require restruct/silverstripe-blockbase:^2`. `restruct/silverstripe-admintweaks` moves to
   `^3` (Silverstripe 5) or `^4` (Silverstripe 6) with it.
2. **Rename `description` to `class_description`** on your own block classes and in YAML:

   ```php
   //private static $description = 'Call to action';
   private static $class_description = 'Call to action';
   ```

   Elemental 5.4 ignores the old key and Elemental 6 does not have it, so without the rename the block
   type shows its singular name instead. `class_description` is not inherited: set it on every block
   class that should have one.
3. **Replace `getDescription()` calls** in your own code or templates with `i18n_classDescription()`, or
   with `getBlockDescription()` (`$BlockDescription` in templates) to fall back to the type name. Elemental
   6 removed `getDescription()`.
4. **Project extensions of these classes**: the module's extensions now extend `Extension`. If you
   subclass one of them and call `parent::` on a hook that the module class does not define itself
   (for example `parent::onBeforeWrite()` on `ContentBlocksToggleExtension` or `SubsitesPageExtension`),
   remove that call; `Extension` has no such method. Keep `parent::` calls to hooks the module class
   does define: `onBeforeWrite()` on `ElementBlockBaseExtension` and `ElementVirtualExtension`, and
   `updateCMSFields()` on `ElementBlockBaseExtension`, `ElementVirtualExtension`,
   `ContentBlocksToggleExtension` and `SubsitesPageExtension`. `ElementContentMigrationExtension`
   defines neither hook.
5. If you applied `ElementContentMigrationExtension` to `MigrateContentToElement`: it now actually
   excludes non-`Page` types and skips pages that already have blocks. Check that is what you want before
   running the task again.
6. Flush and build: `dev/build flush=1` (Silverstripe 5) or `sake db:build --flush` (Silverstripe 6). No
   schema changes.
