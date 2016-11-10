<?php

/**
 * Base class for all product categories stored in the database. The
 * intention is to allow category objects to be extended in the same way
 * as a more conventional "Page" object.
 * 
 * This allows users familier with working with the CMS a common
 * platform for developing ecommerce type functionality.
 * 
 * @author i-lateral (http://www.i-lateral.com)
 * @package catalogue
 */
class CatalogueCategory extends DataObject implements PermissionProvider
{
    
    /**
     * Description for this object that will get loaded by the website
     * when it comes to creating it for the first time.
     * 
     * @var string
     * @config
     */
    private static $description = "A basic product category";
    
    private static $db = array(
        "Title"             => "Varchar",
        "URLSegment"        => "Varchar",
        "Sort"              => "Int",
        "MetaDescription"   => "Text",
        "ExtraMeta"         => "HTMLText",
        "Disabled"          => "Boolean"
    );

    private static $has_one = array(
        'Parent'        => 'CatalogueCategory'
    );

    private static $many_many = array(
        'Products'      => 'CatalogueProduct'
    );

    private static $many_many_extraFields = array(
        'Products' => array('SortOrder' => 'Int')
    );

    private static $extensions = array(
        "Hierarchy"
    );

    private static $summary_fields = array(
        'Title'         => 'Title',
        'URLSegment'    => 'URLSegment'
    );

    private static $casting = array(
        "MenuTitle"     => "Varchar",
        "AllProducts"   => "ArrayList"
    );

    private static $default_sort = '"Sort" ASC';

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
	 * Stub method to get the site config, unless the current class can provide an alternate.
	 *
	 * @return SiteConfig
	 */
	public function getSiteConfig() {

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
	 * Return the link for this {@link Category}
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

    public function getMenuTitle()
    {
        return $this->Title;
    }

    /**
     * Returns TRUE if this is the currently active category being used
     * to handle a request.
     *
     * @return bool
     */
    public function isCurrent()
    {
        return $this->URLSegment == Controller::curr()->request->getURL();
    }


    /**
     * Check if this object is in the currently active section (e.g. it
     * is either current or one of it's children is currently being
     * viewed).
     *
     * @return bool
     */
    public function isSection()
    {
        return $this->isCurrent() || (
            method_exists(Director::get_current_page(), "getAncestors") && in_array($this->URLSegment, Director::get_current_page()->getAncestors()->column('URLSegment'))
        );
    }

    /**
     * Return "link", "current" or section depending on if this page is the current page, or not on the current page but
     * in the current section.
     *
     * @return string
     */
    public function LinkingMode()
    {
        if ($this->isCurrent()) {
            return 'current';
        } elseif ($this->isSection()) {
            return 'section';
        } else {
            return 'link';
        }
    }

    /**
     * Return "link" or "section" depending on if this is the current section.
     *
     * @return string
     */
    public function LinkOrSection()
    {
        return $this->isSection() ? 'section' : 'link';
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
        $template = new SSViewer('BreadcrumbsTemplate');

        return $template->process($this->customise(new ArrayData(array(
            'Pages' => new ArrayList(array_reverse($this->parentStack()))
        ))));
    }

    /**
     * Returns the category in the current stack of the given level.
     * Level(1) will return the category item that we're currently inside, etc.
     */
    public function Level($level)
    {
        $parent = $this;
        $stack = array($parent);
        while ($parent = $parent->Parent) {
            array_unshift($stack, $parent);
        }

        return isset($stack[$level-1]) ? $stack[$level-1] : null;
    }

    /**
     * Return a list of child categories that are not disabled
     *
     * @return ArrayList
     */
    public function EnabledChildren()
    {
        return $this
            ->Children()
            ->filter("Disabled", 0);
    }
    
    /**
     * Return a list of products in that category that are not disabled
     *
     * @return ArrayList
     */
    public function EnabledProducts()
    {
        return $this
            ->Products()
            ->filter("Disabled", 0);
    }
    
    /**
     * Return sorted products in thsi category that are enabled
     *
     * @return ArrayList
     */
    public function SortedProducts()
    {
        return $this
            ->EnabledProducts()
            ->Sort(array(
                "SortOrder" => "ASC",
                "Title" => "ASC"
            ));
    }

    /**
     * Get a list of all products from this category and it's children
     * categories.
     *
     * @return ArrayList
     */
    public function AllProducts($sort = array())
    {
        // Setup the default sort for our products
        if (count($sort) == 0) {
            $sort = array(
                "SortOrder" => "ASC",
                "Title" => "ASC"
            );
        }
        
        $ids = array($this->ID);
        $ids = array_merge($ids, $this->getDescendantIDList());

        $products = CatalogueProduct::get()
            ->filter(array(
                "Categories.ID" => $ids,
                "Disabled" => 0
            ))->sort($sort);

        return $products;
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        // Get a list of available product classes
        $classnames = array_values(ClassInfo::subclassesFor("Category"));
        $category_types = array();

        foreach ($classnames as $classname) {
            $instance = singleton($classname);
            $category_types[$classname] = $instance->i18n_singular_name();
        }
        
        // If CMS Installed, use URLSegmentField, otherwise use text
        // field for URL
        if (class_exists('SiteTreeURLSegmentField')) {
            $baseLink = Controller::join_links(
                Director::absoluteBaseURL()
            );
                        
            $url_field = SiteTreeURLSegmentField::create("URLSegment");
            $url_field->setURLPrefix($baseLink);
        } else {
            $url_field = TextField::create("URLSegment");
        }

        $fields->removeByName("Sort");
        $fields->removeByName("Disabled");
        $fields->removeByName("MetaDescription");
        $fields->removeByName("ExtraMeta");
        $fields->removeByName("Products");

        $fields->addFieldsToTab(
            "Root.Main",
            array(
                $url_field,
                ToggleCompositeField::create(
                    'Metadata',
                    _t('CatalogueAdmin.MetadataToggle', 'Metadata'),
                    array(
                        $metaFieldDesc = TextareaField::create(
                            "MetaDescription",
                            $this->fieldLabel('MetaDescription')
                        ),
                        $metaFieldExtra = TextareaField::create(
                            "ExtraMeta",
                            $this->fieldLabel('ExtraMeta')
                        )
                    )
                )->setHeadingLevel(4)
            )
        );
        
        // Help text for MetaData on page content editor
        $metaFieldDesc
            ->setRightTitle(
                _t(
                    'CatalogueAdmin.MetaDescHelp',
                    "Search engines use this content for displaying search results (although it will not influence their ranking)."
                )
            )->addExtraClass('help');

        $metaFieldExtra
            ->setRightTitle(
                _t(
                    'CatalogueAdmin.MetaExtraHelp',
                    "HTML tags for additional meta information. For example &lt;meta name=\"customName\" content=\"your custom content here\" /&gt;"
                )
            )->addExtraClass('help');
        
        if ($this->exists()) {

            // Ensure that we set the parent ID to the current category
            // when creating a new record 
            $child_config = GridFieldConfig_Catalogue::create("Category", null, "Sort");
            $child_edit = $child_config->getComponentByType('GridFieldDetailForm');

            $self = $this; // PHP 5.3 support - $this can't be used in closures
            $child_edit->setItemEditFormCallback(function($form, $itemRequest) use ($self) {
                $record = $form->getRecord();

                if (!$record->ID) {
                    $parent_field = $form->Fields()->dataFieldByName("ParentID");
                    $parent_field->setValue($self->ID);
                }
            });

            $fields->addFieldToTab(
                'Root.Children',
                GridField::create(
                    "Children",
                    "",
                    Category::get()->filter("ParentID", $this->ID),
                    $child_config
                )
            );

            $fields->addFieldToTab(
                'Root.Products',
                GridField::create(
                    "Products",
                    "",
                    $this->Products(),
                    new GridFieldConfig_CatalogueRelated("Product", null, "SortOrder")
                )
            );
        }

        $fields->addFieldToTab(
            "Root.Settings",
            DropdownField::create(
                "ClassName",
                _t("CatalogueAdmin.CategoryType", "Type of Category"),
                $category_types
            )
        );

        if ($this->exists()) {
            $fields->addFieldToTab(
                "Root.Settings",
                TreeDropdownField::create(
                    "ParentID",
                    _t("CatalogueAdmin.ParentCategory", "Parent Category"),
                    "CatalogueCategory"
                )->setLabelField("Title")
                ->setKeyField("ID")
            );
        }
        
        $this->extend('updateCMSFields', $fields);

        return $fields;
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        // Only call on first creation, ir if title is changed
        if (($this->ID == 0) || $this->isChanged('Title') || !($this->URLSegment)) {
            // Set the URL Segment, so it can be accessed via the controller
            $filter = URLSegmentFilter::create();
            $t = $filter->filter($this->Title);

            // Fallback to generic name if path is empty (= no valid, convertable characters)
            if (!$t || $t == '-' || $t == '-1') {
                $t = "category-{$this->ID}";
            }

            // Ensure that this object has a non-conflicting URLSegment value.
            $existing_cats = CatalogueCategory::get()->filter('URLSegment', $t)->count();
            $existing_products = CatalogueProduct::get()->filter('URLSegment', $t)->count();
            $existing_pages = (class_exists('SiteTree')) ? SiteTree::get()->filter('URLSegment', $t)->count() : 0;

            $count = (int)$existing_cats + (int)$existing_products + (int)$existing_pages;

            $this->URLSegment = ($count) ? $t . '-' . ($count + 1) : $t;
        }
    }

    public function onBeforeDelete()
    {
        parent::onBeforeDelete();

        if ($this->Children()) {
            foreach ($this->Children() as $child) {
                $child->delete();
            }
        }
    }
    
    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        
        // Alter any existing recods that might have the wrong classname
        foreach (CatalogueCategory::get()->filter("ClassName", "CatalogueCategory") as $category) {
            $category->ClassName = "Category";
            $category->write();
        }
    }
    
    public function providePermissions()
    {
        return array(
            "CATALOGUE_ADD_CATEGORIES" => array(
                'name' => 'Add categories',
                'help' => 'Allow user to add categories to catalogue',
                'category' => 'Catalogue',
                'sort' => 50
            ),
            "CATALOGUE_EDIT_CATEGORIES" => array(
                'name' => 'Edit categories',
                'help' => 'Allow user to edit any categories in catalogue',
                'category' => 'Catalogue',
                'sort' => 100
            ),
            "CATALOGUE_DELETE_CATEGORIES" => array(
                'name' => 'Delete categories',
                'help' => 'Allow user to delete any categories in catalogue',
                'category' => 'Catalogue',
                'sort' => 150
            )
        );
    }

    public function canView($member = false)
    {
        return true;
    }

    public function canCreate($member = false)
    {
        if ($member instanceof Member) {
            $memberID = $member->ID;
        } elseif (is_numeric($member)) {
            $memberID = $member;
        } else {
            $memberID = Member::currentUserID();
        }

        if ($memberID && Permission::checkMember($memberID, array("ADMIN", "CATALOGUE_ADD_CATEGORIES"))) {
            return true;
        } elseif ($memberID && $memberID == $this->CustomerID) {
            return true;
        }

        return false;
    }

    public function canEdit($member = false)
    {
        if ($member instanceof Member) {
            $memberID = $member->ID;
        } elseif (is_numeric($member)) {
            $memberID = $member;
        } else {
            $memberID = Member::currentUserID();
        }

        if ($memberID && Permission::checkMember($memberID, array("ADMIN", "CATALOGUE_EDIT_CATEGORIES"))) {
            return true;
        } elseif ($memberID && $memberID == $this->CustomerID) {
            return true;
        }

        return false;
    }

    public function canDelete($member = false)
    {
        if ($member instanceof Member) {
            $memberID = $member->ID;
        } elseif (is_numeric($member)) {
            $memberID = $member;
        } else {
            $memberID = Member::currentUserID();
        }

        if ($memberID && Permission::checkMember($memberID, array("ADMIN", "CATALOGUE_DELETE_CATEGORIES"))) {
            return true;
        } elseif ($memberID && $memberID == $this->CustomerID) {
            return true;
        }

        return false;
    }
}
