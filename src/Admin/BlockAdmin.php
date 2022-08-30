<?php

namespace Restruct\Silverstripe\BlockBase\Admin;

// Only load if dnadesign/silverstripe-elemental exists (prevent 'Class not found' build-error):
if ( ! \SilverStripe\Core\ClassInfo::exists('DNADesign\Elemental\Models\BaseElement') ) return;

use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Models\ElementalArea;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest;
use SilverStripe\Forms\GridField\GridFieldPageCount;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\ORM\FieldType\DBHTMLVarchar;

class BlockAdmin
    extends ModelAdmin
{

    /**
     * @var string[]
     */
    private static $managed_models = [
        'block' => [
            'dataClass' => BaseElement::class,
            'title' => 'Blocks'
        ]
    ];

    /**
     * @var string
     */
    private static $url_segment = 'blocks-admin';

    /**
     * @var string
     */
    private static $menu_title = 'Content Blocks';

    protected function getGridFieldConfig(): GridFieldConfig
    {
        $config = parent::getGridFieldConfig();

        if ($this->modelClass === BaseElement::class) {
            $baseElement = BaseElement::singleton();
            $config = new GridFieldConfig_RecordEditor();
            $config->removeComponentsByType(GridFieldPageCount::class);
            $config->getComponentByType(GridFieldPaginator::class)
                ->setItemsPerPage(50);
            $config->getComponentByType(GridFieldDataColumns::class)
                ->setDisplayFields([
                    'Type' => $baseElement->fieldLabel('Type'),
                    'Icon' => $baseElement->fieldLabel('Icon'),
                    'Title' => $baseElement->fieldLabel('Title'),
                    'Parent.OwnerPage.Link' => $baseElement->fieldLabel('UsedOnPage'),
                ]);

            $detailForm = $config->getComponentByType(GridFieldDetailForm::class);
            $detailForm->setItemEditFormCallback(function($form, GridFieldDetailForm_ItemRequest $request) use ($baseElement) {
                /** @var FieldList $fields */
                $fields = $form->Fields();
                $record = $request->getRecord();
                $elArea = $record->Parent();
                $elPage = $elArea ? $elArea->getOwnerPage() : null;

                $fields->addFieldToTab('Root.Main', HeaderField::create('SettingsHeader', $baseElement->fieldLabel('Settings')));
                $fields->addFieldsToTab('Root.Main', $fields->fieldByName('Root.Settings')->Fields());
                $fields->removeByName('Settings');
                $fields->removeByName('History');

                // This only works for the default SiteTree/Page -has-one- ElementArea relation, skip if setup is different
                $elementalPagetypes = ElementalArea::singleton()->supportedPageTypes();
                $firstPageType = count($elementalPagetypes) ? $elementalPagetypes[0] : null;
                if($firstPageType){
                    $fields->addFieldToTab('Root.Main',
                        $treeDrd = TreeDropdownField::create(
                                'ParentID',
                                'Linked to page',
                                $firstPageType,
                                'ElementalAreaID',
                                'MenuTitle'
                            )
                            ->setValue($record->ParentID)
                            ->setForm($form)
                    );
                    if($elPage){
                        $treeDrd->setRightTitle(
                            DBHTMLVarchar::create()->setValue(
                                sprintf("ID: %d (<a href=\"%s\" target=\"blank\">Edit in CMS</a>)", $elPage->ID, $elPage->CMSEditLink())
                            )
                        );
                    }
                }

            });

        }

        return $config;
    }
}

