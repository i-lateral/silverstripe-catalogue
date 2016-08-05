<?php

/**
 * CatalogueAdmin creates an admin area that allows editing of products
 * and Product Categories
 *
 * @author i-lateral (http://www.i-lateral.com)
 * @package catalogue
 */
class CatalogueAdmin extends ModelAdmin
{
    
    /**
     * Set the page length for products
     * 
     * @config
     */
    private static $product_page_length = 20;
    
    /**
     * Set the page length for categories
     * 
     * @config
     */
    private static $category_page_length = 20;
    
    private static $url_segment = 'catalogue';

    private static $menu_title = 'Catalogue';

    private static $menu_priority = 11;

    private static $managed_models = array(
        'Product' => array('title' => 'Products'),
        'Category' => array('title' => 'Categories')
    );

    private static $model_importers = array(
        'Product' => 'CatalogueProductCSVBulkLoader',
    );

    public $showImportForm = array('Product');

    public function init()
    {
        parent::init();
    }

    /**
     * Get a list of subclasses for the chosen type (either CatalogueProduct
     * or CatalogueCategory).
     *
     * @return array
     */
    protected function get_classes_list(GridField $grid) {
        // Get a list of available product classes
        $classnames = ClassInfo::subclassesFor($grid->getModelClass());
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
    
    public function getExportFields()
    {
        $fields = array(
            "Title" => "Title",
            "URLSegment" => "URLSegment"
        );
        
        if ($this->modelClass == 'Product') {
            $fields["StockID"] = "StockID";
            $fields["ClassName"] = "Type";
            $fields["BasePrice"] = "Price";
            $fields["TaxRate.Amount"] = "TaxPercent";
            $fields["Images.first.Name"] = "Image1";
            $fields["Categories.first.Title"] = "Category1";
            $fields["Content"] = "Content";
        }
        
        $this->extend("updateExportFields", $fields);
        
        return $fields;
    }

    public function getList()
    {
        $list = parent::getList();
        
        // Filter categories
        if ($this->modelClass == 'Category') {
            $parentID = $this->request->requestVar('ParentID');
            if (!$parentID) {
                $parentID = 0;
            }

            $list = $list->filter('ParentID', $parentID);
        }
        
        $this->extend('updateList', $list);

        return $list;
    }

    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);
        $params = $this->request->requestVar('q');
        
        // Bulk manager
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

        $gridField = $form->Fields()->fieldByName($this->modelClass);
        $field_config = $gridField->getConfig();

        $add_button = new GridFieldAddNewMultiClass("buttons-before-left");
        $add_button->setClasses($this->get_classes_list($gridField));

        if ($this->modelClass == 'Product') {
            $field_config
                ->removeComponentsByType('GridFieldPrintButton')
                ->removeComponentsByType('GridFieldAddNewButton')
                ->removeComponentsByType('GridFieldExportButton')
                ->removeComponentsByType('GridFieldDetailForm')
                ->addComponents(
                    $add_button,
                    new GridFieldExportButton("buttons-before-right"),
                    $manager,
                    new CatalogueEnableDisableDetailForm()
                );
                
            
            // Set the page length
            $field_config
                ->getComponentByType('GridFieldPaginator')
                ->setItemsPerPage($this->config()->product_page_length);

            // Update list of items for subsite (if used)
            if (class_exists('Subsite')) {
                $list = $gridField
                    ->getList()
                    ->filter(array(
                        'SubsiteID' => Subsite::currentSubsiteID()
                    ));

                $gridField->setList($list);
            }
        }
        
        // Alterations for Hiarachy on product cataloge
        if ($this->modelClass == 'Category') {
            $field_config
                ->removeComponentsByType('GridFieldExportButton')
                ->removeComponentsByType('GridFieldPrintButton')
                ->removeComponentsByType('GridFieldDetailForm')
                ->removeComponentsByType('GridFieldAddNewButton')
                ->addComponents(
                    new CatalogueCategoryDetailForm(),
                    $add_button,
                    $manager,
                    GridFieldOrderableRows::create('Sort')
                );
            
            // Set the page length
            $field_config
                ->getComponentByType('GridFieldPaginator')
                ->setItemsPerPage($this->config()->category_page_length);

            // Setup hierarchy view
            $parentID = $this->request->requestVar('ParentID');

            if ($parentID) {
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

            if (!$params) {
                $fields = array_merge(array('listChildrenLink' => ''), $fields);
            }

            $columns->setDisplayFields($fields);
            $columns->setFieldCasting(array('Title' => 'HTMLText', 'URLSegment' => 'Text'));

            $controller = $this;
            $columns->setFieldFormatting(array(
                'listChildrenLink' => function ($value, &$item) use ($controller) {
                    return sprintf(
                        '<a class="list-children-link" data-pjax-target="ListViewForm" href="%s?ParentID=%d">&#9658;</a>',
                        $controller->Link(),
                        $item->ID
                    );
                }
            ));

            // Update list of items for subsite (if used)
            if (class_exists('Subsite')) {
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
        
        if ($this->modelClass == 'Category') {
            //special case for building the breadcrumbs when calling the listchildren Pages ListView action
            if($parentID = $this->getRequest()->getVar('ParentID')) {
                // Rebuild items so we can get the right order
                $first_item = $items->first();
                $first_item->Link = $this->Link();
                $last_item = $items->last();
                $items = ArrayList::create();
                
                $category = DataObject::get_by_id('CatalogueCategory', $parentID);
                
                $categories = array();

                //build a reversed list of the parent tree
                while($category) {
                    array_unshift($categories, $category); //add to start of array so that array is in reverse order
                    $category = $category->Parent;
                }

                //turns the title and link of the breadcrumbs into template-friendly variables
                $params = array_filter(array(
                    'view' => $this->getRequest()->getVar('view'),
                    'q' => $this->getRequest()->getVar('q')
                ));
                
                $items->push($first_item);
                
                foreach($categories as $category) {
                    $params['ParentID'] = $category->ID;
                    $item = new StdClass();
                    $item->Title = $category->Title;
                    $item->Link = Controller::join_links($this->Link(), '?' . http_build_query($params));
                    $items->push(new ArrayData($item));
                }
                
                // Dont add the last item if it is the same as the
                // first item
                if ($last_item->Title != $first_item->Title) {
                    $items->push($last_item);
                }
            }
        }

		return $items;
	}
}