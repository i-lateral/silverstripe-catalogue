<?php

/**
 * Top level controller that all catalogue controllers should extend.
 * There are some methods that have to be taken from ContentController
 * to allow the catalogue module to operate with just the core
 * framework, or with the CMS.
 *
 * @author i-lateral (http://www.i-lateral.com)
 * @package catalogue
 */
abstract class CatalogueController extends Controller
{

    protected $dataRecord;

    /**
     * Returns the associated database record
     */
    public function data()
    {
        return $this->dataRecord;
    }

    public function getDataRecord()
    {
        return $this->data();
    }

    public function setDataRecord($dataRecord)
    {
        $this->dataRecord = $dataRecord;
        return $this;
    }
    
    public function init()
    {
        parent::init();
        
        // Use theme from the site config
        if (($config = SiteConfig::current_site_config()) && $config->Theme) {
            Config::inst()->update('SSViewer', 'theme', $config->Theme);
        }
    }
    
    /**
     * Return the link to this controller, but force the expanded link to be returned so that form methods and
     * similar will function properly.
     *
     * @param string|null $action Action to link to.
     * @return string
     */
    public function Link($action = null)
    {
        return $this->data()->Link(($action ? $action : true));
    }
    
    /**
     * Return the title, description, keywords and language metatags.
     * 
     * @todo Move <title> tag in separate getter for easier customization and more obvious usage
     * 
     * @param boolean|string $includeTitle Show default <title>-tag, set to false for custom templating
     * @return string The XHTML metatags
     */
    public function MetaTags($includeTitle = true)
    {
        $tags = "";
        if ($includeTitle === true || $includeTitle == 'true') {
            $tags .= "<title>" . Convert::raw2xml($this->Title) . "</title>\n";
        }

        $generator = trim(Config::inst()->get('SiteTree', 'meta_generator'));
        if (!empty($generator)) {
            $tags .= "<meta name=\"generator\" content=\"" . Convert::raw2att($generator) . "\" />\n";
        }

        $charset = Config::inst()->get('ContentNegotiator', 'encoding');
        $tags .= "<meta http-equiv=\"Content-type\" content=\"text/html; charset=$charset\" />\n";
        if ($this->MetaDescription) {
            $tags .= "<meta name=\"description\" content=\"" . Convert::raw2att($this->MetaDescription) . "\" />\n";
        }
        if ($this->ExtraMeta) {
            $tags .= $this->ExtraMeta . "\n";
        }
        
        if (Permission::check('CMS_ACCESS_CMSMain') && in_array('CMSPreviewable', class_implements($this)) && !$this instanceof ErrorPage) {
            $tags .= "<meta name=\"x-page-id\" content=\"{$this->ID}\" />\n";
            $tags .= "<meta name=\"x-cms-edit-link\" content=\"" . $this->CMSEditLink() . "\" />\n";
        }

        $this->extend('MetaTags', $tags);

        return $tags;
    }

    /**
     * If content controller exists, return it's menu function
     * @param int $level Menu level to return.
     * @return ArrayList
     */
    public function getMenu($level = 1)
    {
        if (class_exists(ContentController::class)) {
            $controller = ContentController::singleton();
            return $controller->getMenu($level);
        }
    }

    public function Menu($level)
    {
        return $this->getMenu();
    }

    /**
     * Process and render search results. This has been hacked a bit to load
     * products into the list (if they exists). Will need to come up with a more
     * elegant solution to dealing with complex searches of objects though.
     *
     * @param array $data The raw request data submitted by user
     * @param SearchForm $form The form instance that was submitted
     * @param SS_HTTPRequest $request Request generated for this action
     */
    public function results($data, $form, $request)
    {
        $results = $form->getResults();

        // For the moment this will also need to be added to your
        // Page_Controller::results() method (until a more elegant solution can
        // be found
        if (class_exists("Product")) {
            $products = Product::get()->filterAny(array(
                "Title:PartialMatch" => $data["Search"],
                "StockID" => $data["Search"],
                "Content:PartialMatch" => $data["Search"]
            ));

            $results->merge($products);
        }

        $results = $results->sort("Title", "ASC");

        $data = array(
            'Results' => PaginatedList::create($results, $this->request),
            'Query' => $form->getSearchQuery(),
            'Title' => _t('SearchForm.SearchResults', 'Search Results')
        );

        return $this
            ->owner
            ->customise($data)
            ->renderWith(array(
                'Page_results',
                'SearchResults',
                'Page'
            ));
    }
    
    public function SiteConfig()
    {
        if (method_exists($this->dataRecord, 'getSiteConfig')) {
            return $this->dataRecord->getSiteConfig();
        } else {
            return SiteConfig::current_site_config();
        }
    }
}
