<?php

namespace Restruct\Silverstripe\BlockBase\Extensions;

use Page;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Extension;
// Quick reference;
//use DNADesign\Elemental\Tasks\MigrateContentToElement\MigrateContentToElement;

class ElementContentMigrationExtension
extends Extension
{
    // By reference: MigrateContentToElement passes its variables through extend() by reference, and a
    // by-value parameter here made the assignment below a no-op (every page type stayed migratable)
//    public function updateIsMigratable ($migratable, $pageType)
    /**
     * @param bool $migratable
     * @param string|SiteTree $pageType
     */
    public function updateIsMigratable (&$migratable, $pageType)
    {
        if($pageType !== Page::class){
            $migratable = false;
        }
    }

//    public function updatePageFilter ($pages, $pageType)
//    {
//
//    }

    // By reference, as above: by value, pages that already had blocks were never skipped
//    public function updatePageShouldSkip ($skip, $page)
    public function updatePageShouldSkip (&$skip, $page)
    {
//        // Delete previously migrated block (if any)
//        $pageBlocks = $page->ElementalArea->Elements();
//        if($pageBlocks->count() && $pageBlocks->first()->Title=='Auto migrated content'){
//            $prevBlock = $pageBlocks->first();
//            $prevBlock->doUnpublish();
//            $prevBlock->delete();
//        }
        // Skip if page already has Blocks
        if($page->ElementalArea->Elements()->count()){
            $skip = true;
        }
    }

    public function updateMigratedElement ($element, $content, $page)
    {
        $element->Title .= ': '.$page->Title;
//        $element->AvailableGlobally = false;

        $page->UseLayout = 'Blocks';
        $page->write();
    }
}
