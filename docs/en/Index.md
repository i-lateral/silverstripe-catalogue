Silverstripe Catalogue Module
=============================

The Silverstripe Cataqlogue provides an interface to create and manage
"Products" and "Categories" in isolation of the CMS module.

Most ecommerce systems provide a tabular interface to easily manage
product catalogues (including bulk import, export and alterations), but
most Silverstripe modules seem to focus on turning products into a type
of page object (therefore requireing the CMS module).

If the CMS module is installed, then this module will attempt to correct
URL's of products and categories so that they do not intefere with Page
object URLs.

**NOTE** Please be aware, this module only provides a product catalogue
to Silverstripe, including Stock ID's, prices, SEO friendly URLS, etc.

If you want full e-commerce, you will need to add the [orders](https://github.com/i-lateral/silverstripe-orders)
and [checkout](https://github.com/i-lateral/silverstripe-checkout) module
(or install the [commerce](https://github.com/i-lateral/silverstripe-commerce)
module instead).

## Further reading

* [Installation & Setup](Installation.md)
* [Configuration](Configuration.md)
* [Templates & variables](Templates.md)
