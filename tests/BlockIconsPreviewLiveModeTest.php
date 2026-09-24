<?php

namespace Restruct\Silverstripe\BlockBase\Tests;

use Restruct\Silverstripe\BlockBase\Dev\BlockIconsPreviewController;
use SilverStripe\Admin\CMSMenu;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Kernel;
use SilverStripe\Dev\FunctionalTest;

/**
 * The icon preview is a dev helper. Outside dev it must not be reachable or listed in the CMS menu,
 * although, as a LeftAndMain with a url_segment, AdminRootController routes it in every environment.
 */
class BlockIconsPreviewLiveModeTest extends FunctionalTest
{
    # FunctionalTest logs out in setUp(), which queries session-manager's table when it is installed
    protected $usesDatabase = true;

    /** @var string|null environment to restore */
    private $previousEnvironment;

    protected function setUp(): void
    {
        parent::setUp();
        $kernel = Injector::inst()->get(Kernel::class);
        $this->previousEnvironment = $kernel->getEnvironment();
        # Switched at runtime: the config manifest (and so the dev-only Director rule) stays as built
        $kernel->setEnvironment('live');
    }

    protected function tearDown(): void
    {
        Injector::inst()->get(Kernel::class)->setEnvironment($this->previousEnvironment);
        parent::tearDown();
    }

    public function testNotReachableOutsideDev()
    {
        $this->logInWithPermission('ADMIN');
        # AdminRootController's route from url_segment, present in every environment
        $response = $this->get('admin/admin/blocktypeicons');
        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringNotContainsString('<title>Block-Type Icons</title>', (string) $response->getBody());
        # The Director rule, present here because the test manifest was built in dev
        $response = $this->get('admin/blocktypeicons');
        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringNotContainsString('<title>Block-Type Icons</title>', (string) $response->getBody());
    }

    public function testNotInTheCmsMenu()
    {
        CMSMenu::populate_menu();
        # One assertion over all items, so the count does not depend on how many sections a major has
        $controllers = array_map(fn($item) => $item->controller, CMSMenu::get_menu_items());
        $this->assertNotContains(BlockIconsPreviewController::class, array_values($controllers));
    }
}
