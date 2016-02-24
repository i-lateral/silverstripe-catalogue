<?php
/**
 * Loops through all products and Categories, and sets their URL Segments, if
 * they do not already have one
 *
 * @package commerce
 * @subpackage tasks
 */
class CatalogueWriteAllItemsTask extends BuildTask {
	
	protected $title = 'Write All Products and Categories';
	
	protected $description = 'Loop through all products and product categories and re-save them.';
	
	public function run($request) {
	    $products = 0;
	    $categories = 0;
	    
	    // First load all products
		$items = CatalogueProduct::get();
		
		foreach($items as $item) {
		    // Just write product, on before write should deal with the rest
		    $item->write();
		    $products++;
		}
	
	    // Then all categories
		$items = CatalogueCategory::get();
		
		foreach($items as $item) {
		    // Just write category, on before write should deal with the rest
		    $item->write();
		    $categories++;
		}
		
		echo "Wrote $products products and $categories categories.\n";
	}
	
}
