<?php

namespace Restruct\Silverstripe\BlockBase\Tests;

use DNADesign\Elemental\Extensions\ElementalPageExtension;
use Page;
use Restruct\Silverstripe\BlockBase\Blocks\BlockContent;
use Restruct\Silverstripe\BlockBase\Extensions\ElementContentMigrationExtension;
use Restruct\Silverstripe\BlockBase\Tests\Stub\MigrationHost;
use Restruct\Silverstripe\BlockBase\Tests\Stub\TestPage;
use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\Dev\SapphireTest;

/**
 * The hooks are fired through extend(), as MigrateContentToElement fires them.
 */
class ElementContentMigrationExtensionTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected static $extra_dataobjects = [
        TestPage::class,
    ];

    protected static $required_extensions = [
        MigrationHost::class => [
            ElementContentMigrationExtension::class,
        ],
        TestPage::class => [
            ElementalPageExtension::class,
        ],
    ];

    /**
     * Regression: the parameter was taken by value, so no page type was ever excluded.
     */
    public function testOnlyPlainPagesAreMigratable()
    {
        $host = MigrationHost::create();

        # Variables, not literals: extend() takes every argument by reference
        $migratable = true;
        $pageType = RedirectorPage::class;
        $host->extend('updateIsMigratable', $migratable, $pageType);
        $this->assertFalse($migratable);

        $migratable = true;
        $pageType = Page::class;
        $host->extend('updateIsMigratable', $migratable, $pageType);
        $this->assertTrue($migratable);
    }

    /**
     * Regression: the parameter was taken by value, so pages with blocks were migrated again.
     */
    public function testPagesThatAlreadyHaveBlocksAreSkipped()
    {
        $host = MigrationHost::create();
        $page = TestPage::create();
        $page->write();

        $skip = false;
        $host->extend('updatePageShouldSkip', $skip, $page);
        $this->assertFalse($skip, 'A page without blocks is migrated');

        $area = $page->ElementalArea();
        $area->write();
        $page->ElementalAreaID = $area->ID;
        $page->write();
        BlockContent::create(['ParentID' => $area->ID])->write();
        $skip = false;
        $host->extend('updatePageShouldSkip', $skip, $page);
        $this->assertTrue($skip, 'A page that already has blocks is skipped');
    }

    public function testMigratedElementIsNamedAfterItsPage()
    {
        $host = MigrationHost::create();
        $page = TestPage::create(['Title' => 'Source page']);
        $page->write();
        $element = BlockContent::create(['Title' => 'Migrated']);

        $content = '<p>x</p>';
        $host->extend('updateMigratedElement', $element, $content, $page);
        $this->assertSame('Migrated: Source page', $element->Title);
    }
}
