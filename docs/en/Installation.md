Installing the Cataloge module
==============================

## Via composer

The default way to do this is to use composer. If you are doing this
you need to add:

    "i-lateral/silverstripe-catalogue":"*"

To your project's composer.json.

At the moment this module is still in heavy development. Once this cycle
stabalises we will look into adding stable releases.

## From source / manuallly

You can download this module either direct from the Silverstripe addons
directory or Github.

If you do, then follow this process:

* Download a Zip or Tarball of this module
* Extract the module into a directory callled "catalogue" in your project
* Run http://www.yoursite.com/dev/build?flush=all

## Add a "Product" and "Category" objects and controllers

The catalogue module works in a similar way to the CMS module. Once
installed you will need to add a "Product" and a "Category" (that extend
CatalogueProduct and CatalogueCategory) object to your "mysite" folder.

For example:

    /projectroot/mysite/code/Product.php
    
    <?php
    
    class Product extends CatalogueProduct {
    
      private static $db = array(
          "StockLevel" => "Int"
      );
    
    }
    
    /projectroot/mysite/code/Category.php
    
    <?php
    
    class Category extends CatalogueCategory {
    
      private static $has_one = array(
          "Image" => "Image"
      );
    
    }
    
**Note** You will need to add this in order to add a product through the
admin.

Once you have done this, you also need to add a Product_Controller and
Catagory_Controller object to your mysite folder that extend
CatalogueProductController and CatalogueCategoryController, EG:

    /projectroot/mysite/code/Product_Controller.php
    
    <?php
    
    class Product_Controller extends CatalogueProductController {    
    
        public function index() {
            // Some stuff happens here
            return parent::index();
        }
    
    }
    
    /projectroot/mysite/code/Category_Controller.php
    
    <?php
    
    class Category_Controller extends CatalogueCategoryController {
        
        public function index() {
            // Some stuff happens here
            return parent::index();
        }
    }


## Setting up your catalogue

Once you have installed the module, you can begine setting up products
and categories. To do this, log into your admin, you should now see a
"Catalogue" tab to the left. Click this.

Once in the Catalogue admin, you will see "Products" and "Categories" in
the top right. From here you can fairly easily add new Products and 
Categories.

### Adding nested Categories

This module supports a hierachy for categories. To add "Sub categories"
you must click the arrow to the left of a category name. The category
tab will reload showing you the children of this category. Once this has
happened, you can add a new category and it will automatically be added
as a child.
