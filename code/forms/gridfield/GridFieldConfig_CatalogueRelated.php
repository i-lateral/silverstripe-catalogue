<?php

/**
 * Allows editing of records contained within the GridField, instead of only allowing the ability to view records in
 * the GridField.
 *
 * @package forms
 * @subpackage fields-gridfield
 */
class GridFieldConfig_CatalogueRelated extends GridFieldConfig_Catalogue {

	/**
	 *
	 * @param array $classname Name of class who's subclasses will be added to form
	 * @param int $itemsPerPage - How many items per page should show up
	 * @param boolean | string $sorting Allow sorting of rows, either false or the name of the sort column
	 */
	public function __construct($classname, $itemsPerPage=null, $sort_col = false) {
		parent::__construct($classname, $itemsPerPage=null, $sort_col = false);

		// Remove uneeded components
		$this->removeComponentsByType('GridFieldDeleteAction');
        $this->removeComponentsByType('GridFieldExportButton');

		$this->addComponent(new GridFieldAddExistingAutocompleter('buttons-before-right'));
		$this->addComponent(new GridFieldDeleteAction(true));

		$this->extend('updateConfig');
	}
}