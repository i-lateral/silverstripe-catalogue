<?php

namespace ilateral\SilverStripe\Catalogue\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\View\Requirements;

/**
 * Inject extra requirements into the CMS
 * 
 * @author i-lateral (http://www.i-lateral.com)
 * @package orders
 */
class AdminExtension extends Extension
{
    public function init()
    {
        Requirements::css('i-lateral/silverstripe-catalogue: client/dist/css/admin.css');
    }
}