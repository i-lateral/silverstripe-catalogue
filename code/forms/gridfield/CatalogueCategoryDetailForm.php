<?php

class CatalogueCategoryDetailForm extends CatalogueEnableDisableDetailForm
{
}

class CatalogueCategoryDetailForm_ItemRequest extends CatalogueEnableDisableDetailForm_ItemRequest
{
    private static $allowed_actions = array(
        'edit',
        'view',
        'ItemEditForm'
    );

    /**
     *
     * @param GridFIeld $gridField
     * @param GridField_URLHandler $component
     * @param DataObject $record
     * @param Controller $popupController
     * @param string $popupFormName
     */
    public function __construct($gridField, $component, $record, $popupController, $popupFormName)
    {
        parent::__construct(
            $gridField,
            $component,
            $record,
            $popupController,
            $popupFormName
        );
    }

    public function Link($action = null)
    {
        $parentParam = Controller::curr()
            ->request
            ->requestVar('ParentID');

        $link = ($parentParam) ? parent::Link($action) . "?ParentID=$parentParam" : parent::Link($action);

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
    public function Breadcrumbs($unlinked = false)
    {
		$items = parent::Breadcrumbs($unlinked);

		//special case for building the breadcrumbs when calling the listchildren Pages ListView action
		if ($parentID = $this->getRequest()->getVar('ParentID')) {
            $controller = Controller::curr();
            
            // Rebuild items so we can get the right order
            $first_item = $items->first();
            $first_item->Link = $controller->Link();
            $last_item = $items->last();
            $items = ArrayList::create();
            
			$category = DataObject::get_by_id('CatalogueCategory', $parentID);
            
            $categories = array();

			//build a reversed list of the parent tree
			while ($category) {
				array_unshift($categories, $category); //add to start of array so that array is in reverse order
				$category = $category->Parent;
			}

			//turns the title and link of the breadcrumbs into template-friendly variables
			$params = array_filter(array(
				'view' => $this->getRequest()->getVar('view'),
				'q' => $this->getRequest()->getVar('q')
			));
            
            $items->push($first_item);
            
			foreach ($categories as $category) {
				$params['ParentID'] = $category->ID;
				$item = new StdClass();
				$item->Title = $category->Title;
				$item->Link = Controller::join_links($controller->Link(), '?' . http_build_query($params));
				$items->push(new ArrayData($item));
			}
            
            $items->push($last_item);
		}

		return $items;
	}

    public function ItemEditForm()
    {
        $form = parent::ItemEditForm();

        if ($form) {
            // Update the default parent field
            $parentParam = Controller::curr()->request->requestVar('ParentID');
            $parent_field = $form->Fields()->dataFieldByName("ParentID");

            if ($parentParam && $parent_field) {
                $parent_field->setValue($parentParam);
            }

            return $form;
        }
    }
}