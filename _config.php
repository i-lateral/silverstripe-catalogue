<?php

// Ensure compatibility with PHP 7.2 ("object" is a reserved word),
// with SilverStripe 3.6 (using Object) and SilverStripe 3.7 (using SS_Object)
if (!class_exists('SS_Object')) class_alias('Object', 'SS_Object');

// If subsites is installed
if(class_exists('Subsite')) {
    CatalogueProduct::add_extension('SubsiteCatalogueExtension');
    CatalogueCategory::add_extension('SubsiteCatalogueExtension');
    TaxRate::add_extension('SubsiteCatalogueExtension');
    CatalogueAdmin::add_extension('SubsiteMenuExtension');
}

// Setup google sitemaps
$catalogue_enabled = Catalogue::config()->enable_frontend;

if($catalogue_enabled && class_exists("GoogleSitemap")) {
    // Ensure compatibility with PHP 7.2 ("object" is a reserved word),
    // with SilverStripe 3.6 (using Object) and SilverStripe 3.7 (using SS_Object)
    if (!class_exists('SS_Object')) {
        class_alias('Object', 'SS_Object');
    }

    GoogleSitemap::register_dataobject('CatalogueProduct');
    GoogleSitemap::register_dataobject('CatalogueCategory');
}
