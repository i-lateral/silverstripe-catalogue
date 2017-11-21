<?php

namespace ilateral\SilverStripe\Catalogue\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DB;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\View\SSViewer;
use SilverStripe\View\ArrayData;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\ToggleCompositeField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\TreeMultiselectField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\View\Parsers\URLSegmentFilter;
use ilateral\SilverStripe\Catalogue\Forms\GridField\GridFieldConfig_Catalogue;
use ilateral\SilverStripe\Catalogue\Forms\GridField\GridFieldConfig_CatalogueRelated;
use SilverStripe\Assets\Image;
use ilateral\SilverStripe\Catalogue\Catalogue;
use SilverStripe\Core\Convert;
use TaxRate;
use CatalogueCategory;
use Catagory;

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
class CatalogueProduct extends DataObject implements PermissionProvider
{
    
    private static $table_name = 'CatalogueProduct';
    
    /**
     * Determines if a product's stock ID will be auto generated if
     * not set.
     * 
     * @config
     */
    private static $auto_stock_id = true;
    
    /**
     * Description for this object that will get loaded by the website
     * when it comes to creating it for the first time.
     * 
     * @var string
     * @config
     */
    private static $description = "A standard catalogue product";
    
    private static $db = [
        "Title"             => "Varchar(255)",
        "StockID"           => "Varchar",
        "BasePrice"         => "Currency",
        "URLSegment"        => "Varchar",
        "Content"           => "HTMLText",
        "MetaDescription"   => "Text",
        "ExtraMeta"         => "HTMLText",
        "Disabled"          => "Boolean"
    ];
    
    private static $has_one = [
        "TaxRate"           => TaxRate::class
    ];

    private static $many_many = [
        "Images"            => Image::class,
        "RelatedProducts"   => CatalogueProduct::class
    ];

    private static $many_many_extraFields = [
        "Images" => ["SortOrder" => "Int"],
        'RelatedProducts' => ['SortOrder' => 'Int']
    ];

    private static $belongs_many_many = [
        "Categories"    => CatalogueCategory::class
    ];

    private static $casting = [
        "MenuTitle"         => "Varchar",
        "CategoriesList"    => "Varchar",
        "CMSThumbnail"      => "Varchar",
        "Price"             => "Currency",
        "Tax"               => "Currency",
        "TaxPercent"        => "Decimal",
        "PriceAndTax"       => "Currency",
        "TaxString"         => "Varchar",
        "IncludeTax"        => "Boolean"
    ];

    private static $summary_fields = [
        "CMSThumbnail"  => "Thumbnail",
        "ClassName"     => "Product",
        "StockID"       => "StockID",
        "Title"         => "Title",
        "BasePrice"     => "Price",
        "TaxRate.Amount"=> "Tax Percent",
        "CategoriesList"=> "Categories",
        "Disabled"      => "Disabled"
    ];

    private static $searchable_fields = [
        "Title",
        "URLSegment",
        "Content",
        "StockID",
        "MetaDescription"
    ];

    private static $default_sort = [
        "Title" => "ASC"
    ];
    
    /**
     * Is this object enabled?
     * 
     * @return Boolean
     */
    public function isEnabled()
    {
        return ($this->Disabled) ? false : true;
    }
    
    /**
     * Is this object disabled?
     * 
     * @return Boolean
     */
    public function isDisabled()
    {
        return $this->Disabled;
    }
    
    /**
     * Method that allows us to define in templates if we should show
     * price including tax, or excluding tax
     * 
     * @return boolean
     */
    public function IncludesTax()
    {
        return Catalogue::config()->price_includes_tax;
    }
    
    /**
     * Get a final price for this product. We make this a method so that
     * we can tap into extensions and allow third party modules to alter
     * this (to add items such as tax, bulk pricing, etc).
     *
     * @param int $decimal_size Should we round this number to a
     *             specific size? If set will round the output.
     * @return Float
     */
    public function getPrice($decimal_size = null)
    {
        $price = $this->BasePrice;
        
        $new_price = $this->extend("updatePrice", $price);
        if ($new_price && is_array($new_price)) {
            $price = $new_price[0];
        }
        
        if($decimal_size) {
            $price = number_format($price, $decimal_size);
        }
        
        return $price;
    }
    
    /**
     * Get a final tax amount for this product. You can extend this
     * method using "UpdateTax" allowing third party modules to alter
     * tax amounts dynamically.
     *
     * @param int $decimal_size Should we round this number to a
     *             specific size? If set will round the output. 
     * @return Float
     */
    public function getTax($decimal_size = null)
    {
        $price = $this->BasePrice;
        
        // If tax is enabled in config, add it to the final price
        if ($this->TaxRateID && $this->TaxRate()->Amount) {
            $tax = ($price / 100) * $this->TaxRate()->Amount;
        } else {
            $tax = 0;
        }
        
        $new_tax = $this->extend("updateTax", $tax);
        if ($new_tax && is_array($new_tax)) {
            $tax = $new_tax[0];
        }
        
        if($decimal_size) {
            $tax = number_format($tax, $decimal_size);
        }
        
        return $tax;
    }
    
    /**
     * Get the percentage amount of tax applied to this item
     *
     * @return Decimal
     */
    public function getTaxPercent()
    {
        return ($this->TaxRateID) ? $this->TaxRate()->Amount : 0;
    }
    
    /**
     * Get the final price of this product, including tax (if any)
     *
     * @param int $decimal_size Should we round this number to a
     *             specific size? If set will round the output. 
     * @return Float
     */
    public function getPriceAndTax($decimal_size = null)
    {
        $price = $this->Price + $this->Tax;
        
        $new_price = $this->extend("updatePriceAndTax", $price);
        if ($new_price && is_array($new_price)) {
            $price = $new_price[0];
        }
        
        if($decimal_size) {
            $price = number_format($price, $decimal_size);
        }
        
        return $price;
    }
    
    /**
     * Generate a string to go with the the product price. We can
     * overwrite the wording of this by using Silverstripes language
     * files
     *
     * @return String
     */
    public function getTaxString()
    {
        if ($this->TaxRateID && Catalogue::config()->price_includes_tax) {
            $return = _t("Catalogue.TaxIncludes", "Includes") . " " . $this->TaxRate()->Title;
        } elseif ($this->TaxRateID && !Catalogue::config()->price_includes_tax) {
            $return = _t("Catalogue.TaxExcludes", "Excludes") . " " . $this->TaxRate()->Title;
        } else {
            $return = "";
        }
        
        return $return;
    }

    /**
	 * Stub method to get the site config, unless the current class can provide an alternate.
	 *
	 * @return SiteConfig
	 */
    public function getSiteConfig()
    {
		if($this->hasMethod('alternateSiteConfig')) {
			$altConfig = $this->alternateSiteConfig();
			if($altConfig) return $altConfig;
		}

		return SiteConfig::current_site_config();
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
    public function Link($action = null)
    {
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
    public function AbsoluteLink($action = null)
    {
        if ($this->hasMethod('alternateAbsoluteLink')) {
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
    public function RelativeLink($action = null)
    {
        $base = $this->URLSegment;
		
		$return = $this->extend('updateRelativeLink', $base, $action);

        if($return && is_array($return))
            return $return[count($return) - 1];
        else
            return Controller::join_links($base, $action);
	}
    
    
    /**
     * We use this to tap into the categories "isSection" setup,
     * essentially adding the product's first category to the list
     * 
     * @param $include_parent Include the direct parent of this product
     * @return ArrayList 
     */
    public function getAncestors($include_parent = false)
    {
        $ancestors = ArrayList::create();
        $object    = $this->Categories()->first();
        
        if($object) {
            if($include_parent) $ancestors->push($object);

            while ($object = $object->getParent()) {
                $ancestors->push($object);
            }
        }
        
        $this->extend('updateAncestors', $ancestors, $include_parent);

        return $ancestors;
    }
    
    public function getMenuTitle()
    {
        return $this->Title;
    }

    /**
     * Return sorted products related to this product
     *
     * @return ArrayList
     */
    public function SortedRelatedProducts()
    {
        return $this
            ->RelatedProducts()
            ->Sort([
                "SortOrder" => "ASC",
                "Title" => "ASC"
            ]);
    }

    /**
     * Return sorted images, if no images exist, create a new opbject set
     * with a blank product image in it.
     *
     * @return ArrayList
     */
    public function SortedImages()
    {
        if ($this->Images()->exists()) {
            $images = $this->Images()->Sort('SortOrder');
        } elseif (SiteConfig::current_site_config()->DefaultProductImageID) {
            $default_image = SiteConfig::current_site_config()->DefaultProductImage();
            
            $images = ArrayList::create();
            $images->add($default_image);
        } else {
            $no_image = "assets/no-image.png";
            $no_image_path = Controller::join_links(BASE_PATH, $no_image);
            
            // if no-image does not exist, copy to the assets folder
            if (!file_exists($no_image_path)) {
                $curr_file = Controller::join_links(
                    BASE_PATH,
                    "catalogue/images/no-image.png"
                );
                
                copy($curr_file, $no_image_path);
            }
            
            $images = ArrayList::Create();
            
            $default_image = Image::create();
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
    public function Breadcrumbs($maxDepth = 20)
    {
        $items = array();
        
        $ancestors = $this->getAncestors(true);

        if($ancestors->exists()) {
            $items[] = $this;

            foreach($ancestors as $item) {
                $items[] = $item;
            }
        }

        $template = new SSViewer('BreadcrumbsTemplate');

        return $template->process($this->customise(ArrayData::create(array(
            'Pages' => ArrayList::create(array_reverse($items))
        ))));
    }

    public function getCMSThumbnail()
    {
        return $this->SortedImages()->first()->PaddedImage(50, 50);
    }

    public function getCategoriesList()
    {
        $list = '';

        if ($this->Categories()->exists()) {
            foreach ($this->Categories() as $category) {
                $list .= $category->Title;
                $list .= ', ';
            }
        }

        return $list;
    }

    public function getCMSFields()
    {
        // Get a list of available product classes
        $classnames = array_values(ClassInfo::subclassesFor("Product"));
        $product_types = array();

        foreach ($classnames as $classname) {
            $instance = singleton($classname);
            $product_types[$classname] = $instance->i18n_singular_name();
        }

        // If CMS Installed, use URLSegmentField, otherwise use text
        // field for URL
        if (class_exists('\SilverStripe\CMS\Forms\SiteTreeURLSegmentField')) {
            $baseLink = Controller::join_links(
                Director::absoluteBaseURL()
            );
                        
            $url_field = \SilverStripe\CMS\Forms\SiteTreeURLSegmentField::create("URLSegment");
            $url_field->setURLPrefix($baseLink);
        } else {
            $url_field = TextField::create("URLSegment");
        }

        $fields = FieldList::create(
            $rootTab = TabSet::create("Root",
                // Main Tab Fields
                $tabMain = Tab::create('Main',
                    TextField::create("Title", $this->fieldLabel('Title')),
                    $url_field,
                    HTMLEditorField::create('Content', $this->fieldLabel('Content'))
                        ->setRows(20)
                        ->addExtraClass('stacked'),
                    ToggleCompositeField::create('Metadata', _t('CatalogueAdmin.MetadataToggle', 'Metadata'),
                        array(
                            $metaFieldDesc = TextareaField::create("MetaDescription", $this->fieldLabel('MetaDescription')),
                            $metaFieldExtra = TextareaField::create("ExtraMeta", $this->fieldLabel('ExtraMeta'))
                        )
                    )->setHeadingLevel(4)
                ),
                $tabSettings = Tab::create('Settings',
                    NumericField::create("BasePrice", _t("Catalogue.Price", "Price")),
                    TextField::create("StockID", $this->fieldLabel('StockID'))
                        ->setRightTitle(_t("Catalogue.StockIDHelp", "For example, a product SKU")),
                    DropdownField::create(
                        "TaxRateID",
                        $this->fieldLabel('TaxRate'),
                        TaxRate::get()->map()
                    )->setEmptyString(_t("Catalogue.None", "None")),
                    TreeMultiSelectField::create("Categories", null, "CatalogueCategory"),
                    DropdownField::create(
                        "ClassName",
                        _t("CatalogueAdmin.ProductType", "Type of product"),
                        $product_types
                    )
                )
            )
        );

        // Help text for MetaData on page content editor
        $metaFieldDesc
            ->setRightTitle(
                _t(
                    'CatalogueAdmin.MetaDescHelp',
                    "Search engines use this content for displaying search results (although it will not influence their ranking)."
                )
            )
            ->addExtraClass('help');
        $metaFieldExtra
            ->setRightTitle(
                _t(
                    'CatalogueAdmin.MetaExtraHelp',
                    "HTML tags for additional meta information. For example &lt;meta name=\"customName\" content=\"your custom content here\" /&gt;"
                )
            )
            ->addExtraClass('help');

        if ($this->ID) {
            $fields->addFieldToTab(
                'Root.Images',
                UploadField::create(
                    'Images',
                    $this->fieldLabel('Images'),
                    $this->Images()
                )
            );

            $fields->addFieldToTab(
                'Root.Related',
                GridField::create(
                    'RelatedProducts',
                    "",
                    $this->RelatedProducts(),
                    new GridFieldConfig_CatalogueRelated("Product",null,'SortOrder')
                )
            );
        }

        $this->extend('updateCMSFields', $fields);

        return $fields;
    }

    public function getCMSValidator()
    {
        $required = array("Title");
        
        if (!$this->config()->auto_stock_id) {
            $required[] = "StockID";
        }
        
        return RequiredFields::create($required);
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
    public function validURLSegment()
    {
        $objects_to_check = array(
            CatalogueProduct::class,
            CatalogueCategory::class
        );
        
        if (class_exists("\SilverStripe\CMS\Model\SiteTree")) {
            $objects_to_check[] = "\SilverStripe\CMS\Model\SiteTree";
        }

        $segment = Convert::raw2sql($this->URLSegment);

        foreach ($objects_to_check as $classname) {
            $return = $classname::get()
                ->filter(array(
                    "URLSegment"=> $segment,
                    "ID:not"    => $this->ID
                ));

            if ($return->exists()) {
                return false;
            }
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
    public function generateURLSegment($title)
    {
        $filter = URLSegmentFilter::create();
        $t = $filter->filter($title);

        // Fallback to generic page name if path is empty (= no valid, convertable characters)
        if (!$t || $t == '-' || $t == '-1') {
            $t = "page-$this->ID";
        }

        // Hook for extensions
        $this->extend('updateURLSegment', $t, $title);

        return $t;
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        // If there is no URLSegment set, generate one from Title
        if ((!$this->URLSegment || $this->URLSegment == 'new-product') && $this->Title) {
            $this->URLSegment = $this->generateURLSegment($this->Title);
        } elseif ($this->isChanged('URLSegment', 2)) {
            // Do a strict check on change level, to avoid double encoding caused by
            // bogus changes through forceChange()
            $filter = URLSegmentFilter::create();
            $this->URLSegment = $filter->filter($this->URLSegment);
            // If after sanitising there is no URLSegment, give it a reasonable default
            if (!$this->URLSegment) {
                $this->URLSegment = "page-$this->ID";
            }
        }

        // Ensure that this object has a non-conflicting URLSegment value.
        $count = 2;
        while (!$this->validURLSegment()) {
            $this->URLSegment = preg_replace('/-[0-9]+$/', null, $this->URLSegment) . '-' . $count;
            $count++;
        }
        
        if ($this->ID && $this->config()->auto_stock_id && !$this->StockID) {
            $title = "";
            
            foreach (explode("-", $this->URLSegment) as $string) {
                $string = substr($string, 0, 1);
                $title .= $string;
            }
            
            $this->StockID = $title . "-" . $this->ID;
        }
    }
    
    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        
        $records = CatalogueProduct::get()
            ->filter("ClassName", "CatalogueProduct");
        
        if ($records->exists()) {
            // Alter any existing recods that might have the wrong classname
            foreach ($records as $product) {
                $product->ClassName = "Product";
                $product->write();
            }
            DB::alteration_message("Updated {$records->count()} Product records", 'obsolete');
        }
    }
    
    public function providePermissions()
    {
        return [
            "CATALOGUE_ADD_PRODUCTS" => [
                'name' => 'Add products',
                'help' => 'Allow user to add products to catalogue',
                'category' => 'Catalogue',
                'sort' => 50
            ],
            "CATALOGUE_EDIT_PRODUCTS" => [
                'name' => 'Edit products',
                'help' => 'Allow user to edit any product in catalogue',
                'category' => 'Catalogue',
                'sort' => 100
            ],
            "CATALOGUE_DELETE_PRODUCTS" => [
                'name' => 'Delete products',
                'help' => 'Allow user to delete any product in catalogue',
                'category' => 'Catalogue',
                'sort' => 150
            ]
        ];
    }

    public function canView($member = null, $context = [])
    {
        return true;
    }

    public function canCreate($member = null, $context = [])
    {
        if ($member instanceof Member) {
            $memberID = $member->ID;
        } elseif (is_numeric($member)) {
            $memberID = $member;
        } else {
            $memberID = Member::currentUserID();
        }

        if ($memberID && Permission::checkMember($memberID, array("ADMIN", "CATALOGUE_ADD_PRODUCTS"))) {
            return true;
        } elseif ($memberID && $memberID == $this->CustomerID) {
            return true;
        }

        return true;
    }

    public function canEdit($member = null, $context = [])
    {
        if ($member instanceof Member) {
            $memberID = $member->ID;
        } elseif (is_numeric($member)) {
            $memberID = $member;
        } else {
            $memberID = Member::currentUserID();
        }

        if ($memberID && Permission::checkMember($memberID, array("ADMIN", "CATALOGUE_EDIT_PRODUCTS"))) {
            return true;
        } elseif ($memberID && $memberID == $this->CustomerID) {
            return true;
        }

        return false;
    }

    public function canDelete($member = null)
    {
        if ($member instanceof Member) {
            $memberID = $member->ID;
        } elseif (is_numeric($member)) {
            $memberID = $member;
        } else {
            $memberID = Member::currentUserID();
        }

        if ($memberID && Permission::checkMember($memberID, array("ADMIN", "CATALOGUE_DELETE_PRODUCTS"))) {
            return true;
        } elseif ($memberID && $memberID == $this->CustomerID) {
            return true;
        }

        return false;
    }
}
