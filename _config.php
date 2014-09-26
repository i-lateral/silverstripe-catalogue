<?php

// If subsites is installed
if(class_exists('Subsite')) {
    CatalogueProduct::add_extension('SubsiteCatalogueExtension');
    CatalogueCategory::add_extension('SubsiteCatalogueExtension');
    TaxRate::add_extension('SubsiteCatalogueExtension');
    CatalogueAdmin::add_extension('SubsiteMenuExtension');
}

// Setup google sitemaps
if(class_exists("GoogleSitemap")) {
    GoogleSitemap::register_dataobject('CatalogueProduct');
    GoogleSitemap::register_dataobject('CatalogueCategory');
}
