<?php

namespace Restruct\Silverstripe\BlockBase\Tests;

use DNADesign\Elemental\Extensions\ElementalPageExtension;
use DNADesign\Elemental\Models\BaseElement;
use Page;
use Restruct\Silverstripe\BlockBase\Admin\BlockAdmin;
use Restruct\Silverstripe\BlockBase\Blocks\BlockContent;
use SilverStripe\Dev\FunctionalTest;

class BlockAdminTest extends FunctionalTest
{
    protected $usesDatabase = true;

    public function testManagesAllBlocks()
    {
        $this->assertSame(
            ['block' => ['dataClass' => BaseElement::class, 'title' => 'Blocks']],
            BlockAdmin::config()->get('managed_models')
        );
        $this->assertSame('blocks-admin', BlockAdmin::config()->get('url_segment'));
    }

    public function testListAndEditFormShowWhereABlockIsUsed()
    {
        // The page type the TreeDropdownField is built for is the first elemental page type
        $this->assertTrue(Page::has_extension(ElementalPageExtension::class));

        $page = Page::create(['Title' => 'Page with a block', 'URLSegment' => 'page-with-a-block']);
        $page->write();
        // A new page's area is not written until something writes it, and adding to the relation of
        // an unsaved area writes nothing
        $area = $page->ElementalArea();
        $area->write();
        $page->ElementalAreaID = $area->ID;
        $page->write();
        $block = BlockContent::create(['Title' => 'Listed block', 'Heading' => 'Listed', 'ParentID' => $area->ID]);
        $block->write();

        $this->logInWithPermission('ADMIN');
        $list = $this->get('admin/blocks-admin');
        $this->assertSame(200, $list->getStatusCode());
        $this->assertStringContainsString('Listed block', $list->getBody());
        // Column added by getGridFieldConfig(): the page the block is used on
        $this->assertStringContainsString('col-Parent-OwnerPage-Link', $list->getBody());

        // Find the item's edit link in the list rather than predicting the GridField's name
        $this->assertSame(1, preg_match('#"([^"]*/item/' . $block->ID . '/edit)[^"]*"#', $list->getBody(), $m));
        $edit = $this->get(html_entity_decode($m[1]));
        $this->assertSame(200, $edit->getStatusCode());
        $body = $edit->getBody();
        $this->assertStringContainsString('Linked to page', $body);
        $this->assertStringContainsString('ID: ' . $page->ID . ' (', $body);
        // The page's own CMS edit link. BlockAdmin calls getCMSEditLink() on 6 and CMSEditLink() on 5, but
        // this does not show which one ran: 6 keeps CMSEditLink() as a deprecated wrapper with the same URL
        $this->assertStringContainsString('admin/pages/edit/show/' . $page->ID, $body);
        // Settings fields moved to the main tab under a header
        $this->assertStringContainsString('SettingsHeader', $body);
    }
}
