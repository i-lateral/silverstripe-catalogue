<?php
/**
 * Provides additional settings required globally for this module
 *
 * @author i-lateral (http://www.i-lateral.com)
 * @package product-catalogue
 */
class CatalogueSiteConfigExtension extends DataExtension {
    
    private static $has_one = array(
        'DefaultProductImage'    => 'Image'
    );

    public function updateCMSFields(FieldList $fields) {
        $product_image_field = UploadField::create(
            'DefaultProductImage',
            _t("ProductCatalogue.DefaultProductImage", 'Default product image')
        );

        // Add config sets
        $fields->addFieldToTab('Root.Main', $product_image_field);
    }
    
}
