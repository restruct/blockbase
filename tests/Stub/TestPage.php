<?php

namespace Restruct\Silverstripe\BlockBase\Tests\Stub;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\TestOnly;

/**
 * A page type owned by the test suite, so the tests do not depend on how a host project configures
 * its own Page. The extensions under test are applied through each test's $required_extensions.
 *
 * Kept concrete: an abstract DataObject anywhere in tests/ fatals the temp-database build of every
 * consuming project (SOP, Phase 4).
 */
class TestPage extends SiteTree implements TestOnly
{
    # Short, so relation join tables stay below MySQL's 64-character limit
    private static $table_name = 'BlockBaseTestPage';
}
