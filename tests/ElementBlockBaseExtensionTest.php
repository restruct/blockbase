<?php

namespace Restruct\Silverstripe\BlockBase\Tests;

use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Models\ElementalArea;
use DNADesign\Elemental\Models\ElementContent;
use Restruct\Silverstripe\BlockBase\Blocks\BlockBase;
use Restruct\Silverstripe\BlockBase\Blocks\BlockContent;
use Restruct\Silverstripe\BlockBase\Extensions\ElementBlockBaseExtension;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\DropdownField;

class ElementBlockBaseExtensionTest extends SapphireTest
{
    protected $usesDatabase = true;

    public function testAppliedToEveryElement()
    {
        $this->assertTrue(BaseElement::has_extension(ElementBlockBaseExtension::class));
        $this->assertTrue(BlockContent::singleton()->hasExtension(ElementBlockBaseExtension::class));
    }

    public function testBaseTypesCannotBeCreated()
    {
        $this->logInWithPermission('ADMIN');
        foreach ([BaseElement::class, ElementContent::class, BlockBase::class] as $class) {
            $this->assertFalse($class::singleton()->canCreate(), "$class should be hidden from the add-block menu");
        }
        $this->assertTrue(BlockContent::singleton()->canCreate());
    }

    /**
     * Regression: onBeforeWrite() called getDescription(), which elemental 6 removed, so every new
     * block without a Title threw on write.
     */
    public function testNewBlockWithoutTitleIsNamedAfterItsType()
    {
        $block = BlockContent::create();
        $block->write();
        $this->assertSame('Text/Content Block', $block->Title);

        $named = BlockContent::create(['Title' => 'My own title']);
        $named->write();
        $this->assertSame('My own title', $named->Title);
    }

    public function testBlockDescriptionFallsBackToTheTypeName()
    {
        $this->assertSame('Text/Content', BlockContent::singleton()->getBlockDescription());
        // No own class_description: the (singular) type name, never an empty string
        $this->assertSame(BlockBase::singleton()->getType(), BlockBase::singleton()->getBlockDescription());
        $this->assertNotSame('', BlockBase::singleton()->getBlockDescription());

        // An element outside BlockBase, whose getType() is its singular name, still reports its
        // class description (elemental's ElementContent has both, and they differ)
        $content = ElementContent::singleton();
        $this->assertNotEmpty($content->i18n_classDescription());
        $this->assertNotSame($content->getType(), $content->i18n_classDescription());
        $this->assertSame($content->i18n_classDescription(), $content->getBlockDescription());
    }

    public function testNewBlockIsNotAvailableGlobally()
    {
        if (!BaseElement::singleton()->hasField('AvailableGlobally')) {
            $this->markTestSkipped('dnadesign/silverstripe-elemental-virtual is not installed');
        }
        // elemental-virtual defaults new blocks to globally available; the workaround turns that off
        $block = BlockContent::create(['AvailableGlobally' => 1]);
        $block->write();
        $this->assertSame(0, (int) BlockContent::get()->byID($block->ID)->AvailableGlobally);

        // ...only on creation: an existing block keeps what the editor chose
        $block->AvailableGlobally = 1;
        $block->write();
        $this->assertSame(1, (int) BlockContent::get()->byID($block->ID)->AvailableGlobally);
    }

    public function testBlockSchemaSummarisesTheBlock()
    {
        $block = BlockContent::create(['Heading' => 'Schema heading', 'Content' => '<p>Body</p>']);
        $block->write();
        $schema = $block->getBlockSchema();
        $this->assertStringStartsWith('Text/Content block – “', $schema['content']);
        $this->assertStringContainsString('Schema heading', $schema['content']);
    }

    public function testExtraClassIsRemovedWithoutStyleOptions()
    {
        $fields = BlockContent::create()->getCMSFields();
        $this->assertNull($fields->dataFieldByName('ExtraClass'));
    }

    public function testStyleOptionsMakeExtraClassADropdownOnTheMainTab()
    {
        Config::modify()->set(BlockContent::class, 'style_options', ['light' => 'Light', 'dark' => 'Dark']);
        $fields = BlockContent::create()->getCMSFields();
        $field = $fields->dataFieldByName('ExtraClass');
        $this->assertInstanceOf(DropdownField::class, $field);
        $this->assertSame(['light' => 'Light', 'dark' => 'Dark'], $field->getSource());
        $this->assertNotNull($fields->fieldByName('Root.Main')->fieldByName('ExtraClass'));
    }

    public function testStyleDropdownHasNoEmptyOptionAndSitsOnTheMainTab()
    {
        Config::modify()->set(BlockContent::class, 'styles', ['wide' => 'Wide', 'narrow' => 'Narrow']);
        $fields = BlockContent::create()->getCMSFields();
        $style = $fields->dataFieldByName('Style');
        $this->assertInstanceOf(DropdownField::class, $style);
        $this->assertFalse($style->getHasEmptyDefault());
        $this->assertNotNull($fields->fieldByName('Root.Main')->fieldByName('Style'));
        $this->assertSame('Layout style', $style->Title());
    }

    public function testTitleIsRelabelledAsInternalDescription()
    {
        $fields = BlockContent::create()->getCMSFields();
        $this->assertStringContainsString('(intern)', (string) $fields->dataFieldByName('Title')->Title());
    }

    public function testBlockHolderClassesRenderOnOneLine()
    {
        $block = BlockContent::create(['Style' => 'wide', 'ExtraClass' => 'dark']);
        $block->write();
        $classes = $block->BlockHolderClasses();
        $this->assertStringNotContainsString("\n", $classes);
        $this->assertStringContainsString('block-item-holder block-outer', $classes);
        $this->assertStringContainsString('block-outer_BlockContent', $classes);
        $this->assertStringContainsString('wide', $classes);
        $this->assertStringContainsString('dark', $classes);
    }

    public function testHolderTemplateWrapsTheBlock()
    {
        $area = ElementalArea::create();
        $area->write();
        $block = BlockContent::create(['Heading' => 'In a holder', 'ParentID' => $area->ID]);
        $block->write();

        // The holder is rendered by the element's controller, with controller_template: BlockHolder
        // -> templates/DNADesign/Elemental/Layout/BlockHolder.ss
        $html = (string) $block->getController()->forTemplate();
        $this->assertStringContainsString('id="' . $block->getAnchor() . '"', $html);
        $this->assertStringContainsString('block-outer_BlockContent', $html);
        $this->assertStringContainsString('pos-1', $html);
        $this->assertStringContainsString('<h2>In a holder</h2>', $html);
    }

    public function testPathNamesPageTitleAndType()
    {
        $block = BlockContent::create(['Title' => 'Orphan']);
        $block->write();
        $this->assertSame('(-) Orphan [type: Text/Content]', $block->getPath());
    }
}
