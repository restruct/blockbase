<?php

namespace Restruct\Silverstripe\BlockBase\Tests\Stub;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Dev\TestOnly;

/**
 * Stands in for elemental's MigrateContentToElement task as the owner of ElementContentMigrationExtension:
 * the task fires its hooks through the same Extensible::extend(), which passes arguments by reference.
 * The task itself is not used because its constructor and run() differ between elemental 5 and 6.
 */
class MigrationHost implements TestOnly
{
    use Configurable;
    use Extensible;
    use Injectable;
}
