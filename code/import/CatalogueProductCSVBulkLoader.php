<?php

/**
 * Allow slightly more complex product imports from a CSV file
 *
 * @author i-lateral (http://www.i-lateral.com)
 * @package catalogue
 */
class CatalogueProductCSVBulkLoader extends CsvBulkLoader {

    function __construct($objectClass = null) {
        if(!$objectClass) $objectClass = 'Product';

        parent::__construct($objectClass);
    }


    public function processRecord($record, $columnMap, &$results, $preview = false) {

        // Get Current Object
        $objID = parent::processRecord($record, $columnMap, $results, $preview);
        $object = DataObject::get_by_id($this->objectClass, $objID);

        $this->extend("onBeforeProcess", $record, $object);

        // Get all categories by name
        if(isset($record['Categories']) && $record['Categories']) {
            $cat_names = explode(",",$record["Categories"]);
            $categories = CatalogueCategory::get()
                ->filter("Title", $cat_names);

            foreach($categories as $category) {
                $object->Categories()->add($category);
            }
        }

        $this->extend("onAfterProcess", $record, $object);

        $object->destroy();
        unset($object);

        return $objID;
    }

}
