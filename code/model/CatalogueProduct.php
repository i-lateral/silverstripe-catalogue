<?php

/**
 * Base class for all products stored in the database. The intention is
 * to allow Product objects to be extended in the same way as a more
 * conventional "Page" object.
 * 
 * This allows users familier with working with the CMS a common
 * platform for developing ecommerce type functionality.
 * 
 * @author i-lateral (http://www.i-lateral.com)
 * @package catalogue
 */
class CatalogueProduct extends DataObject implements PermissionProvider {
    
    /**
     * Description for this object that will get loaded by the website
     * when it comes to creating it for the first time.
     * 
     * @var string
     * @config
     */
    private static $description = "A standard catalogue product";
    
    private static $db = array(
        "Title"             => "Varchar(255)",
        "StockID"           => "Varchar",
        "BasePrice"         => "Currency",
        "URLSegment"        => "Varchar",
        "Content"           => "HTMLText",
        "MetaDescription"   => "Text",
        "ExtraMeta"         => "HTMLText",
        "Disabled"          => "Boolean"
    );
    
    private static $has_one = array(
        "TaxRate"           => "TaxRate"
    );

    private static $many_many = array(
        "Images"            => "Image",
        "RelatedProducts"   => "CatalogueProduct"
    );

    private static $many_many_extraFields = array(
        "Images" => array("SortOrder" => "Int")
    );

    private static $belongs_many_many = array(
        "Categories"    => "CatalogueCategory"
    );

    private static $casting = array(
        "MenuTitle"         => "Varchar",
        "CategoriesList"    => "Varchar",
        "CMSThumbnail"      => "Varchar",
        "Price"             => "Currency",
        "Tax"               => "Currency",
        "PriceAndTax"       => "Currency",
        "TaxString"         => "Varchar",
        "IncludeTax"        => "Boolean"
    );

    private static $summary_fields = array(
        "CMSThumbnail"  => "Thumbnail",
        "ClassName"     => "Type",
        "StockID"       => "StockID",
        "Title"         => "Title",
        "BasePrice"     => "Price",
        "TaxRate.Amount"=> "Tax Percent",
        "CategoriesList"=> "Categories",
        "Disabled"      => "Disabled"
    );

    private static $searchable_fields = array(
        "Title",
        "URLSegment",
        "Content",
        "MetaDescription"
    );

    private static $default_sort = '"Title" ASC';
    
    /**
     * Method that allows us to define in templates if we should show
     * price including tax, or excluding tax
     * 
     * @return boolean
     */
    public function IncludesTax() {
        return Catalogue::config()->price_includes_tax;
    }
    
    /**
     * Get a final price for this product. We make this a method so that
     * we can tap into extensions and allow third party modules to alter
     * this (to add items such as tax, bulk pricing, etc).
     *
     * @return Currency
     */
    public function Price() {
        $price = $this->BasePrice;
        
        $this->extend("updatePrice", $price);
        
        return $price;
    }
    
    /**
     * Get a final tax amount for this product. You can extend this
     * method using "UpdateTax" allowing third party modules to alter
     * tax amounts dynamically.
     *
     * @return Currency
     */
    public function Tax() {
        $price = $this->BasePrice;
        
        // If tax is enabled in config, add it to the final price
        if($this->TaxRateID && $this->TaxRate()->Amount)
            $tax = ($price / 100) * $this->TaxRate()->Amount;
        else
            $tax = 0;
        
        $this->extend("updateTax", $tax);
        
        return $tax;
    }
    
    /**
     * Get the final price of this product, including tax (if any)
     *
     * @return Currency
     */
    public function PriceAndTax() {
        $price = $this->Price() + $this->Tax();
        
        $this->extend("updatePriceAndTax", $price);
        
        return $price;
    }
    
    /**
     * Generate a string to go with the the product price. We can
     * overwrite the wording of this by using Silverstripes language
     * files
     *
     * @return String
     */
    public function TaxString() {
        if($this->TaxRateID && Catalogue::config()->price_includes_tax)
            $return = _t("Catalogue.TaxIncludes", "Includes") . " " . $this->TaxRate()->Title;
        elseif($this->TaxRateID && !Catalogue::config()->price_includes_tax)
            $return = _t("Catalogue.TaxExcludes", "Excludes") . " " . $this->TaxRate()->Title;
        else
            $return = "";
        
        return $return;
    }
    
    
    /**
	 * Return the link for this {@link SimpleProduct} object, with the
     * {@link Director::baseURL()} included.
	 *
	 * @param string $action Optional controller action (method). 
	 *  Note: URI encoding of this parameter is applied automatically through template casting,
	 *  don't encode the passed parameter.
	 *  Please use {@link Controller::join_links()} instead to append GET parameters.
	 * @return string
	 */
	public function Link($action = null) {
		return Controller::join_links(
            Director::baseURL(),
            $this->RelativeLink($action)
        );
	}
	
	/**
	 * Get the absolute URL for this page, including protocol and host.
	 *
	 * @param string $action See {@link Link()}
	 * @return string
	 */
	public function AbsoluteLink($action = null) {
		if($this->hasMethod('alternateAbsoluteLink')) {
			return $this->alternateAbsoluteLink($action);
		} else {
			return Director::absoluteURL($this->Link($action));
		}
	}
    
    /**
	 * Return the link for this {@link Product}
	 *
	 * 
	 * @param string $action See {@link Link()}
	 * @return string
	 */
	public function RelativeLink($action = null) {
        $base = $this->URLSegment;
		
		$this->extend('updateRelativeLink', $base, $action);

		return Controller::join_links($base, $action);
	}
    
    /**
     * We use this to tap into the categories "isSection" setup,
     * essentially adding the product's first category to the list
     * 
     */
    public function getAncestors() {
        $ancestors = ArrayList::create();
        
        $object    = $this->Categories()->first();

        while($object = $object->getParent()) {
            $ancestors->push($object);
        }

        return $ancestors;
    }
    
    public function getMenuTitle() {
        return $this->Title;
    }

    /**
     * Return sorted images, if no images exist, create a new opbject set
     * with a blank product image in it.
     *
     * @return ArrayList
     */
    public function SortedImages(){
        if($this->Images()->exists())
            $images = $this->Images()->Sort('SortOrder');
        elseif(SiteConfig::current_site_config()->DefaultProductImageID) {
            $default_image = SiteConfig::current_site_config()->DefaultProductImage();
            
            $images = new ArrayList();
            $images->add($default_image);
        } else {
            $no_image = "assets/no-image.png";
            $no_image_path = Controller::join_links(BASE_PATH, $no_image);
            
            // if no-image does not exist, copy to the assets folder
            if(!file_exists($no_image_path)) {
                $curr_file = Controller::join_links(
                    BASE_PATH,
                    "catalogue/images/no-image.png"
                );
                
                copy($curr_file, $no_image_path);
            }
            
            $images = new ArrayList();
            
            $default_image = new Image();
            $default_image->ID = -1;
            $default_image->Title = "No Image Available";
            $default_image->FileName = $no_image;
            
            $images->add($default_image);
        }

        return $images;
    }

    /**
     * Return a breadcrumb trail for this product (which accounts for parent
     * categories)
     *
     * @param int $maxDepth The maximum depth to traverse.
     *
     * @return string The breadcrumb trail.
     */
    public function Breadcrumbs($maxDepth = 20) {
        $items = array();

        if($this->Categories()->exists()) {
            $items[] = $this;
            $category = $this->Categories()->first();

            foreach($category->parentStack() as $item) {
                $items[] = $item;
            }
        }

        $template = new SSViewer('BreadcrumbsTemplate');

        return $template->process($this->customise(new ArrayData(array(
            'Pages' => new ArrayList(array_reverse($items))
        ))));
    }

    public function getCMSThumbnail() {
        return $this->SortedImages()->first()->PaddedImage(50,50);
    }

    public function getCategoriesList() {
        $list = '';

        if($this->Categories()->exists()){
            foreach($this->Categories() as $category) {
                $list .= $category->Title;
                $list .= ', ';
            }
        }

        return $list;
    }

    public function getCMSFields() {
        // Get a list of available product classes
        $classnames = ClassInfo::getValidSubClasses("CatalogueProduct");
        $product_array = array();
        
        foreach($classnames as $classname) {
            if($classname != "CatalogueProduct") {
                $description = Config::inst()->get($classname, 'description');
                
                if($classname == 'Product' && !$description)
                    $description = self::config()->description;
                        
                $description = ($description) ? $classname . ' - ' . $description : $classname; 
                
                $product_array[$classname] = $description;
            }
        }
        
        
        // If we are creating a product, let us choose the product type
        if(!$this->ID) {
            $fields = new FieldList(
                $rootTab = new TabSet("Root",
                    // Main Tab Fields
                    $tabMain = new Tab('Main',
                        HiddenField::create("Title")
                            ->setValue(_t("Catalogue.NewProduct", "New Product")),
                        ProductTypeField::create(
                            "ClassName",
                            _t("ProductCatalogue.SelectProductType", "Select a type of Product"),
                           $product_array
                        )
                    )
                )
            );
        } else {
            // If CMS Installed, use URLSegmentField, otherwise use text
            // field for URL
            if(class_exists('SiteTreeURLSegmentField')) {     
                $baseLink = Controller::join_links (
                    Director::absoluteBaseURL()
                );
                           
                $url_field = SiteTreeURLSegmentField::create("URLSegment");
                $url_field->setURLPrefix($baseLink);
            } else
                $url_field = TextField::create("URLSegment");
            
            $fields = new FieldList(
                $rootTab = new TabSet("Root",
                    // Main Tab Fields
                    $tabMain = new Tab('Main',
                        TextField::create("Title", $this->fieldLabel('Title')),
                        $url_field,
                        HTMLEditorField::create('Content', $this->fieldLabel('Content'))
                            ->setRows(20)
                            ->addExtraClass('stacked'),
                        ToggleCompositeField::create('Metadata', _t('CommerceAdmin.MetadataToggle', 'Metadata'),
                            array(
                                $metaFieldDesc = TextareaField::create("MetaDescription", $this->fieldLabel('MetaDescription')),
                                $metaFieldExtra = TextareaField::create("ExtraMeta",$this->fieldLabel('ExtraMeta'))
                            )
                        )->setHeadingLevel(4)
                    ),
                    $tabSettings = new Tab('Settings',
                        NumericField::create("BasePrice", _t("Catalogue.Price", "Price")),
                        TextField::create("StockID", $this->fieldLabel('StockID'))
                            ->setRightTitle(_t("Catalogue.StockIDHelp", "For example, a product SKU")),
                        DropdownField::create(
                            "TaxRateID",
                            $this->fieldLabel('TaxRate'),
                            TaxRate::get()->map()
                        )->setEmptyString(_t("Catalogue.None", "None")),
                        TreeMultiSelectField::create("Categories", null, "CatalogueCategory"),
                        CheckboxField::create(
                            "Disabled",
                            _t("Catalogue.DisableProduct", "Disable this product (will not appear on shopfront)")
                        ),
                        DropdownField::create(
                            "ClassName",
                            _t("CatalogueAdmin.ProductType", "Type of product"),
                            $product_array
                        )
                    ),
                    $tabImages = new Tab('Images',
                        SortableUploadField::create('Images', $this->fieldLabel('Images'), $this->Images())
                    )
                )
            );

            // Help text for MetaData on page content editor
            $metaFieldDesc
                ->setRightTitle(
                    _t(
                        'CommerceAdmin.MetaDescHelp',
                        "Search engines use this content for displaying search results (although it will not influence their ranking)."
                    )
                )
                ->addExtraClass('help');
            $metaFieldExtra
                ->setRightTitle(
                    _t(
                        'CommerceAdmin.MetaExtraHelp',
                        "HTML tags for additional meta information. For example &lt;meta name=\"customName\" content=\"your custom content here\" /&gt;"
                    )
                )
                ->addExtraClass('help');

            $fields->addFieldToTab(
                'Root.Related',
                GridField::create(
                    'RelatedProducts',
                    "",
                    $this->RelatedProducts(),
                    GridFieldConfig_RelationEditor::create()
                )
            );
        }

        $this->extend('updateCMSFields', $fields);

        return $fields;
    }

    public function getCMSValidator() {
        return new RequiredFields(array("Title","StockID"));
    }

    /**
     * Returns TRUE if this object has a URLSegment value that does not conflict with any other objects. This methods
     * checks for:
     *   - A page with the same URLSegment that has a conflict.
     *   - Conflicts with actions on the parent page.
     *   - A conflict caused by a root page having the same URLSegment as a class name.
     *
     * @return bool
     */
    public function validURLSegment() {
        $objects_to_check = array(
            "SiteTree",
            "CatalogueProduct",
            "CatalogueCategory"
        );

        $segment = Convert::raw2sql($this->URLSegment);

        foreach($objects_to_check as $classname) {
            $return = $classname::get()
                ->filter(array(
                    "URLSegment"=> $segment,
                    "ID:not"    => $this->ID
                ));

            if($return->exists()) return false;
        }

        return true;
    }

    /**
     * Generate a URL segment based on the title provided.
     *
     * If {@link Extension}s wish to alter URL segment generation, they can do so by defining
     * updateURLSegment(&$url, $title).  $url will be passed by reference and should be modified.
     * $title will contain the title that was originally used as the source of this generated URL.
     * This lets extensions either start from scratch, or incrementally modify the generated URL.
     *
     * @param string $title Page title.
     * @return string Generated url segment
     */
    public function generateURLSegment($title){
        $filter = URLSegmentFilter::create();
        $t = $filter->filter($title);

        // Fallback to generic page name if path is empty (= no valid, convertable characters)
        if(!$t || $t == '-' || $t == '-1') $t = "page-$this->ID";

        // Hook for extensions
        $this->extend('updateURLSegment', $t, $title);

        return $t;
    }

    public function onBeforeWrite() {
        parent::onBeforeWrite();

        // If there is no URLSegment set, generate one from Title
        if((!$this->URLSegment || $this->URLSegment == 'new-product') && $this->Title) {
            $this->URLSegment = $this->generateURLSegment($this->Title);
        } else if($this->isChanged('URLSegment', 2)) {
            // Do a strict check on change level, to avoid double encoding caused by
            // bogus changes through forceChange()
            $filter = URLSegmentFilter::create();
            $this->URLSegment = $filter->filter($this->URLSegment);
            // If after sanitising there is no URLSegment, give it a reasonable default
            if(!$this->URLSegment) $this->URLSegment = "page-$this->ID";
        }

        // Ensure that this object has a non-conflicting URLSegment value.
        $count = 2;
        while(!$this->validURLSegment()) {
            $this->URLSegment = preg_replace('/-[0-9]+$/', null, $this->URLSegment) . '-' . $count;
            $count++;
        }
    }
    
    public function providePermissions() {
        return array(
            "CATALOGUE_ADD_PRODUCTS" => array(
                'name' => 'Add products',
                'help' => 'Allow user to add products to catalogue',
                'category' => 'Catalogue',
                'sort' => 50
            ),
            "CATALOGUE_EDIT_PRODUCTS" => array(
                'name' => 'Edit products',
                'help' => 'Allow user to edit any product in catalogue',
                'category' => 'Catalogue',
                'sort' => 100
            ),
            "CATALOGUE_DELETE_PRODUCTS" => array(
                'name' => 'Delete products',
                'help' => 'Allow user to delete any product in catalogue',
                'category' => 'Catalogue',
                'sort' => 150
            )
        );
    }

    public function canView($member = false) {
        return true;
    }

    public function canCreate($member = null) {
        if($member instanceof Member)
            $memberID = $member->ID;
        else if(is_numeric($member))
            $memberID = $member;
        else
            $memberID = Member::currentUserID();

        if($memberID && Permission::checkMember($memberID, array("ADMIN", "CATALOGUE_ADD_PRODUCTS")))
            return true;
        else if($memberID && $memberID == $this->CustomerID)
            return true;

        return true;
    }

    public function canEdit($member = null) {
        if($member instanceof Member)
            $memberID = $member->ID;
        else if(is_numeric($member))
            $memberID = $member;
        else
            $memberID = Member::currentUserID();

        if($memberID && Permission::checkMember($memberID, array("ADMIN", "CATALOGUE_EDIT_PRODUCTS")))
            return true;
        else if($memberID && $memberID == $this->CustomerID)
            return true;

        return false;
    }

    public function canDelete($member = null) {
        if($member instanceof Member)
            $memberID = $member->ID;
        else if(is_numeric($member))
            $memberID = $member;
        else
            $memberID = Member::currentUserID();

        if($memberID && Permission::checkMember($memberID, array("ADMIN", "CATALOGUE_DELETE_PRODUCTS")))
            return true;
        else if($memberID && $memberID == $this->CustomerID)
            return true;

        return false;
    }
}
