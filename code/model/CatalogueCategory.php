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
class CatalogueCategory extends DataObject implements PermissionProvider {
    
    private static $db = array(
        'Title'         => 'Varchar',
        'URLSegment'    => 'Varchar',
        'Sort'          => 'Int'
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


    public function getMenuTitle() {
        return $this->Title;
    }

    /**
     * Returns TRUE if this is the currently active category being used
     * to handle a request.
     *
     * @return bool
     */
    public function isCurrent() {
        return $this->URLSegment == Controller::curr()->request->getURL();
    }


    /**
	 * Check if this object is in the currently active section (e.g. it
     * is either current or one of it's children is currently being
     * viewed).
	 *
	 * @return bool
	 */
	public function isSection() {        
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
    public function LinkingMode() {
        if($this->isCurrent()) {
            return 'current';
        } elseif($this->isSection()) {
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
    public function LinkOrSection() {
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
    public function Breadcrumbs($maxDepth = 20) {
        $template = new SSViewer('BreadcrumbsTemplate');

        return $template->process($this->customise(new ArrayData(array(
            'Pages' => new ArrayList(array_reverse($this->parentStack()))
        ))));
    }

    /**
     * Returns the category in the current stack of the given level.
     * Level(1) will return the category item that we're currently inside, etc.
     */
    public function Level($level) {
        $parent = $this;
        $stack = array($parent);
        while($parent = $parent->Parent) {
            array_unshift($stack, $parent);
        }

        return isset($stack[$level-1]) ? $stack[$level-1] : null;
    }

    /**
     * Return sorted images, if no images exist, create a new opbject set
     * with a blank product image in it.
     *
     * @return ArrayList
     */
    public function SortedProducts() {
        return $this->Products()->Sort("SortOrder ASC, \"CatalogueProduct\".\"Title\" ASC");
    }

    /**
     * Get a list of all products from this category and it's children
     * categories.
     *
     * @return ArrayList
     */
    public function AllProducts() {
        $ids = array($this->ID);
        $ids = array_merge($ids, $this->getDescendantIDList());

        $products = CatalogueProduct::get()
            ->filter(array(
                "Categories.ID" => $ids,
                "Disabled" => 0
            ));

        return $products;
    }

    public function getCMSFields() {
        $fields = parent::getCMSFields();

        $fields->removeByName('Sort');
        $fields->removeByName('Products');

        $url_field = TextField::create('URLSegment')
            ->setReadonly(true)
            ->performReadonlyTransformation();

        $parent_field = TreeDropdownField::create(
            'ParentID',
            'Parent Category',
            'CatalogueCategory'
        )->setLabelField("Title");
        
        
        $gridconfig = new GridFieldConfig_RelationEditor();
        $gridconfig->addComponent(new GridFieldOrderableRows('SortOrder'));
        
        $products_field = GridField::create(
            "Products",
            "",
            $this->Products(),
            $gridconfig
        );

        // Add fields to the CMS
        $fields->addFieldToTab('Root.Main', TextField::create('Title'));
        $fields->addFieldToTab('Root.Main', $url_field);
        $fields->addFieldToTab('Root.Main', $parent_field);
        $fields->addFieldToTab('Root.Products', $products_field);

        $this->extend('updateCMSFields', $fields);

        return $fields;
    }

    public function onBeforeWrite() {
        parent::onBeforeWrite();

        // Only call on first creation, ir if title is changed
        if(($this->ID == 0) || $this->isChanged('Title') || !($this->URLSegment)) {
            // Set the URL Segment, so it can be accessed via the controller
            $filter = URLSegmentFilter::create();
            $t = $filter->filter($this->Title);

            // Fallback to generic name if path is empty (= no valid, convertable characters)
            if(!$t || $t == '-' || $t == '-1') $t = "category-{$this->ID}";

            // Ensure that this object has a non-conflicting URLSegment value.
            $existing_cats = CatalogueCategory::get()->filter('URLSegment',$t)->count();
            $existing_products = CatalogueProduct::get()->filter('URLSegment',$t)->count();
            $existing_pages = (class_exists('SiteTree')) ? SiteTree::get()->filter('URLSegment',$t)->count() : 0;

            $count = (int)$existing_cats + (int)$existing_products + (int)$existing_pages;

            $this->URLSegment = ($count) ? $t . '-' . ($count + 1) : $t;
        }
    }

    public function onBeforeDelete() {
        parent::onBeforeDelete();

        if($this->Children()) {
            foreach($this->Children() as $child) {
                $child->delete();
            }
        }
    }
    
    public function providePermissions() {
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

    public function canView($member = false) {
        return true;
    }

    public function canCreate($member = false) {
        if($member instanceof Member)
            $memberID = $member->ID;
        else if(is_numeric($member))
            $memberID = $member;
        else
            $memberID = Member::currentUserID();

        if($memberID && Permission::checkMember($memberID, array("ADMIN", "CATALOGUE_ADD_CATEGORIES")))
            return true;
        else if($memberID && $memberID == $this->CustomerID)
            return true;

        return false;
    }

    public function canEdit($member = false) {
        if($member instanceof Member)
            $memberID = $member->ID;
        else if(is_numeric($member))
            $memberID = $member;
        else
            $memberID = Member::currentUserID();

        if($memberID && Permission::checkMember($memberID, array("ADMIN", "CATALOGUE_EDIT_CATEGORIES")))
            return true;
        else if($memberID && $memberID == $this->CustomerID)
            return true;

        return false;
    }

    public function canDelete($member = false) {
        if($member instanceof Member)
            $memberID = $member->ID;
        else if(is_numeric($member))
            $memberID = $member;
        else
            $memberID = Member::currentUserID();

        if($memberID && Permission::checkMember($memberID, array("ADMIN", "CATALOGUE_DELETE_CATEGORIES")))
            return true;
        else if($memberID && $memberID == $this->CustomerID)
            return true;

        return false;
    }
}
