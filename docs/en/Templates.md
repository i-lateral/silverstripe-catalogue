Catalogue Templates
===================

The catalogue module makes use of Silverstripe's templating system
so you can add new Product types easily and then setup associated
templates.

## Adding new templates

A default template for "Product" is provided in this module's
"templates" folder.

Once you add new products, you can use custom templates for them by
adding a template od the samme name as your Product object to your
theme.


## Template Variables

The catalogue module provides several variables to Controller. These
variables should be available globally and accessed via the:

    $Catalogue
    
Template variable. Currently the catalogue variable provides the
following additional variables:

### List of categories

You can pull a list of categories at any time, and then stipulate the
parent ID for this list, if you want to generate a list of categories in
your template as navigation, then you can use the following code:

    <ul>
      <% loop $Catalogue.Categories %>
        <li><a href="$Link">$Title</a></li>
      <% end_loop %>
    </ul>

### List of products

You can also get a list of products at any point by calling:

    <% loop $Catalogue.Products %>
    
    <% end_loop %>

You can also add the ID of a parent category for this list as an
argument.
