<?php

namespace ilateral\SilverStripe\Catalogue\Extensions;

use SilverStripe\Core\Extension;
use ilateral\SilverStripe\Catalogue\Catalogue;

/**
 * Extension for Controller that provide additional methods to all
 * templates 
 *
 * @author i-lateral (http://www.i-lateral.com)
 * @package catalogue
 */
class ControllerExtension extends Extension
{
    
    /**
     * @return void
     */
    public function onBeforeInit()
    {
        // Check if we are runing a dev build, if so check if DB needs
        // upgrading
        $controller = $this->owner->request->param("Controller");
        $action = $this->owner->request->param("Action");
        global $project;
        
        // Only check if the DB needs upgrading on a dev build
        if ($controller == "DevelopmentAdmin" && $action == "build") {
            
            // Now check if the files we need are installed
            // Check if we have the files we need, if not, create them
            if (!class_exists("Category")) {
                copy(BASE_PATH . "/catalogue/scaffold/Category", BASE_PATH . "/{$project}/code/model/Category.php");
            }
            
            if (!class_exists("Category_Controller")) {
                copy(BASE_PATH . "/catalogue/scaffold/Category_Controller", BASE_PATH . "/{$project}/code/control/Category_Controller.php");
            }
            
            if (!class_exists("Product")) {
                copy(BASE_PATH . "/catalogue/scaffold/Product", BASE_PATH . "/{$project}/code/model/Product.php");
            }
            
            if (!class_exists("Product_Controller")) {
                copy(BASE_PATH . "/catalogue/scaffold/Product_Controller", BASE_PATH . "/{$project}/code/control/Product_Controller.php");
            }
        }
    }
    
    /**
     * Inject our product catalogue object into the controller
     * 
     * @return ProductCatalogue
     */
    public function getCatalogue()
    {
        return Catalogue::create();
    }
}
