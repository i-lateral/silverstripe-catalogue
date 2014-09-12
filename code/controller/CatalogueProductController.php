<?php

/**
 * Controller used to render pages in the catalogue (either categories
 * or pages)
 *
 * @author i-lateral (http://www.i-lateral.com)
 * @package catalogue
 */
class CatalogueProductController extends CatalogueController {

    private static $allowed_actions = array(
        'image',
        'Form'
    );

    /**
     * Return the link to this controller, but force the expanded link to be returned so that form methods and
     * similar will function properly.
     *
     * @return string
     */
    public function Link($action = null) {
        return $this->data()->Link(($action ? $action : true));
    }

    /**
     * The Controller will take the URLSegment parameter from the URL
     * and use that to look up a record.
     */
    public function __construct($dataRecord = null) {
        if(!$dataRecord) {
			$dataRecord = new CatalogueProduct();
			if($this->hasMethod("Title")) $dataRecord->Title = $this->Title();
			$dataRecord->URLSegment = get_class($this);
			$dataRecord->ID = -1;
		}
        
        $this->dataRecord = $dataRecord;
        $this->failover = $this->dataRecord;
        parent::__construct();
    }

    /**
     * Get a list of templates to call and return a default render with
     */
    public function index() {
        $classes = ClassInfo::ancestry($this->dataRecord->class);
        
        array_push($classes, "Catalogue", "Page");

        return $this->renderWith($classes);
    }
    
    /**
     * Get a list of templates to call and return a default render with
     */
    public function image() {
        return $this->index();
    }

    /**
     * The productimage action is used to determine the default image that will
     * appear related to a product
     *
     * @return Image
     */
    public function ProductImage() {
        $images = $this->SortedImages();
        $action = $this->request->param('Action');
        $id = $this->request->param('ID');

        $image = null;

        if($action && $action == "image" && $id)
            $image = $images->filter("ID",$id)->first();

        if(!$image)
            $image = $images->first();

        return $image;
    }
    
    /**
     * Create a form to associate with this product, by default it will
     * be empty, but is intended to be easily extendable to allow "add
     * item to cart", or "get a quote" functionality.
     * 
     * @return Form 
     */
    public function Form() {
        $form = Form::create(
            $this,
            "Form",
            FieldList::create(),
            FieldList::create()
        );
        
        $this->extend("updateForm", $form);
        
        return $form;
    }
}
