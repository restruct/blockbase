<?php

namespace Restruct\Silverstripe\BlockBase\Extensions;

use Restruct\Silverstripe\AdminTweaks\Helpers\GeneralHelpers;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;

class ContentBlocksToggleExtension
    extends DataExtension
{
    private static $db = [
        "ContentBlocksToggle" => "Enum('content_blocks,content,blocks','content_blocks')",
    ];

    public function updateFieldLabels(&$labels)
    {
        $labels = array_merge(
            $labels,
            [
                'ContentBlocksToggle' => _t(__CLASS__.'.ContentBlocksToggle', 'Show/use content, blocks, or both'),
                'ContentBlocksToggle_descr' => _t(__CLASS__.'.ContentBlocksToggle_descr', '(Save after changing to show/hide relevant input fields)'),
                'ContentBlocksToggle_content_blocks' => _t(__CLASS__.'.ContentBlocksToggle_content_blocks', 'Content field followed by blocks'),
                'ContentBlocksToggle_content' => _t(__CLASS__.'.ContentBlocksToggle_content', 'Content field only (do not use blocks)'),
                'ContentBlocksToggle_blocks' => _t(__CLASS__.'.ContentBlocksToggle_blocks', 'Blocks only (do not use regular Content field)'),
            ]
        );
    }

    public function updateCMSFields(FieldList $fields)
    {
        // Content/Blocks layout switching (Content field may not exist on some pagetypes like redirectorpage)
        if($fields->dataFieldByName('Content')){
            $opts = $this->owner->dbObject("ContentBlocksToggle")->enumValues();
            $translatedOpts = GeneralHelpers::get_options_translations($opts, self::class . '.ContentBlocksToggle_');

            $fields->insertBefore(
                'Content',
                DropdownField::create('ContentBlocksToggle', $this->owner->fieldLabel('ContentBlocksToggle'))
                    ->setSource($translatedOpts)
                    ->setDescription($this->owner->fieldLabel('ContentBlocksToggle_descr'))
            );
        }

        // Show/hide fields based on selection
        if($this->owner->ContentBlocksToggle == 'content'){
            $fields->removeByName('ElementalArea');
        }
        if($this->owner->ContentBlocksToggle == 'blocks'){
            $fields->removeByName('Content');
        }
    }
}
