<?php

namespace Restruct\Silverstripe\BlockBase\Extensions;

use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Subsites\Model\Subsite;
use SilverStripe\Subsites\State\SubsiteState;

/**
 * @package elemental
 */
class SubsitesPageExtension
    extends DataExtension
{
    /**
     * @config array enable/disable block types (classes) on subsites (and remove UI if none allowed)
     */
    private static $subsites_allowed_elements = null;
    private static $subsites_disallowed_elements = null;

    public function updateAvailableTypesForClass(&$pageClass, &$elementTypes)
    {
        // only apply if on subsite (not on 'main' site)
        if(!SubsiteState::singleton()->getSubsiteId()) {
            return;
        }

        $config = $this->owner->config();
        // apply/limit available block classes (if any)
        $allowed = $config->get('subsites_allowed_elements');
        if ($allowed===false) {
            $elementTypes = [];
        } elseif (is_array($allowed)) {
            $elementTypes = array_intersect_key($elementTypes, array_flip($allowed));
        }
        // remove unavailable block classes
        $disallowed = (array) $config->get('subsites_disallowed_elements');
        if (count($disallowed)) {
            $elementTypes = array_diff_key($elementTypes, array_flip($disallowed));
        }
    }

    public function updateCMSFields(FieldList $fields)
    {
        if(!$this->owner->supportsElemental()){
            return;
        }

        // Remove ElementalArea UI if no blocktypes available/allowed (allow removing existing ones, eg after config change)
        if(count($this->owner->getElementalTypes())==0 && $this->owner->ElementalArea()->Elements()->count()==0){
            $fields->removeByName('ContentBlocksToggle');
            $fields->removeByName('ElementalArea');
        }

        // Move SubsiteOperations below ElementalArea
        $subsiteActions = $fields->fieldByName('Root.Main.SubsiteOperations');
        $elementalArea = $fields->dataFieldByName('ElementalArea');
        if($subsiteActions && $elementalArea) {
            $fields->removeByName('SubsiteOperations');
            $fields->insertAfter('ElementalArea', $subsiteActions);
        }
    }

//    /**
//     * If a page is duplicated across subsites, copy the elements across too
//     *
//     * @return Page The duplicated page
//     */
//    public function onAfterDuplicateToSubsite($originalPage)
//    {
//        /** @var ElementalArea $originalElementalArea */
//        $originalElementalArea = $originalPage->getComponent('ElementalArea');
//
//        $duplicateElementalArea = $originalElementalArea->duplicate(false);
//        $duplicateElementalArea->write();
//
//        $this->owner->ElementalAreaID = $duplicateElementalArea->ID;
//        $this->owner->write();
//
//        foreach ($originalElementalArea->Items() as $originalElement) {
//            /** @var BaseElement $originalElement */
//            $duplicateElement = $originalElement->duplicate(true);
//
//            // manually set the ParentID of each element, so we don't get versioning issues
//            DB::query(
//                sprintf(
//                    "UPDATE %s SET ParentID = %d WHERE ID = %d",
//                    DataObject::getSchema()->tableName(BaseElement::class),
//                    $duplicateElementalArea->ID,
//                    $duplicateElement->ID
//                )
//            );
//        }
//    }
}
