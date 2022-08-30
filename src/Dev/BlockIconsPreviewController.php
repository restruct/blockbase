<?php

namespace Restruct\Silverstripe\BlockBase\Dev;

use Restruct\Silverstripe\BlockBase\Blocks\BlockBase;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\Requirements;
use SilverStripe\View\SSViewer;

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

//    private static $segment = 'BlockTypeIconsPreview';
//    protected $title = 'Preview icons of all Block-Types';
//    protected $description = 'Helper to preview block-icons and get crops right';

    public function index($request)
    {
        $blockTypes = [];
        $blockClasses = ClassInfo::subclassesFor(Block::class, false);
        /** @var BlockBase $blockClass */
        $i = 0;
        foreach ($blockClasses as $blockClass) {
            $i++;
            $blockTypes[] = DataObject::singleton($blockClass)
                ->customise([
                    'IconClass' => Config::inst()->get($blockClass, 'icon'),
                    'LastItem' => $i==count($blockClasses) ? 'last': '',
                ]);
        };

//        Requirements::css('app/client/dist/css/app-cms-tweaks.css');

        $html = $this
            ->customise([
                'BlockTypes' => ArrayList::create( $blockTypes ),
            ])
            ->renderWith(SSViewer::fromString('
<!DOCTYPE html>
<html>
<head>
	<% base_tag %>
	<title>Block-Type Icons</title>
	<style>
	    .element-editor-header__info.last {
	        border-bottom: 1px solid grey;
	    }
	</style>
</head>
<body>
<div class="container">
    <h2 class="row col"><br /><br />BlockTypes:</h2>
    <% loop $BlockTypes %>
    <div class="row col">
        <br /><br />
        <div class="element-editor-header__info $LastItem" style="border-top: 1px solid grey; padding: 1rem 0;">
            <div class="element-editor-header__icon-container">$Icon</div>
            <h3 class="element-editor-header__title"><strong>$Description</strong><br /><code>$ClassName</code></h3>
            <hr />
            <button type="button" class="{$IconClass} btn--icon-xl element-editor-add-element__button popover-option-set__button btn btn-secondary">$Description</button>
        </div>
        <br /><br />
    </div>
    <% end_loop %>
</div>
</body>
</html>
            '));

        return Requirements::includeInHTML($html);
    }
}
