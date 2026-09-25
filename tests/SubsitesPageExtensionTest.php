<?php

namespace Restruct\Silverstripe\BlockBase\Tests;

use DNADesign\Elemental\Extensions\ElementalAreasExtension;
use DNADesign\Elemental\Extensions\ElementalPageExtension;
use DNADesign\Elemental\Models\ElementContent;
use Restruct\Silverstripe\BlockBase\Blocks\BlockContent;
use Restruct\Silverstripe\BlockBase\Extensions\ContentBlocksToggleExtension;
use Restruct\Silverstripe\BlockBase\Extensions\SubsitesPageExtension;
use Restruct\Silverstripe\BlockBase\Tests\Stub\TestPage;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;

/**
 * Needs silverstripe/subsites (suggested, not required); skipped without it.
 */
class SubsitesPageExtensionTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected static $extra_dataobjects = [
        TestPage::class,
    ];

    protected static $required_extensions = [
        TestPage::class => [
            ElementalPageExtension::class,
            ContentBlocksToggleExtension::class,
            SubsitesPageExtension::class,
        ],
    ];

    # Class names as strings: the package may not be installed
    private const SUBSITE_STATE = 'SilverStripe\\Subsites\\State\\SubsiteState';

    protected function setUp(): void
    {
        if (!class_exists(self::SUBSITE_STATE)) {
            // Before parent::setUp(): $required_extensions would apply an extension whose hooks
            // reference subsites classes
            $this->markTestSkipped('silverstripe/subsites is not installed');
        }
        parent::setUp();
    }

    private function inSubsite(int $id, callable $callback)
    {
        // Elemental 6 caches the available types per page class for the rest of the process, keyed on
        // the class only, so a list computed for one subsite would be served for another. Reset it on
        // the way in and out (the reset() method does not exist in elemental 5, which has no cache).
        $reset = function () {
            if (method_exists(ElementalAreasExtension::class, 'reset')) {
                ElementalAreasExtension::reset();
            }
        };
        $state = call_user_func([self::SUBSITE_STATE, 'singleton']);
        $reset();
        try {
            return $state->withState(function ($state) use ($id, $callback) {
                $state->setSubsiteId($id);
                return $callback();
            });
        } finally {
            $reset();
        }
    }

    public function testMainSiteKeepsEveryType()
    {
        Config::modify()->set(TestPage::class, 'subsites_allowed_elements', false);
        $types = TestPage::create()->getElementalTypes();
        $this->assertArrayHasKey(BlockContent::class, $types);
    }

    public function testFalseAllowsNoTypesOnASubsite()
    {
        Config::modify()->set(TestPage::class, 'subsites_allowed_elements', false);
        $types = $this->inSubsite(1, fn () => TestPage::create()->getElementalTypes());
        $this->assertSame([], $types);
    }

    public function testAllowListLimitsTheTypesOnASubsite()
    {
        Config::modify()->set(TestPage::class, 'subsites_allowed_elements', [BlockContent::class]);
        $types = $this->inSubsite(1, fn () => TestPage::create()->getElementalTypes());
        $this->assertSame([BlockContent::class], array_keys($types));
    }

    public function testDenyListRemovesTypesOnASubsite()
    {
        $all = TestPage::create()->getElementalTypes();
        $this->assertArrayHasKey(BlockContent::class, $all);

        Config::modify()->set(TestPage::class, 'subsites_disallowed_elements', [BlockContent::class]);
        $types = $this->inSubsite(1, fn () => TestPage::create()->getElementalTypes());
        $this->assertArrayNotHasKey(BlockContent::class, $types);
        $this->assertCount(count($all) - 1, $types);
    }

    public function testBlockEditorIsRemovedWhenNoTypesAreAllowed()
    {
        $page = TestPage::create();
        $page->write();
        $fields = $page->getCMSFields();
        $this->assertNotNull($fields->dataFieldByName('ElementalArea'));

        Config::modify()->set(TestPage::class, 'subsites_allowed_elements', false);
        $fields = $this->inSubsite(1, fn () => $page->getCMSFields());
        $this->assertNull($fields->dataFieldByName('ElementalArea'));
        $this->assertNull($fields->dataFieldByName('ContentBlocksToggle'));
    }

    public function testBlockEditorStaysWhileBlocksRemain()
    {
        $page = TestPage::create();
        $page->write();
        // Existing blocks must stay removable after a config change
        $area = $page->ElementalArea();
        $area->write();
        $page->ElementalAreaID = $area->ID;
        $page->write();
        ElementContent::create(['ParentID' => $area->ID])->write();
        $this->assertSame(1, $page->ElementalArea()->Elements()->count());

        Config::modify()->set(TestPage::class, 'subsites_allowed_elements', false);
        $fields = $this->inSubsite(1, fn () => $page->getCMSFields());
        $this->assertNotNull($fields->dataFieldByName('ElementalArea'));
    }
}
