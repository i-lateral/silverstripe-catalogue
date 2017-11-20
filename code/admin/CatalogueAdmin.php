<?php

namespace ilateral\SilverStripe\Catalogue\Admin;

use SilverStripe\Admin\ModelAdmin;
use ilateral\SilverStripe\Catalogue\GridFieldConfig_Catalogue;
use \Product;
use \Category;

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
        Product::class,
        Category::class
    );

    private static $model_importers = array(
        'Product' => 'CatalogueProductCSVBulkLoader',
    );

    public $showImportForm = array('Product');

    public function init()
    {
        parent::init();
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
            $list = $list->filter('ParentID', 0);
        }
        
        $this->extend('updateList', $list);

        return $list;
    }

    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);
        $fields = $form->Fields();
        $params = $this->request->requestVar('q');
        $gridField = $form->Fields()->fieldByName($this->modelClass);

        if ($this->modelClass == 'Product') {
            $gridField->setConfig(new GridFieldConfig_Catalogue(
                $this->modelClass,
                $this->config()->product_page_length
            ));
        }
        
        // Alterations for Hiarachy on product cataloge
        if ($this->modelClass == 'Category') {
            $gridField->setConfig(new GridFieldConfig_Catalogue(
                $this->modelClass,
                $this->config()->category_page_length,
                "Sort"
            ));
        }

        $this->extend("updateEditForm", $form);

        return $form;
    }
}