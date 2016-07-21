<?php

/**
 * Simple helper class to provide common functions across
 * all libraries
 *
 * @author i-lateral (http://www.i-lateral.com)
 * @package catalogue
 */
class CatalogueHelper extends Object
{
    /**
     * Template names to be removed from the default template list 
     * 
     * @var array
     * @config
     */
    private static $classes_to_remove = array(
        "Object",
        "ViewableData",
        "DataObject",
        "CatalogueProduct",
        "CatalogueCategory"
    );

    /**
     * Get a list of templates for rendering
     *
     * @param $classname ClassName to find tempaltes for
     * @return array Array of classnames
     */
    public static function get_templates_for_class($classname)
    {
        $classes = ClassInfo::ancestry($classname);
        $classes = array_reverse($classes);
        $remove_classes = self::config()->classes_to_remove;
        $return = array();

        array_push($classes, "Catalogue", "Page");

        foreach ($classes as $class) {
            if (!in_array($class, $remove_classes)) {
                $return[] = $class;
            }
        }
        
        return $return;
    }
}