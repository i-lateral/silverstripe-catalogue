<?php

class CatalogueLeftAndMainExtension extends LeftAndMainExtension {
    public function init() {
        parent::init();

        Requirements::css('catalogue/css/admin.css');
    }
}
