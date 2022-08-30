<?php

namespace Restruct\Silverstripe\BlockBase\Extensions;

use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Models\ElementContent;
use Restruct\Silverstripe\BlockBase\Blocks\BlockBase;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\ListboxField;
use SilverStripe\Forms\MultiSelectField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\SelectionGroup;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\FieldType\DBHTMLVarchar;
use SilverStripe\TagField\StringTagField;

class ElementBlockBaseExtension
    extends DataExtension
{
    //
    // DISABLE SOME BLOCKTYPES
    //
    private static $disabled_types = [
        BaseElement::class,
        ElementContent::class,
        BlockBase::class,
    ];

    public function canCreate($member)
    {
        // Effectively 'hide' specific Elemental types & prevent them from being created
        if(in_array($this->owner->ClassName, self::$disabled_types)) {
            return false;
        }

        return null;
    }

    //
    // Other Elemental/Blocks enhancements
    //
    /**
     * @config
     * @var array of css-class values
     */
    private static $style_options = [];

    public function updateFieldLabels(&$labels)
    {
        $labels = array_merge(
            $labels,
            [
                'Title' => DBHTMLVarchar::create()
                    ->setValue(_t(__CLASS__.'.Title', 'Omschrijving/referentie <small>(intern)</small>')),
                'Style' => _t(__CLASS__.'.Style', 'Layout style'),
                'ExtraClass' => _t(__CLASS__.'.ExtraClass', 'Styling/variant'),
                'AvailableGlobally' => _t(__CLASS__.'.AvailableGlobally',
                    'Maak dit blok beschikbaar voor plaatsing op andere paginas (als ‘Virtueel Blok’)'),
            ]
        );
    }

    public function updateCMSFields(FieldList $fields)
    {
        if($classOpts = $this->owner->config()->get('style_options')) {
            $fields->replaceField(
                'ExtraClass',
//                StringTagField::create('ExtraClass') // Works but contains unpatched bug & doesnt handle key-value options
//                ListboxField::create('ExtraClass') // Not react-ive yet it seems (at least doesnt render within Elemental UI)
//                CheckboxSetField::create('ExtraClass') // OK but a bit unfriendly
//                    ->setSource(array_unique(array_merge($classOpts, explode(',',$this->owner->ExtraClass))))
//                    ->setCanCreate(true)
//                OptionsetField::create('ExtraClass') // Saves as string
                DropdownField::create('ExtraClass') // Limit to just one for now, saves as array with string...
                    ->setSource($classOpts)
            );
        } else {
            $fields->removeByName('ExtraClass');
        }

        // Clear emptyvalue from 'Style' (labeled 'Layout')
        /** @var DropdownField $classDrd */
        if($classDrd = $fields->dataFieldByName('Style')){
            $emptyString = $classDrd->getEmptyString();
            $classDrd->setHasEmptyDefault(false);
        }

        // Move 'settings' fields to Main tab
        foreach([ 'Style', 'ExtraClass', 'AvailableGlobally' ] as $fieldName){
            if($field = $fields->dataFieldByName($fieldName)){
                $fields->addFieldToTab('Root.Main', $field);
            }
        }

        // auto-add descriptions
        foreach($this->owner->fieldLabels() as $fieldName => $fieldLabel){
            if($field = $fields->dataFieldByName($fieldName)){
                $field->setTitle($fieldLabel);
                if($this->owner->fieldLabel("{$fieldName}_descr") !== FormField::name_to_label("{$fieldName}_descr")) {
                    $field->setDescription($this->owner->fieldLabel("{$fieldName}_descr"));
                }
            }
        }
    }

    public function BlockHolderClasses()
    {
        return str_replace("\n", ' ', $this->owner->renderWith('BlockHolderClasses'));
    }

    public function getPath()
    {
        $Page = $this->owner->getPage();
        $path = $Page && $Page->ID ? $Page->Link() : '-';

        return "({$path}) {$this->owner->Title} [type: {$this->owner->getType()}]";
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        // Workaround VirtualElement bug https://github.com/dnadesign/silverstripe-elemental-virtual/issues/42
        if (!$this->owner->ID) {
            $this->owner->AvailableGlobally = 0;
        }

        if (!$this->owner->Title) {
            $this->owner->Title = $this->owner->getDescription() . " Block";
        }
    }

    /**
     * Extension hook to update  block schema data, which will be serialised and sent via GraphQL to the editor client.
     */
    public function updateBlockSchema( &$blockSchema )
    {
        // Summary takes content and/or fileUrl/fileTitle props, see:
        // https://github.com/silverstripe/silverstripe-elemental/blob/4/client/src/components/ElementEditor/Summary.js
        $blockSchema['content'] = "{$this->owner->getDescription()} block – “{$this->owner->getSummary()}…”";
    }
}
