<?php

namespace Restruct\Silverstripe\BlockBase\Tests;

use Restruct\Silverstripe\BlockBase\Blocks\BlockContent;
use SilverStripe\Control\Director;
use SilverStripe\Dev\FunctionalTest;

class BlockIconsPreviewControllerTest extends FunctionalTest
{
    # FunctionalTest logs out in setUp(), which queries session-manager's table when it is installed
    protected $usesDatabase = true;

    protected function setUp(): void
    {
        parent::setUp();
        if (!Director::isDev()) {
            $this->markTestSkipped('The admin/blocktypeicons route is only configured in dev');
        }
    }

    /**
     * Regression: index() did not match LeftAndMain::index() (a fatal on every flush), and it listed
     * subclasses of a class that does not exist, so no block type would have been shown.
     */
    public function testListsEveryBlockTypeWithItsIconAndDescription()
    {
        $this->logInWithPermission('ADMIN');
        $response = $this->get('admin/blocktypeicons');
        $this->assertSame(200, $response->getStatusCode());

        $body = $response->getBody();
        $this->assertStringContainsString('<title>Block-Type Icons</title>', $body);
        $this->assertStringContainsString(htmlspecialchars(BlockContent::class), $body);
        $this->assertStringContainsString('font-icon-block-content', $body);
        $this->assertStringContainsString('<strong>Text/Content</strong>', $body);
        // elemental's own types are listed too
        $this->assertStringContainsString(htmlspecialchars('DNADesign\\Elemental\\Models\\ElementContent'), $body);
    }

    public function testNeedsCmsAccess()
    {
        $response = $this->get('admin/blocktypeicons');
        $this->assertStringNotContainsString('<title>Block-Type Icons</title>', $response->getBody());
    }
}
