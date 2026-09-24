<?php

namespace Restruct\Silverstripe\BlockBase\Tests;

use Restruct\Silverstripe\BlockBase\Blocks\BlockContent;
use Restruct\Silverstripe\BlockBase\Extensions\ElementVirtualExtension;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\DropdownField;

/**
 * Needs dnadesign/silverstripe-elemental-virtual (suggested, not required); skipped without it.
 */
class ElementVirtualExtensionTest extends SapphireTest
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

    public function testModuleConfigAppliesToVirtualBlocks()
    {
        $virtual = self::VIRTUAL;
        $this->assertTrue($virtual::has_extension(ElementVirtualExtension::class));
        $this->assertSame('BlockHolder', $virtual::config()->get('controller_template'));
        $this->assertTrue($virtual::config()->get('inline_editable'));
        // Renamed from 'description', which elemental 5.4 ignores and elemental 6 does not have
        $this->assertSame('Duplicaat (clone)', $virtual::singleton()->i18n_classDescription());
    }

    public function testPickerOffersOnlyGloballyAvailableBlocks()
    {
        $shared = BlockContent::create(['Title' => 'Shared']);
        $shared->write();
        // The extension switches AvailableGlobally off on creation, so set it on the saved block
        $shared->AvailableGlobally = 1;
        $shared->write();
        $private = BlockContent::create(['Title' => 'Private']);
        $private->write();

        $fields = singleton(self::VIRTUAL)->create()->getCMSFields();
        $picker = $fields->dataFieldByName('LinkedElementID');
        $this->assertInstanceOf(DropdownField::class, $picker);
        $this->assertSame([$shared->ID => $shared->getPath()], $picker->getSource());

        foreach (['Style', 'AvailableGlobally', 'ExtraClass'] as $name) {
            $this->assertNull($fields->dataFieldByName($name), "$name does not apply to a clone");
        }
    }

    public function testPickerExplainsWhatToDoWhenNothingIsShared()
    {
        $fields = singleton(self::VIRTUAL)->create()->getCMSFields();
        $picker = $fields->dataFieldByName('LinkedElementID');
        $this->assertSame([], $picker->getSource());
        $this->assertStringContainsString('beschikbaar', (string) $picker->getDescription());
    }
}
