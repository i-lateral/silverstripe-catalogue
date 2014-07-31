<?php

// If subsites is installed
if(class_exists('Subsite')) {
    CatalogueProduct::add_extension('SubsiteCatalogueExtension');
    CatalogueCategory::add_extension('SubsiteCatalogueExtension');
    CatalogueAdmin::add_extension('SubsiteMenuExtension');
}
