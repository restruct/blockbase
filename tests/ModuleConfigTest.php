<?php

namespace Restruct\Silverstripe\BlockBase\Tests;

use DNADesign\Elemental\Controllers\ElementController;
use DNADesign\Elemental\Extensions\ElementalAreasExtension;
use DNADesign\Elemental\Extensions\ElementalPageExtension;
use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\CMS\Model\VirtualPage;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Manifest\ModuleResourceLoader;
use SilverStripe\Dev\SapphireTest;

/**
 * The module's _config/config.yml: what a project gets just by installing it.
 */
class ModuleConfigTest extends SapphireTest
{
    public function testElementDefaults()
    {
        $config = BaseElement::config();
        $this->assertTrue($config->get('inline_editable'));
        $this->assertFalse($config->get('displays_title_in_template'));
        $this->assertSame('BlockHolder', $config->get('controller_template'));
        // Renamed from 'description', which elemental 5.4 ignores and elemental 6 does not have.
        // (Displayed, elemental's own lang entry for BaseElement.CLASS_DESCRIPTION takes precedence.)
        $this->assertSame('Base', Config::inst()->get(BaseElement::class, 'class_description', Config::UNINHERITED));
        // ...and no longer sets the old key (elemental 5 still declares its own default there)
        $this->assertNotSame('Base', Config::inst()->get(BaseElement::class, 'description', Config::UNINHERITED));
    }

    public function testElementalBehaviour()
    {
        $this->assertTrue(Config::inst()->get(ElementalAreasExtension::class, 'keep_content_fields'));
        $this->assertFalse(ElementController::config()->get('include_default_styles'));

        $ignored = Config::inst()->get(ElementalPageExtension::class, 'ignored_classes');
        $this->assertContains(RedirectorPage::class, $ignored);
        $this->assertContains(VirtualPage::class, $ignored);
    }

    public function testAdminStylesheetIsRequiredAndExists()
    {
        $css = 'restruct/silverstripe-blockbase:client/dist/css/admin-block-tweaks.css';
        $this->assertContains($css, LeftAndMain::config()->get('extra_requirements_css'));
        $this->assertFileExists(Director::getAbsFile(ModuleResourceLoader::singleton()->resolvePath($css)));
    }

    public function testIconPreviewRouteExistsInDev()
    {
        if (!Director::isDev()) {
            $this->markTestSkipped('The route is only configured in dev');
        }
        $this->assertSame(
            'Restruct\\Silverstripe\\BlockBase\\Dev\\BlockIconsPreviewController',
            Director::config()->get('rules')['admin/blocktypeicons'] ?? null
        );
    }
}
