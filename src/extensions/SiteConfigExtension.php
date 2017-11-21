<?php

namespace ilateral\SilverStripe\Catalogue\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Assets\Image;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldToolbarHeader;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldSortableHeader;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldConfig;
use ilateral\SilverStripe\Catalogue\Model\TaxRate;

/**
 * Provides additional settings required globally for this module
 *
 * @author i-lateral (http://www.i-lateral.com)
 * @package product-catalogue
 */
class SiteConfigExtension extends DataExtension
{
    
    private static $has_one = [
        'DefaultProductImage'    => Image::class
    ];

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
