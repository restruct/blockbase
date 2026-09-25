<?php

namespace Restruct\Silverstripe\BlockBase\Tests;

use DNADesign\Elemental\Extensions\ElementalPageExtension;
use Page;
use Restruct\Silverstripe\BlockBase\Extensions\ContentBlocksToggleExtension;
use Restruct\Silverstripe\BlockBase\Tests\Stub\TestPage;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\DropdownField;

class ContentBlocksToggleExtensionTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected static $extra_dataobjects = [
        TestPage::class,
    ];

    protected static $required_extensions = [
        TestPage::class => [
            ElementalPageExtension::class,
            ContentBlocksToggleExtension::class,
        ],
    ];

    public function testModuleConfigAppliesItToPage()
    {
        $this->assertTrue(Page::has_extension(ContentBlocksToggleExtension::class));
        $this->assertTrue(Page::has_extension(ElementalPageExtension::class));
    }

    public function testToggleFieldDefaultsToContentAndBlocks()
    {
        $db = TestPage::singleton()->config()->get('db');
        $this->assertStringStartsWith('Enum(', $db['ContentBlocksToggle']);
        // The Enum default is applied by the database, so read the page back
        $page = TestPage::create();
        $page->write();
        $this->assertSame('content_blocks', TestPage::get()->byID($page->ID)->ContentBlocksToggle);
    }

    public function testToggleIsInsertedDirectlyBeforeContent()
    {
        $page = TestPage::create();
        $page->write();
        $fields = $page->getCMSFields();

        $toggle = $fields->dataFieldByName('ContentBlocksToggle');
        $this->assertInstanceOf(DropdownField::class, $toggle);
        $this->assertSame(
            [
                'content_blocks' => 'Content field followed by blocks',
                'content' => 'Content field only (do not use blocks)',
                'blocks' => 'Blocks only (do not use regular Content field)',
            ],
            $toggle->getSource()
        );
        $this->assertSame('Show/use content, blocks, or both', $toggle->Title());

        $names = array_map(fn ($f) => $f->getName(), $fields->fieldByName('Root.Main')->Fields()->toArray());
        $this->assertSame(
            array_search('Content', $names) - 1,
            array_search('ContentBlocksToggle', $names),
            'ContentBlocksToggle should sit directly before Content'
        );
        // keep_content_fields: both editors are there
        $this->assertNotNull($fields->dataFieldByName('Content'));
        $this->assertNotNull($fields->dataFieldByName('ElementalArea'));
    }

    public function testContentOnlyHidesTheBlocksEditor()
    {
        $page = TestPage::create(['ContentBlocksToggle' => 'content']);
        $page->write();
        $fields = $page->getCMSFields();
        $this->assertNull($fields->dataFieldByName('ElementalArea'));
        $this->assertNotNull($fields->dataFieldByName('Content'));
    }

    public function testBlocksOnlyHidesTheContentField()
    {
        $page = TestPage::create(['ContentBlocksToggle' => 'blocks']);
        $page->write();
        $fields = $page->getCMSFields();
        $this->assertNull($fields->dataFieldByName('Content'));
        $this->assertNotNull($fields->dataFieldByName('ElementalArea'));
    }
}
