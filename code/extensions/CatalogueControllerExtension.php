<?php

/**
 * Extension for Controller that provide additional methods to all
 * templates 
 *
 * @author i-lateral (http://www.i-lateral.com)
 * @package catalogue
 */
class CatalogueControllerExtension extends Extension {
    
    /**
     * Inject our product catalogue object into the controller
     * 
     * @return ProductCatalogue
     */
    public function getCatalogue() {
        return Catalogue::create();
    }

}
