<?php

namespace ilateral\SilverStripe\Catalogue\Control;

/**
 * URLController determins what part of Silverstripe (framework, 
 * Catalogue or CMS) will handle the current URL.
 *
 * @author i-lateral (http://www.i-lateral.com)
 * @package catalogue
 */
class CatalogueURLController extends Controller
{
    
    public function init()
    {
        parent::init();
    }
    
    /**
     * Get the appropriate {@link CatalogueProductController} or
     * {@link CatalogueProductController} for handling the relevent
     * object.
     *
     * @param $object Either Product or Category object
     * @param string $action
     * @return CatalogueController
     */
    protected static function controller_for($object, $action = null)
    {
        if ($object->class == 'CatalogueProduct') {
            $controller = "CatalogueProductController";
        } elseif ($object->class == 'CatalogueCategory') {
            $controller = "CatalogueCategoryController";
        } else {
            $ancestry = ClassInfo::ancestry($object->class);
            
            while ($class = array_pop($ancestry)) {
                if (class_exists($class . "_Controller")) {
                    break;
                }
            }
            
            // Find the controller we need, or revert to a default
            if ($class !== null) {
                $controller = "{$class}_Controller";
            } elseif (ClassInfo::baseDataClass($object->class) == "CatalogueProduct") {
                $controller = "CatalogueProductController";
            } elseif (ClassInfo::baseDataClass($object->class) == "CatalogueCategory") {
                $controller = "CatalogueCategoryController";
            }
        }

        if ($action && class_exists($controller . '_' . ucfirst($action))) {
            $controller = $controller . '_' . ucfirst($action);
        }
        
        return class_exists($controller) ? Injector::inst()->create($controller, $object) : $object;
    }

    /**
     * Check catalogue URL's before we get to the CMS (if it exists)
     * 
     * @param SS_HTTPRequest $request
     * @param DataModel|null $model
     * @return SS_HTTPResponse
     */
    public function handleRequest(SS_HTTPRequest $request, DataModel $model)
    {
        $this->request = $request;
		$this->setDataModel($model);
        $catalogue_enabled = Catalogue::config()->enable_frontend;
		
		$this->pushCurrent();

        // Create a response just in case init() decides to redirect
        $this->response = new SS_HTTPResponse();

        $this->init();
        
        // If we had a redirection or something, halt processing.
        if ($this->response->isFinished()) {
            $this->popCurrent();
            return $this->response;
        }
        
        // If DB is not present, build
        if (!DB::isActive() || !ClassInfo::hasTable('CatalogueProduct') || !ClassInfo::hasTable('CatalogueCategory')) {
            return $this->response->redirect(Director::absoluteBaseURL() . 'dev/build?returnURL=' . (isset($_GET['url']) ? urlencode($_GET['url']) : null));
        }
        
        $urlsegment = $request->param('URLSegment');

        $this->extend('onBeforeInit');

        $this->init();

        $this->extend('onAfterInit');

        // Find link, regardless of current locale settings
        if (class_exists('Translatable')) {
            Translatable::disable_locale_filter();
        }
        
        $filter = array(
            'URLSegment' => $urlsegment,
            'Disabled' => 0
        );
        
        if($catalogue_enabled && $object = CatalogueProduct::get()->filter($filter)->first()) {
            $controller = $this->controller_for($object);
        } elseif($catalogue_enabled && $object = CatalogueCategory::get()->filter($filter)->first()) {
            $controller = $this->controller_for($object);
        } elseif (class_exists('ModelAsController')) { // If CMS installed
            $controller = ModelAsController::create();
        } else {
            $controller = Controller::create();
        }
        
        if (class_exists('Translatable')) {
            Translatable::enable_locale_filter();
        }
        
        $result = $controller->handleRequest($request, $model);

        $this->popCurrent();
        return $result;
    }
}
