<?php

namespace Restruct\Silverstripe\BlockBase\Blocks;

// Only load if dnadesign/silverstripe-elemental exists (prevent 'Class not found' build-error):
//if (!class_exists('DNADesign\Elemental\Models\BaseElement')) return;
if ( ! \SilverStripe\Core\ClassInfo::exists('DNADesign\Elemental\Models\BaseElement') ) return;

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\ListboxField;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\FieldType\DBHTMLVarchar;
use SilverStripe\ORM\ValidationException;
use SilverStripe\TagField\StringTagField;

/**
 * Base block class for common functionality (project specific blocks can subclass this, comparable to DataObject)
 */
class BlockBase
extends \DNADesign\Elemental\Models\BaseElement
{
    private static $table_name = 'BlockBase';

    /**
     * Get the type of the current block, for use in GridField summaries, block
     * type dropdowns etc. Examples are "Content", "File", "Media", etc.
     *
     * @return string
     */
    public function getType()
    {
        return $this->getDescription();
    }

    /**
     * This can be overridden on child elements to create a summary for display
     * in GridFields.
     *
     * @return string
     */
    public function getSummary()
    {
        return DBField::create_field(DBHTMLText::class, $this->Heading)->Summary(20);
    }

    // Append plain theme-based (non-namespaced) template paths
    public function getRenderTemplates($suffix = '')
    {
        $renderTemplates = parent::getRenderTemplates($suffix);

        $shortClassName = ClassInfo::shortName($this->ClassName);
        foreach(['Blocks\\', ''] as $prefix) {
            if ($style = $this->Style) {
                $renderTemplates[] = "{$prefix}{$shortClassName}" . $suffix . '_'. $this->getAreaRelationName() . '_' . $style;
                $renderTemplates[] = "{$prefix}{$shortClassName}" . $suffix . '_' . $style;
            }
            $renderTemplates[] = "{$prefix}{$shortClassName}" . $suffix . '_'. $this->getAreaRelationName();
            $renderTemplates[] = "{$prefix}{$shortClassName}" . $suffix;
        }

        return $renderTemplates;
    }

    // Check /admin/blocktypeicons/?flush=1 to preview icons (Dev\BlockIconsPreviewController)
//    private static $icon = 'font-icon-block-layout'; // = default defined on \DNADesign\Elemental\Models\BaseElement

//    private static $description = 'Base';

//    private static $singular_name = 'content block';

//    private static $plural_name = 'content blocks';

//    private static $displays_title_in_template = false;

//    private static $inline_editable = true;

//    // BUG: this isn't working... (DNADesign\ElementalVirtual\Extensions\BaseElementExtension)
//    private static $default_global_elements = false;

//    private static $controller_template = 'BlockHolder';

//    /**
//     * @config
//     * @var array of css-class values
//     */
//    private static $extra_classes = [ 'one', 'two' ];



//    public function fieldLabels($includerelations = true)
//    {
//        return array_merge(
//            parent::fieldLabels($includerelations),
//            [
//                'Title' => DBHTMLVarchar::create()
//                    ->setValue(_t(__CLASS__.'.Title', 'Omschrijving/referentie <small>(intern)</small>')),
//                'Heading' => _t(__CLASS__.'.Heading', 'Titel'),
//                'Image' => _t(__CLASS__.'.Image', 'Afbeelding'),
//                'BackgroundImage' => _t(__CLASS__.'.BackgroundImage', 'Achtergrond'),
//                'Style' => _t(__CLASS__.'.Style', 'Layout'),
//                'AvailableGlobally' => _t(__CLASS__.'.AvailableGlobally',
//                    'Maak dit blok beschikbaar voor plaatsing op andere paginas (als ‘Virtueel Blok’)'),
//            ]
//        );
//    }

//    public function getCMSFields()
//    {
//        $this->beforeUpdateCMSFields(function (FieldList $fields) {
//            // Stuff to do before extensions get applied
//        });
//
//        $fields = parent::getCMSFields();
//
//        if(!static::$has_heading){
//            $fields->removeByName('Heading');
//        }
//        if(!static::$has_introline){
//            $fields->removeByName('IntroLine');
//        } elseif($field = $fields->dataFieldByName('IntroLine')) {
//            $field->setRows(2);
//        }
//        if(!static::$has_image){
//            $fields->removeByName('Image');
//        }
//        if(!static::$has_bg_image){
//            $fields->removeByName('BackgroundImage');
//        }
//
//        foreach([ 'Style', 'AvailableGlobally', 'ExtraClass' ] as $fieldName){
//            if($field = $fields->dataFieldByName($fieldName)){
//                $fields->addFieldToTab('Root.Main', $field);
//            }
//        }
//
//        // Clear emptyvalue from 'Style' (labeled 'Layout')
//        /** @var DropdownField $classDrd */
//        if($classDrd = $fields->dataFieldByName('Style')){
//            $emptyString = $classDrd->getEmptyString();
//            $classDrd->setHasEmptyDefault(false);
//            $classDrd->setTitle('Style/variation');
//        }
//
//        // set uploadfield foldernames
//        foreach ($fields->saveableFields() as $field){
//            if(is_a($field, UploadField::class)){
//                $field->setFolderName('blockimages');
//            }
//        }

////        // auto-add descriptions
////        foreach($this->fieldLabels() as $fieldName => $fieldLabel){
////            if($field = $fields->dataFieldByName($fieldName)){
////                $field->setTitle($fieldLabel);
////                if($this->fieldLabel("{$fieldName}_descr") !== FormField::name_to_label("{$fieldName}_descr")) {
////                    $field->setDescription($this->fieldLabel("{$fieldName}_descr"));
////                }
////            }
////        }
//
////        // add attributes
//////        $fields->dataFieldByName('Heading')->setSchemaData(['attributes' => [
//////            'placeholder' => 'HELLO',
//////        ]]);
////        /** @var Tab $mainTab */
////        $mainTab = $fields->fieldByName('Root.Main');
////        foreach ($mainTab->Fields() as $field){
////            var_dump($field->getAttributes());
////            foreach ($field->getAttributes() as $attrName => $attrValue){
////                if($attrName=='placeholder'){
////                    $field->setSchemaData(['attributes' => [
////                        'placeholder' => 'HELLO',
////                    ]]);
////                }
////            }
//////            if($placeholder = $field->getAttribute('placeholder')){
//////                // eg ->setSchemaData(['attributes' => ['placeholder' => 'placeholder value']])
//////                die($placeholder);
//////                $field->setSchemaData(['attributes' => [
//////                    'placeholder' => 'HELLO',
//////                ]]);
//////            }
////        }
//
//        return $fields;
//    }

//    /** -> MOVED TO ElementBlockBaseExtension <-
//     * Provide block schema data, which will be serialised and sent via GraphQL to the editor client.
//     *
//     * Overload this method in child element classes to augment, or use the extension point on `getBlockSchema`
//     * to update it from an `Extension`.
//     *
//     * @return array
//     * @throws SchemaBuilderException
//     * @throws ValidationException
//     */
//    protected function provideBlockSchema()
//    {
//        $blockSchema = parent::provideBlockSchema();
//
//        // Summary takes content and/or fileUrl/fileTitle props, see:
//        // https://github.com/silverstripe/silverstripe-elemental/blob/4/client/src/components/ElementEditor/Summary.js
//        $blockSchema['content'] = "{$this->getDescription()} Block: “{$this->getSummary()}…”";
//
//        return $blockSchema;
//    }
}
