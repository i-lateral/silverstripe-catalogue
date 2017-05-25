<?php

/**
 * Allows editing of records contained within the GridField, instead of only allowing the ability to view records in
 * the GridField.
 *
 * @package forms
 * @subpackage fields-gridfield
 */
class GridFieldConfig_Catalogue extends GridFieldConfig {

	/**
     * Get a list of subclasses for the chosen type (either CatalogueProduct
     * or CatalogueCategory).
     *
	 * @param string $classname Classname of object we will get list for
     * @return array
     */
    protected function get_subclasses($classname) {
        // Get a list of available product classes
        $classnames = ClassInfo::subclassesFor($classname);
        $return = array();

        foreach ($classnames as $classname) {
            $instance = singleton($classname);
            $description = Config::inst()->get($classname, 'description');                    
            $description = ($description) ? $instance->i18n_singular_name() . ': ' . $description : $instance->i18n_singular_name();
            
            $return[$classname] = $description;
        }

        asort($return);
        return $return;
    }

	/**
	 *
	 * @param array $classname Name of class who's subclasses will be added to form
	 * @param int $itemsPerPage - How many items per page should show up
	 * @param boolean | string $sorting Allow sorting of rows, either false or the name of the sort column
	 */
	public function __construct($classname, $itemsPerPage=null, $sort_col = false) {
		parent::__construct();

		// Setup initial gridfield
		$this->addComponent(new GridFieldButtonRow('before'));
		$this->addComponent(new GridFieldToolbarHeader());
		$this->addComponent($sort = new GridFieldSortableHeader());
		$this->addComponent($filter = new GridFieldFilterHeader());
		$this->addComponent(new GridFieldDataColumns());
		$this->addComponent(new GridFieldEditButton());
		$this->addComponent(new GridFieldDeleteAction());
		$this->addComponent(new GridFieldPageCount('toolbar-header-right'));
		$this->addComponent($pagination = new GridFieldPaginator($itemsPerPage));
		$this->addComponent(new GridFieldExportButton("buttons-before-right"));

		// Setup Bulk manager
		$manager = new GridFieldBulkManager();
        $manager->removeBulkAction("unLink");
        
        $manager->addBulkAction(
            'disable',
            'Disable',
            'CatalogueProductBulkAction'
        );
        
        $manager->addBulkAction(
            'enable',
            'Enable',
            'CatalogueProductBulkAction'
        );

		$this->addComponent($manager);

		// Setup add new button
		$add_button = new GridFieldAddNewMultiClass("buttons-before-left");
        $add_button->setClasses($this->get_subclasses($classname));

		// If we are manageing a category, use the relevent field, else
		// use product
		if ($classname == "Category") {
			$this->addComponent(new CatalogueCategoryDetailForm());
			$add_button->setItemRequestClass("CatalogueCategoryDetailForm_ItemRequest");
		} else {
			$this->addComponent(new CatalogueEnableDisableDetailForm());
			$add_button->setItemRequestClass("CatalogueEnableDisableDetailForm_ItemRequest");
		}

		$this->addComponent($add_button);

		if ($sort_col) {
			$this->addComponent(GridFieldOrderableRows::create($sort_col));
		}

		$sort->setThrowExceptionOnBadDataType(false);
		$filter->setThrowExceptionOnBadDataType(false);
		$pagination->setThrowExceptionOnBadDataType(false);
	}
}