<?php

namespace Restruct\Silverstripe\BlockBase\Tests;

use DNADesign\Elemental\Models\ElementalArea;
use Restruct\Silverstripe\BlockBase\Blocks\BlockBase;
use Restruct\Silverstripe\BlockBase\Blocks\BlockContent;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;

class BlockContentTest extends SapphireTest
{
    protected $usesDatabase = true;

    public function testSchemaHasTheBlockFields()
    {
        $db = BlockContent::singleton()->config()->get('db');
        $this->assertSame('Varchar(255)', $db['Heading']);
        $this->assertSame('Text', $db['IntroLine']);
        $this->assertSame('HTMLText', $db['Content']);
        $this->assertSame('Text', $db['ExtraDataJSON']);

        $hasOne = BlockContent::singleton()->config()->get('has_one');
        $this->assertArrayHasKey('Image', $hasOne);
        $this->assertArrayHasKey('BackgroundImage', $hasOne);
        $owns = BlockContent::singleton()->config()->get('owns');
        $this->assertContains('Image', $owns);
        $this->assertContains('BackgroundImage', $owns);
    }

    /**
     * Regression: the block type came from the 'description' config, which elemental 5.4 no longer
     * reads and elemental 6 does not have; getDescription() is gone from elemental 6 altogether.
     */
    public function testTypeIsTheClassDescription()
    {
        $block = BlockContent::create();
        $this->assertSame('Text/Content', $block->i18n_classDescription());
        $this->assertSame('Text/Content', $block->getType());
    }

    public function testTypeFallsBackToSingularNameWithoutDescription()
    {
        // class_description is uninherited, so BlockBase does not see the 'Base' set on BaseElement
        $block = BlockBase::create();
        $this->assertEmpty($block->i18n_classDescription());
        $this->assertSame($block->i18n_singular_name(), $block->getType());
    }

    /**
     * Regression: FieldEnabled('Content') answered has_image and FieldEnabled('Image') has_content.
     */
    public function testFieldEnabledAnswersEachFieldsOwnSetting()
    {
        Config::modify()->set(BlockContent::class, 'has_image', false);
        Config::modify()->set(BlockContent::class, 'has_content', true);
        $block = BlockContent::create();
        $this->assertFalse((bool) $block->FieldEnabled('Image'));
        $this->assertTrue((bool) $block->FieldEnabled('Content'));

        Config::modify()->set(BlockContent::class, 'has_image', true);
        Config::modify()->set(BlockContent::class, 'has_content', false);
        $this->assertTrue((bool) $block->FieldEnabled('Image'));
        $this->assertFalse((bool) $block->FieldEnabled('Content'));

        Config::modify()->set(BlockContent::class, 'has_heading', false);
        Config::modify()->set(BlockContent::class, 'has_introline', false);
        Config::modify()->set(BlockContent::class, 'has_bg_image', false);
        $this->assertFalse((bool) $block->FieldEnabled('Heading'));
        $this->assertFalse((bool) $block->FieldEnabled('IntroLine'));
        $this->assertFalse((bool) $block->FieldEnabled('BackgroundImage'));
        // Anything else is always enabled
        $this->assertTrue($block->FieldEnabled('Something'));
    }

    public function testAllFieldsShowByDefault()
    {
        $fields = BlockContent::create()->getCMSFields();
        foreach (['Heading', 'IntroLine', 'Content', 'Image', 'BackgroundImage'] as $name) {
            $this->assertNotNull($fields->dataFieldByName($name), "$name should be shown");
        }
        $this->assertSame(2, (int) $fields->dataFieldByName('IntroLine')->getRows());
        $this->assertSame(20, (int) $fields->dataFieldByName('Content')->getRows());
        // The raw JSON store is never edited directly
        $this->assertNull($fields->dataFieldByName('ExtraDataJSON'));
    }

    public function testEachHasConfigRemovesOnlyItsOwnField()
    {
        // No data provider: PHPUnit 9 reads @dataProvider, PHPUnit 11 wants an attribute instead
        $map = [
            'has_heading' => 'Heading',
            'has_introline' => 'IntroLine',
            'has_content' => 'Content',
            'has_image' => 'Image',
            'has_bg_image' => 'BackgroundImage',
        ];
        foreach ($map as $config => $field) {
            Config::nest();
            try {
                Config::modify()->set(BlockContent::class, $config, false);
                $fields = BlockContent::create()->getCMSFields();
                $this->assertNull($fields->dataFieldByName($field), "$config: false should remove $field");
                foreach (array_diff($map, [$field]) as $other) {
                    $this->assertNotNull($fields->dataFieldByName($other), "$config: false should keep $other");
                }
            } finally {
                Config::unnest();
            }
        }
    }

    public function testImageUploadDirSetsTheUploadFolder()
    {
        $fields = BlockContent::create()->getCMSFields();
        $this->assertInstanceOf(UploadField::class, $fields->dataFieldByName('Image'));
        $this->assertNotSame('block-images-test', $fields->dataFieldByName('Image')->getFolderName());

        Config::modify()->set(BlockContent::class, 'image_upload_dir', 'block-images-test');
        $fields = BlockContent::create()->getCMSFields();
        $this->assertSame('block-images-test', $fields->dataFieldByName('Image')->getFolderName());
        $this->assertSame('block-images-test', $fields->dataFieldByName('BackgroundImage')->getFolderName());
    }

    public function testExtraDataFieldsAreStoredAsJsonAndLoadedBack()
    {
        $block = BlockContent::create(['Heading' => 'Extra']);
        $block->ExtraData_Colour = 'blue';
        $block->ExtraData_Count = '3';
        $block->write();

        $this->assertSame(['Colour' => 'blue', 'Count' => '3'], json_decode($block->ExtraDataJSON, true));

        $reloaded = BlockContent::get()->byID($block->ID);
        $this->assertSame(['Colour' => 'blue', 'Count' => '3'], $reloaded->getExtraData());

        // getCMSFields() loads the stored values into ExtraData_* so form fields show them
        $reloaded->getCMSFields();
        $this->assertSame('blue', $reloaded->ExtraData_Colour);

        // A later write keeps values that were not touched and updates the ones that were
        $again = BlockContent::get()->byID($block->ID);
        $again->ExtraData_Count = '4';
        $again->write();
        $this->assertSame(
            ['Colour' => 'blue', 'Count' => '4'],
            BlockContent::get()->byID($block->ID)->getExtraData()
        );
    }

    /**
     * Regression: json_decode(NULL) for a block without stored ExtraData, deprecated since PHP 8.1.
     * Neither PHPUnit major fails a test on a deprecation by default, so it is collected here.
     */
    public function testExtraDataWithoutStoredDataRaisesNoDeprecation()
    {
        $block = BlockContent::create();
        $deprecations = [];
        set_error_handler(function ($errno, $errstr) use (&$deprecations) {
            $deprecations[] = $errstr;
            return true;
        }, E_DEPRECATED | E_USER_DEPRECATED);
        try {
            $data = $block->getExtraData();
        } finally {
            restore_error_handler();
        }
        $this->assertNull($data);
        $this->assertSame([], array_values(array_filter($deprecations, fn ($m) => str_contains($m, 'json_decode'))));
    }

    public function testSummaryCombinesHeadingAndContent()
    {
        $block = BlockContent::create(['Heading' => 'Hello', 'Content' => '<p>World of blocks</p>']);
        $summary = (string) $block->getSummary();
        $this->assertStringContainsString('Hello', $summary);
        $this->assertStringContainsString('World of blocks', $summary);
        $this->assertStringNotContainsString('<h2>', $summary);
    }

    public function testRenderTemplatesIncludePlainThemePaths()
    {
        $block = BlockContent::create(['Style' => 'wide']);
        $templates = $block->getRenderTemplates();
        $area = $block->getAreaRelationName();
        foreach ([
            "Blocks\\BlockContent_{$area}_wide",
            'Blocks\\BlockContent_wide',
            "Blocks\\BlockContent_{$area}",
            'Blocks\\BlockContent',
            'BlockContent_wide',
            'BlockContent',
        ] as $expected) {
            $this->assertContains($expected, $templates);
        }
        // Namespaced candidates from elemental come first; the plain ones are fallbacks
        $this->assertGreaterThan(
            array_search(BlockContent::class, $templates),
            array_search('Blocks\\BlockContent', $templates)
        );
    }

    public function testRendersWithTheModuleTemplate()
    {
        $area = ElementalArea::create();
        $area->write();
        $block = BlockContent::create([
            'Heading' => 'Rendered heading',
            'Content' => '<p>Rendered body</p>',
            'ParentID' => $area->ID,
        ]);
        $block->write();

        // Without the holder: templates/Blocks/BlockContent.ss
        $html = (string) $block->forTemplate(false);
        $this->assertStringContainsString('<h2>Rendered heading</h2>', $html);
        $this->assertStringContainsString('<p>Rendered body</p>', $html);
        $this->assertStringContainsString('class="col typography"', $html);
    }
}
