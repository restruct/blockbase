<?php

namespace Restruct\Silverstripe\BlockBase\Tests;

use Restruct\Silverstripe\BlockBase\Blocks\BlockContent;
use SilverStripe\Dev\SapphireTest;

/**
 * ElementVirtualExtension::LinkedElementRelation() and its onBeforeWrite() transfer: the path a TagField
 * picker saved through (the TagField is commented out in updateCMSFields(); the DropdownField writes
 * LinkedElementID directly). Needs dnadesign/silverstripe-elemental-virtual; skipped without it.
 */
class ElementVirtualLinkedElementRelationTest extends SapphireTest
{
    protected $usesDatabase = true;

    # Class name as a string: the package may not be installed
    private const VIRTUAL = 'DNADesign\\ElementalVirtual\\Model\\ElementVirtual';

    protected function setUp(): void
    {
        if (!class_exists(self::VIRTUAL)) {
            $this->markTestSkipped('dnadesign/silverstripe-elemental-virtual is not installed');
        }
        parent::setUp();
    }

    public function testRelationStartsWithTheCurrentLinkedElement()
    {
        $original = BlockContent::create(['Title' => 'Original']);
        $original->write();

        $virtual = singleton(self::VIRTUAL)->create(['LinkedElementID' => $original->ID]);
        $this->assertSame([$original->ID], array_map('intval', $virtual->LinkedElementRelation()->column('ID')));
    }

    public function testWriteTransfersTheRelationToLinkedElementID()
    {
        $original = BlockContent::create(['Title' => 'Original']);
        $original->write();

        $virtual = singleton(self::VIRTUAL)->create();
        # What a TagField's saveInto() does with the intermediary list
        $virtual->LinkedElementRelation()->setByIDList([$original->ID]);
        $virtual->write();

        $this->assertSame($original->ID, (int) $virtual->LinkedElementID);
    }
}
