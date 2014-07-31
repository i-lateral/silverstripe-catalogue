<?php
/**
 * CatalogueAdmin creates an admin area that allows editing of products
 * and Product Categories
 *
 * @author i-lateral (http://www.i-lateral.com)
 * @package catalogue
 */

class CatalogueAdmin extends ModelAdmin {
    
    private static $url_segment = 'catalogue';

    private static $menu_title = 'Catalogue';

    private static $menu_priority = 11;

    private static $managed_models = array(
        'CatalogueProduct' => array('title' => 'Products'),
        'CatalogueCategory' => array('title' => 'Categories')
    );

    private static $model_importers = array(
        'CatalogueProduct' => 'CatalogueProductCSVBulkLoader',
    );

    public $showImportForm = array('CatalogueProduct');

    public function init() {
        parent::init();
    }

    public function getList() {
        $list = parent::getList();
        
        // Filter categories
        if($this->modelClass == 'CatalogueCategory') {
            $parentID = $this->request->requestVar('ParentID');
            if(!$parentID) $parentID = 0;

            $list = $list->filter('ParentID',$parentID);
        }

        return $list;
    }

    public function getEditForm($id = null, $fields = null) {
        $form = parent::getEditForm($id, $fields);
        $params = $this->request->requestVar('q');

        if($this->modelClass == 'CatalogueProduct') {
            $gridField = $form->Fields()->fieldByName('CatalogueProduct');
            $field_config = $gridField->getConfig();

            // Re add creation button and update grid field
            $add_button = new GridFieldAddNewButton('toolbar-header-left');
            $add_button->setButtonName('Add Product');

            // Bulk manager
            $manager = new GridFieldBulkManager();
            $manager->removeBulkAction("unlink");
            $manager->removeBulkAction("delete");

            $manager->addBulkAction(
                'enable',
                'Enable',
                'CatalogueProductBulkAction'
            );

            $manager->addBulkAction(
                'disable',
                'Disable',
                'CatalogueProductBulkAction'
            );

            $manager->addBulkAction(
                'delete',
                'Delete',
                'GridFieldBulkActionDeleteHandler',
                 array(
                    'isAjax' => true,
                    'icon' => 'decline',
                    'isDestructive' => true
                )
            );

            $field_config
                ->removeComponentsByType('GridFieldPrintButton')
                ->removeComponentsByType('GridFieldDetailForm')
                ->removeComponentsByType('GridFieldAddNewButton')
                ->addComponents(
                    $add_button,
                    $manager,
                    new VersionedDataObjectDetailsForm()
                );

            // Update list of items for subsite (if used)
            if(class_exists('Subsite')) {
                $list = $gridField
                    ->getList()
                    ->filter(array(
                        'SubsiteID' => Subsite::currentSubsiteID()
                    ));

                $gridField->setList($list);
            }

        }

        // Alterations for Hiarachy on product cataloge
        if($this->modelClass == 'CatalogueCategory') {
            $fields = $form->Fields();
            $gridField = $fields->fieldByName('CatalogueCategory');

            // Set custom record editor
            $record_editor = new GridFieldDetailForm();
            $record_editor->setItemRequestClass('CatalogueCategory_ItemRequest');

            // Create add button and update grid field
            $add_button = new GridFieldAddNewButton('toolbar-header-left');
            $add_button->setButtonName('Add Category');

            // Bulk manager
            $manager = new GridFieldBulkManager();
            $manager->removeBulkAction("unlink");

            // Tidy up category config
            $field_config = $gridField->getConfig();
            $field_config
                ->removeComponentsByType('GridFieldExportButton')
                ->removeComponentsByType('GridFieldPrintButton')
                ->removeComponentsByType('GridFieldDetailForm')
                ->removeComponentsByType('GridFieldAddNewButton')
                ->addComponents(
                    $record_editor,
                    $add_button,
                    $manager,
                    GridFieldOrderableRows::create('Sort')
                );

            // Setup hierarchy view
            $parentID = $this->request->requestVar('ParentID');

            if($parentID){
                $field_config->addComponent(
                    GridFieldLevelup::create($parentID)
                        ->setLinkSpec('?ParentID=%d')
                        ->setAttributes(array(
                            'data-pjax' => 'ListViewForm,Breadcrumbs'
                        ))
                );
            }

            // Find data colums, so we can add link to view children
            $columns = $gridField->getConfig()->getComponentByType('GridFieldDataColumns');

            // Don't allow navigating into children nodes on filtered lists
            $fields = array(
                'Title' => 'Title',
                'URLSegment' => 'URLSegement'
            );

            if(!$params) {
                $fields = array_merge(array('listChildrenLink' => ''), $fields);
            }

            $columns->setDisplayFields($fields);
            $columns->setFieldCasting(array('Title' => 'HTMLText', 'URLSegment' => 'Text'));

            $controller = $this;
            $columns->setFieldFormatting(array(
                'listChildrenLink' => function($value, &$item) use($controller) {
                    return sprintf(
                        '<a class="list-children-link" data-pjax-target="ListViewForm" href="%s?ParentID=%d">&#9658;</a>',
                        $controller->Link(),
                        $item->ID
                    );
                }
            ));

            // Update list of items for subsite (if used)
            if(class_exists('Subsite')) {
                $list = $gridField
                    ->getList()
                    ->filter(array(
                        'SubsiteID' => Subsite::currentSubsiteID()
                    ));

                $gridField->setList($list);
            }
        }

        $this->extend("updateEditForm", $form);

        return $form;
    }

    /**
     * Return the title of the current section. Either this is pulled from
     * the current panel's menu_title or from the first active menu
     *
     * @return string
     */
    function SectionTitle() {
        if($this->modelClass == 'CatalogueCategory')
            return 'Category';
        else
            return 'Product';
    }
}

class CatalogueCategory_ItemRequest extends GridFieldDetailForm_ItemRequest {
    private static $allowed_actions = array(
        "ItemEditForm"
    );

    /**
     *
     * @param GridFIeld $gridField
     * @param GridField_URLHandler $component
     * @param DataObject $record
     * @param Controller $popupController
     * @param string $popupFormName
     */
    public function __construct($gridField, $component, $record, $popupController, $popupFormName) {
        parent::__construct($gridField, $component, $record, $popupController, $popupFormName);
    }

    public function Link($action = null) {
        $parentParam = Controller::curr()->request->requestVar('ParentID');
        $link = $parentParam ? parent::Link() . "?ParentID=$parentParam" : parent::Link();

        return $link;
    }

    /**
     * CMS-specific functionality: Passes through navigation breadcrumbs
     * to the template, and includes the currently edited record (if any).
     * see {@link LeftAndMain->Breadcrumbs()} for details.
     *
     * @param boolean $unlinked
     * @return ArrayData
     */
    function Breadcrumbs($unlinked = false) {
        if(!$this->popupController->hasMethod('Breadcrumbs')) return;

        $items = $this->popupController->Breadcrumbs($unlinked);
        if($this->record && $this->record->ID) {
            $ancestors = $this->record->getAncestors();
            $ancestors = new ArrayList(array_reverse($ancestors->toArray()));
            $ancestors->push($this->record);

            // Push each ancestor to breadcrumbs
            foreach($ancestors as $ancestor) {
                $items->push(new ArrayData(array(
                    'Title' => $ancestor->Title,
                    'Link' => ($unlinked) ? false : $this->popupController->Link() . "?ParentID={$ancestor->ID}"
                )));
            }
        } else {
            $items->push(new ArrayData(array(
                'Title' => sprintf(_t('GridField.NewRecord', 'New %s'), $this->record->singular_name()),
                'Link' => false
            )));
        }

        return $items;
    }

    public function ItemEditForm() {
        $form = parent::ItemEditForm();

        if($form) {
            // Update the default parent field
            $parentParam = Controller::curr()->request->requestVar('ParentID');
            $parent_field = $form->Fields()->dataFieldByName("ParentID");

            if($parentParam && $parent_field) {
                $parent_field->setValue($parentParam);
            }

            return $form;
        }
    }
}
