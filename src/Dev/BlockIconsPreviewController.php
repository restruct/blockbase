<?php

namespace Restruct\Silverstripe\BlockBase\Dev;

use DNADesign\Elemental\Models\BaseElement;
use Restruct\Silverstripe\BlockBase\Blocks\BlockBase;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
// ArrayList moved in Silverstripe 6 (ORM\ArrayList -> Model\List\ArrayList) with no alias left behind, so
// the class is resolved per major in index() instead of imported:
//use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\Requirements;
// SSViewer::fromString() is gone in Silverstripe 6; the inline template now lives in
// templates/Restruct/Silverstripe/BlockBase/Dev/BlockIconsPreview.ss
//use SilverStripe\View\SSViewer;

/**
 * Class CRMController
 */
class BlockIconsPreviewController
    extends LeftAndMain
{
    private static $url_segment = 'admin/blocktypeicons';

    private static $allowed_actions = [
        'index' => 'CMS_ACCESS_CMSMain',
    ];

    # A dev helper, not a CMS section. Because it is a LeftAndMain with a url_segment, CMSMenu would
    # otherwise list it (titled with its raw class name) and AdminRootController would route it at
    # admin/admin/blocktypeicons in EVERY environment; only the Director rule in _config/config.yml is
    # dev-gated. So: no menu item, and init() refuses the request outside dev.
    private static $ignore_menuitem = true;

//    private static $segment = 'BlockTypeIconsPreview';
//    protected $title = 'Preview icons of all Block-Types';
//    protected $description = 'Helper to preview block-icons and get crops right';

    // Previous version, kept for reference. Its signature was never compatible with LeftAndMain::index()
    // (typed HTTPRequest, returns HTTPResponse, on admin 2 and 3 alike), which fataled every flush, and
    // Block::class resolved to the non-existent Restruct\Silverstripe\BlockBase\Dev\Block, so no block
    // type would have been listed.
//    public function index($request)
//    {
//        $blockTypes = [];
//        $blockClasses = ClassInfo::subclassesFor(Block::class, false);
//        /** @var BlockBase $blockClass */
//        $i = 0;
//        foreach ($blockClasses as $blockClass) {
//            $i++;
//            $blockTypes[] = DataObject::singleton($blockClass)
//                ->customise([
//                    'IconClass' => Config::inst()->get($blockClass, 'icon'),
//                    'LastItem' => $i==count($blockClasses) ? 'last': '',
//                ]);
//        };
//
////        Requirements::css('app/client/dist/css/app-cms-tweaks.css');
//
//        $html = $this
//            ->customise([
//                'BlockTypes' => ArrayList::create( $blockTypes ),
//            ])
//            ->renderWith(SSViewer::fromString('...'));  // inline template, moved verbatim to BlockIconsPreview.ss
//
//        return Requirements::includeInHTML($html);
//    }

    protected function init()
    {
        # Checked BEFORE LeftAndMain::init(), so a live/test site answers 404 rather than a login
        # redirect that would reveal the page exists (both routes: the dev-only Director rule, which is
        # present whenever the config manifest was built in dev, and AdminRootController's url_segment)
        if (!Director::isDev()) {
            $this->httpError(404);
        }
        parent::init();
    }

    public function index(HTTPRequest $request): HTTPResponse
    {
        $blockTypes = [];
        // Every block type: elemental's own, this module's and the project's
        $blockClasses = ClassInfo::subclassesFor(BaseElement::class, false);
        /** @var BlockBase $blockClass */
        $i = 0;
        foreach ($blockClasses as $blockClass) {
            $i++;
            $block = DataObject::singleton($blockClass);
            $blockTypes[] = $block
                ->customise([
                    'IconClass' => Config::inst()->get($blockClass, 'icon'),
                    'LastItem' => $i==count($blockClasses) ? 'last': '',
                    // getDescription() no longer exists on elemental 6 (ElementBlockBaseExtension)
                    'Description' => $block->getBlockDescription(),
                ]);
        };

//        Requirements::css('app/client/dist/css/app-cms-tweaks.css');

        // Class names as strings, without a leading backslash, so neither needs an import
        $listClass = class_exists('SilverStripe\\Model\\List\\ArrayList')
            ? 'SilverStripe\\Model\\List\\ArrayList'  # Silverstripe 6
            : 'SilverStripe\\ORM\\ArrayList';        # Silverstripe 5

        $html = $this
            ->customise([
                'BlockTypes' => $listClass::create( $blockTypes ),
            ])
            ->renderWith('Restruct\\Silverstripe\\BlockBase\\Dev\\BlockIconsPreview');

        return HTTPResponse::create(Requirements::includeInHTML((string) $html));
    }
}
