<?php

/**
 * Object designed to allow injection of catalogue global settings into
 * templates without having to flood the base controller with methods   
 * 
 * @author i-lateral (http://www.i-lateral.com)
 * @package catalogue
 */
class Catalogue extends ViewableData {
    
    /**
     * Show price including tax in catalogue and product pages?
     * 
     * @var Boolean
     * @config
     */
    private static $price_includes_tax = true;
    
    
    /**
     * Gets a list of all Categories, either top level (default) or
     * from a sub level
     *
     * @param Parent the ID of a parent cetegory
     * @return SS_List
     */
    public function Categories($ParentID = 0) {
        return CatalogueCategory::get()->filter("ParentID", $ParentID);
    }

    /**
     * Get a full list of products, filtered by a category if provided.
     *
     * @param ParentCategoryID the ID of the parent category
     */
    public function Products($ParentCategoryID = 0) {
        $products = CatalogueProduct::get()
            ->filter(array(
                "ParentID" => $ParentCategoryID,
                "Disabled" => 0
            ));

        return $products;
    }
}
