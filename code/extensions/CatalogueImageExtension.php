<?php

/**
 * Extension for Image that allows mapping of products to multiple
 * images
 *
 * @author i-lateral (http://www.i-lateral.com)
 * @package product-catalogue
 */
class CatalogueImageExtension extends DataExtension
{
    private static $belongs_many_many = array(
        'Products'      => 'Product'
    );
}
