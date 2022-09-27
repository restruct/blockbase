<?php

namespace Restruct\Silverstripe\BlockBase\Blocks;

// Only load if dnadesign/silverstripe-elemental exists (prevent 'Class not found' build-error):
//if (!class_exists('DNADesign\Elemental\Models\BaseElement')) return;
if ( ! \SilverStripe\Core\ClassInfo::exists('DNADesign\Elemental\Models\BaseElement') ) return;

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLVarchar;

class BlockContent
    extends BlockBase
{
    private static $table_name = 'BlockContent';

    // Override .font-icon-block-layout
    // (for additional icons see https://gbaumeister.github.io/ss4-icons/)
    private static $icon = 'font-icon-block-content';

    private static $description = 'Text/Content';

    /**
     * @config bool enable/disable attributes on a project basis, can also be overridden in subclasses
     */
    private static $has_heading = true;
    private static $has_introline = true;
    private static $has_image = true;
    private static $image_upload_dir = null;
    private static $has_content = true;
    private static $has_bg_image = true;

    public function FieldEnabled($field = 'IntroLine')
    {
        switch ($field) {
            case 'Heading': return $this->config()->get('has_heading');
            case 'IntroLine': return $this->config()->get('has_introline');
            case 'Content': return $this->config()->get('has_image');
            case 'Image': return $this->config()->get('has_content');
            case 'BackgroundImage': return $this->config()->get('has_bg_image');
        }

        return true;
    }

    private static $db = [
        // NOTE: BaseElement.Title retitled to Description (for internal use only), adding Heading instead
        'Heading' => 'Varchar(255)',
        // NOTE: ElementContent (extends BaseElement) adds 'HTML' => 'HTMLText' (but we replace ElementContent with ContentBlockBase)
        'IntroLine' => 'Text',
        'Content' => 'HTMLText',
    ];

    private static $has_one = [
        'Image'           => Image::class,
        'BackgroundImage' => Image::class,
//        'ElementData'     => ElementData::class,
//        'BlockLink'       => Link::class,
    ];

    private static $owns = [
        'Image',
        'BackgroundImage',
    ];

    public function fieldLabels($includerelations = true)
    {
        return array_merge(
            parent::fieldLabels($includerelations),
            [
                'Heading' => _t(__CLASS__.'.Heading', 'Titel'),
                'Image' => _t(__CLASS__.'.Image', 'Afbeelding'),
                'BackgroundImage' => _t(__CLASS__.'.BackgroundImage', 'Achtergrondafbeelding'),
            ]
        );
    }

    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            // Stuff to do before extensions get applied
        });

        $fields = parent::getCMSFields();

        if(!$this->config()->get('has_heading')){
            $fields->removeByName('Heading');
        }

        if(!$this->config()->get('has_introline')){
            $fields->removeByName('IntroLine');
        } elseif($field = $fields->dataFieldByName('IntroLine')) {
            $field->setRows(2);
        }

        if(!$this->config()->get('has_image')){
            $fields->removeByName('Image');
        }

        if(!$this->config()->get('has_bg_image')){
            $fields->removeByName('BackgroundImage');
        }

        if(!$this->config()->get('has_content')){
            $fields->removeByName('Content');
        } else {
            $fields->dataFieldByName('Content')->setRows(20);
        }

        // set uploadfield foldernames
        foreach ($fields->saveableFields() as $field){
            $uploadDir = $this->config()->get('image_upload_dir');
            if($uploadDir && is_a($field, UploadField::class)){
                $field->setFolderName($uploadDir);
            }
        }

        return $fields;
    }

    public function getSummary()
    {
        return DBField::create_field('HTMLText', "<h2>{$this->Heading}</h2> {$this->Content}")->Summary(20);
    }
}
