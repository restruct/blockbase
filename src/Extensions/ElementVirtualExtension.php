<?php

namespace Restruct\Silverstripe\BlockBase\Extensions;

use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Forms\DropdownField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\UnsavedRelationList;

class ElementVirtualExtension
    extends DataExtension
{
    private static $many_many = [
        'LinkedElements' => BaseElement::class,
    ];

    public function updateCMSFields($fields)
    {
        $availableBlocks = BaseElement::get()
            ->exclude('ClassName', get_class($this->owner));

        // Filter to include only AvailableGlobally blocks (and insert currently selected just to be sure)
        $availableBlocks = $availableBlocks
            ->filterAny([
                'AvailableGlobally' => 1,
                'ID' => $this->owner->LinkedElementID ?: 0,
            ]);

        $dropdownSource = $availableBlocks->map('ID', 'Path')->toArray();
        asort($dropdownSource);

        if($availableBlocks->count()){
            $fields->replaceField(
                'LinkedElementID',
                DropdownField::create(
                    "LinkedElementID",
                    $this->owner->fieldLabel('LinkedElement'),
                    $dropdownSource
                )->setEmptyString('(selecteer block om hier virtueel te plaatsen/linken)')
//                TagField::create("LinkedElementRelation", $this->owner->fieldLabel('LinkedElement'), $availableBlocks)
//                    // Bug: TagField (react) setIsMultiple results in empty (https://github.com/silverstripe/silverstripe-tagfield/issues/195)
//    //                ->setIsMultiple(false)
//                    ->setCanCreate(false)
            );
        }

        foreach([ 'Style', 'AvailableGlobally', 'ExtraClass' ] as $fieldName){
            $fields->removeByName($fieldName);
        }
    }

    /**
     * Create an intermediary UnsavedRelationList to have TagField save the LinkedElement into
     * @return UnsavedRelationList
     */
    public function LinkedElementRelation()
    {
        $this->owner->LinkedElementRelation = UnsavedRelationList::create(
            get_class($this->owner),
            'LinkedElementRelation',
            BaseElement::class
        );
        $this->owner->LinkedElementRelation->add($this->owner->LinkedElementID);
        return $this->owner->LinkedElementRelation;
    }

    /**
     * Transfer LinkedElement from UnsavedRelationList to has_one LinkedElementID
     */
    public function onBeforeWrite()
    {
        if($this->owner->LinkedElementRelation && $this->owner->LinkedElementRelation->first()){
            $this->owner->LinkedElementID = $this->owner->LinkedElementRelation->first()->ID;
        }

        parent::onBeforeWrite();
    }
}

