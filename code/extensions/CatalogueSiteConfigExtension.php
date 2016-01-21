<?php
/**
 * Provides additional settings required globally for this module
 *
 * @author i-lateral (http://www.i-lateral.com)
 * @package product-catalogue
 */
class CatalogueSiteConfigExtension extends DataExtension
{
    
    private static $has_one = array(
        'DefaultProductImage'    => 'Image'
    );

    public function updateCMSFields(FieldList $fields)
    {
        // Add config sets
        $fields->addFieldToTab(
            'Root.Catalogue',
            UploadField::create(
                'DefaultProductImage',
                _t("Catalogue.DefaultProductImage", 'Default product image')
            )
        );
        
        // Add config sets
        $fields->addFieldToTab(
            'Root.Catalogue',
            GridField::create(
                'TaxRates',
                _t("Catalogue.TaxRates", "Tax Rates"),
                TaxRate::get(),
                GridFieldConfig::create()->addComponents(
                    new GridFieldToolbarHeader(),
                    new GridFieldAddNewButton('toolbar-header-right'),
                    new GridFieldSortableHeader(),
                    new GridFieldDataColumns(),
                    new GridFieldPaginator(5),
                    new GridFieldEditButton(),
                    new GridFieldDeleteAction(),
                    new GridFieldDetailForm()
                )
            )
        );
    }
}
