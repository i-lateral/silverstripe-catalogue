<?php

namespace ilateral\SilverStripe\Catalogue\Control;

use SilverStripe\ORM\PaginatedList;
use ilateral\SilverStripe\Catalogue\Helpers\Helper as CatalogueHelper;
use ilateral\SilverStripe\Catalogue\Model\CatalogueCategory;

/**
 * Controller used to render pages in the catalogue (either categories or pages)
 *
 * @author i-lateral (http://www.i-lateral.com)
 * @package catalogue
 */
class CatalogueCategoryController extends CatalogueController
{


    /**
     * Get a paginated list of products contained in this category
     *
     * @return PaginatedList
     */
    public function PaginatedProducts($limit = 10)
    {
        return PaginatedList::create(
            $this->SortedProducts(),
            $this->request
        )->setPageLength($limit);
    }


    /**
     * Get a paginated list of all products at this level and below
     *
     * @return PaginatedList
     */
    public function PaginatedAllProducts($limit = 10)
    {
        return PaginatedList::create(
            $this->AllProducts(),
            $this->request
        )->setPageLength($limit);
    }

    /**
     * The Controller will take the URLSegment parameter from the URL
     * and use that to look up a record.
     */
    public function __construct($dataRecord = null)
    {
        if (!$dataRecord) {
            $dataRecord = new CatalogueCategory();
            if ($this->hasMethod("Title")) {
                $dataRecord->Title = $this->Title();
            }
            $dataRecord->URLSegment = get_class($this);
            $dataRecord->ID = -1;
        }
        
        $this->dataRecord = $dataRecord;
        $this->failover = $this->dataRecord;
        parent::__construct();
    }

    /**
     * Get a list of templates to call and return a default render with
     */
    public function index()
    {   
        $this->extend("onBeforeIndex");

        $classes = CatalogueHelper::get_templates_for_class($this->dataRecord->class);

        return $this->renderWith($classes);
    }
    
    /**
     * Returns a fixed navigation menu of the given level.
     * @return SS_List
     */
    public function CategoryMenu($level = 1)
    {
        if ($level == 1) {
            $result = CatalogueCategory::get()->filter(array(
                "ParentID" => 0
            ));
        } else {
            $parent = $this->data();
            $stack = array($parent);

            if ($parent) {
                while ($parent = $parent->Parent) {
                    array_unshift($stack, $parent);
                }
            }

            if (isset($stack[$level-2])) {
                $result = $stack[$level-2]->Children();
            }
        }

        $visible = array();

        if (isset($result)) {
            foreach ($result as $item) {
                if ($item->canView()) {
                    $visible[] = $item;
                }
            }
        }

        return ArrayList::create($visible);
    }
}
