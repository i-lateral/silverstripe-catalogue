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
            $add_button = new GridFieldAddNewButton('buttons-before-left');
            $add_button->setButtonName(_t("CommerceAdmin.AddProduct", "Add Product"));

            // Bulk manager
            $manager = new GridFieldBulkManager();
            $manager->removeBulkAction("delete");

            $manager->addBulkAction(
                'publish',
                'Publish',
                'CatalogueProductBulkAction'
            );

            $manager->addBulkAction(
                'unpublish',
                'Unpublish',
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
                ->removeComponentsByType('GridFieldAddNewButton')
                ->addComponents(
                    $add_button,
                    $manager
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

        $this->extend("updateEditForm", $form);

        return $form;
    }
}
