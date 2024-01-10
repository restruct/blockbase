<?php

namespace Restruct\Silverstripe\BlockBase\Blocks;

// Only load if dnadesign/silverstripe-elemental exists (prevent 'Class not found' build-error):
//if (!class_exists('DNADesign\Elemental\Models\BaseElement')) return;
if ( ! \SilverStripe\Core\ClassInfo::exists('DNADesign\Elemental\Models\BaseElement') ) return;

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
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
        // Facility to store free-form data in JSON format -> fields/properties starting with 'ExtraData_' are saved
        // NOTE: only works for plain fields (eg TextField), complexer fields probably need some additional work...
        'ExtraDataJSON' => 'Text',
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

    public function getSummary()
    {
        return DBField::create_field('HTMLText', "<h2>{$this->Heading}</h2> {$this->Content}")->Summary(20);
    }

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

        // SET FOLDER NAMES FOR UPLOADS
        foreach ($fields->saveableFields() as $field){
            $uploadDir = $this->config()->get('image_upload_dir');
            if($uploadDir && is_a($field, UploadField::class)){
                $field->setFolderName($uploadDir);
            }
        }

//        // Some quick ExtraData tests...
//        $fields->addFieldToTab('Root.Main', TextField::create('ExtraData_Test1', 'Test 1'));
//        $fields->addFieldToTab('Root.Main', TextField::create('ExtraData_Test2', 'Test 2'));
//        $fields->addFieldToTab('Root.Main', TextField::create('ExtraData_Test3', 'Test 3'));
//        $fields->addFieldToTab('Root.Main', NumericField::create('ExtraData_Number', 'Numeric data'));
//        $fields->addFieldToTab('Root.Main', TextareaField::create('ExtraData_Textarea', 'Textarea'));
//        $fields->addFieldToTab('Root.Main', DropdownField::create('ExtraData_Select', 'Select', ['Opt 1', 'Opt 2', 'Opt 3']));

        // ExtraData functionality/fields; __get/__set dont seem to get called for fields and
        // as ExtraData fields are not 1:1 related db records we have to manually set their value
        if($ExtraData = $this->getExtraData()) foreach ($fields->saveableFields() as $field){
            if(strpos($field->getName(), 'ExtraData_') === 0) {
                $field->setValue( $ExtraData[str_replace('ExtraData_', '', $field->getName())] ?? null );
            }
        }
        // Remove scaffolded 'RAW' JSON field
        $fields->removeByName('ExtraDataJSON');

        return $fields;
    }

    //
    // EXTRADATA HANDLING
    //
    public function getExtraData()
    {
        return json_decode($this->ExtraDataJSON, JSON_OBJECT_AS_ARRAY);
    }

    // Write any ExtraData_* fields into ExtraDataJSON
    private function prepareExtraDataForWriting()
    {
        $ExtraData = $this->getExtraData(); // retains any existing values
        foreach ($this->record as $key => $value) {
            if(strpos($key, 'ExtraData_') === 0) {
                $ExtraData[str_replace('ExtraData_', '', $key)] = $value;
            }
        }
        $this->ExtraDataJSON = json_encode($ExtraData);
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        // Write any ExtraData_* fields into ExtraDataJSON
        $this->prepareExtraDataForWriting();
    }

}
