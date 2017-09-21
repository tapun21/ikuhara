<?php

use Doctrine\DBAL\Connection;
/**
 * search interceptor for shopware 5 and following
 * uses SearchBundle
 */
class Shopware_Plugins_Frontend_Boxalino_SearchInterceptor
    extends Shopware_Plugins_Frontend_Boxalino_Interceptor {

    /**
     * @var Shopware\Components\DependencyInjection\Container
     */
    private $container;

    /**
     * @var Enlight_Event_EventManager
     */
    protected $eventManager;

    /**
     * @var FacetHandlerInterface[]
     */
    protected $facetHandlers;

    /**
     * @var array
     */
    protected $facetOptions = [];

    /**
     * constructor
     * @param Shopware_Plugins_Frontend_Boxalino_Bootstrap $bootstrap
     */
    public function __construct(Shopware_Plugins_Frontend_Boxalino_Bootstrap $bootstrap) {
        parent::__construct($bootstrap);
        $this->container = Shopware()->Container();
        $this->eventManager = Enlight()->Events();
    }

    /**
     * perform autocompletion suggestion
     * @param Enlight_Event_EventArgs $arguments
     * @return boolean
     */
    public function ajaxSearch(Enlight_Event_EventArgs $arguments) {

        if (!$this->Config()->get('boxalino_active') || !$this->Config()->get('boxalino_search_enabled') || !$this->Config()->get('boxalino_autocomplete_enabled')) {
            return null;
        }
        $this->init($arguments);

        Enlight()->Plugins()->Controller()->Json()->setPadding();

        $term = $this->getSearchTerm();
        if (empty($term) || strlen($term) < $this->Config()->get('MinSearchLenght')) {
            return;
        }

        $with_blog = $this->Config()->get('boxalino_blog_search_enabled');
        $templateProperties = $this->Helper()->autocomplete($term, $with_blog);
        $this->View()->loadTemplate('frontend/search/ajax.tpl');
        $this->View()->addTemplateDir($this->Bootstrap()->Path() . 'Views/emotion/');
        $this->View()->extendsTemplate('frontend/plugins/boxalino/ajax.tpl');
        $this->View()->assign($templateProperties);
        return false;
    }

    public function listingAjax(Enlight_Event_EventArgs $arguments) {

        if (!$this->Config()->get('boxalino_active') || !$this->Config()->get('boxalino_navigation_enabled')) {
            return null;
        }

        $this->init($arguments);
        if($this->Request()->getActionName() == 'productNavigation'){
            return null;
        }
        $viewData = $this->View()->getAssign();
        $catId = $this->Request()->getParam('sCategory');
        $streamId = $this->findStreamIdByCategoryId($catId);
        if ($streamId != null || !isset($viewData['sArticles']) || count($viewData['sArticles']) == 0) {
            return null;
        }
        $filter = array();
        if($supplier = $this->Request()->getParam('sSupplier')) {
            $supplier_name = $this->getSupplierName($supplier);
            $filter['products_brand'] = [$supplier_name];
        }
        $listingCount = $this->Request()->getActionName() == 'listingCount';
        $context  = $this->get('shopware_storefront.context_service')->getProductContext();
        /* @var Shopware\Bundle\SearchBundle\Criteria $criteria */
        $criteria = $this->get('shopware_search.store_front_criteria_factory')
            ->createSearchCriteria($this->Request(), $context);
        $hitCount = $criteria->getLimit();
        $pageOffset = $criteria->getOffset();
        $sort =  $this->getSortOrder($criteria, $viewData['sSort'], true);
        $facets = $this->createFacets($criteria, $context);
        $queryText = $this->Request()->getParams()['q'];
        $options = $this->getFacetConfig($facets);
        $this->Helper()->addSearch($queryText, $pageOffset, $hitCount, 'product', $sort, $options, $filter);
        $articles = $this->Helper()->getLocalArticles($this->Helper()->getHitFieldValues('products_ordernumber'));
        $viewData['sArticles'] = $articles;
        if ($listingCount) {
            $this->Controller()->Response()->setBody('{"totalCount":' . $this->Helper()->getTotalHitCount() . '}');
            return false;
        }
        $this->View()->assign($viewData);
        return false;
    }

    public function listing(Enlight_Event_EventArgs $arguments) {

        if (!$this->Config()->get('boxalino_active') || !$this->Config()->get('boxalino_navigation_enabled')) {
            return null;
        }

        $this->init($arguments);
        $viewData = $this->View()->getAssign();
        $catId = $this->Request()->getParam('sCategory');
        $streamId = $this->findStreamIdByCategoryId($catId);
        if ($streamId != null || !isset($viewData['sArticles']) || count($viewData['sArticles']) == 0) {
            return null;
        }

        $filter = array();
        if(isset($viewData['manufacturer']) && !empty($viewData['manufacturer'])) {
            $filter['products_brand'] = [$viewData['manufacturer']->getName()];
        }
        $context  = $this->get('shopware_storefront.context_service')->getProductContext();
        /* @var Shopware\Bundle\SearchBundle\Criteria $criteria */
        $criteria = $this->get('shopware_search.store_front_criteria_factory')
            ->createSearchCriteria($this->Request(), $context);
        $criteria->removeCondition("term");
        $criteria->removeBaseCondition("search");
        $facets = $this->createFacets($criteria, $context);
        $options = $this->getFacetConfig($facets);
        $sort = $this->getSortOrder($criteria, $viewData['sSort'], true);
        $hitCount = $criteria->getLimit();
        $pageOffset = $criteria->getOffset();
        $this->Helper()->addSearch('', $pageOffset, $hitCount, 'product', $sort, $options, $filter);
        $facets = $this->updateFacetsWithResult($facets);
        $articles = $this->Helper()->getLocalArticles($this->Helper()->getHitFieldValues('products_ordernumber'));
        $this->View()->addTemplateDir($this->Bootstrap()->Path() . 'Views/emotion/');
        if ($this->Config()->get('boxalino_navigation_sorting') == true) {
            $this->View()->extendsTemplate('frontend/plugins/boxalino/listing/actions/action-sorting.tpl');
        }
        $this->View()->extendsTemplate('frontend/plugins/boxalino/listing/filter/facet-value-list.tpl');
        $this->View()->extendsTemplate('frontend/plugins/boxalino/listing/index.tpl');
        $totalHitCount = $this->Helper()->getTotalHitCount();
        $templateProperties = array(
            'bxFacets' => $this->Helper()->getFacets(),
            'criteria' => $criteria,
            'facets' => $facets,
            'sNumberArticles' => $totalHitCount,
            'sArticles' => $articles,
            'facetOptions' => $this->facetOptions
        );
        $templateProperties = array_merge($viewData, $templateProperties);
        $this->View()->assign($templateProperties);
        return false;
    }
    /**
     * perform search
     * @param Enlight_Event_EventArgs $arguments
     * @return boolean
     */
    public function search(Enlight_Event_EventArgs $arguments) {

        if (!$this->Config()->get('boxalino_active') || !$this->Config()->get('boxalino_search_enabled')) {
            return null;
        }

        $this->init($arguments);
        $term = $this->getSearchTerm();

        // Check if we have a one to one match for ordernumber, then redirect
        $location = $this->searchFuzzyCheck($term);
        if (!empty($location)) {
            return $this->Controller()->redirect($location);
        }

        /* @var ProductContextInterface $context */
        $context  = $this->get('shopware_storefront.context_service')->getProductContext();
        /* @var Shopware\Bundle\SearchBundle\Criteria $criteria */
        $criteria = $this->get('shopware_search.store_front_criteria_factory')->createSearchCriteria($this->Request(), $context);

        // discard search / term conditions from criteria, such that _all_ facets are properly requested
        $criteria->removeCondition("term");
        $criteria->removeBaseCondition("search");
        $facets = $this->createFacets($criteria, $context);
        $options = $this->getFacetConfig($facets);
        $sort =  $this->getSortOrder($criteria);
        $config = $this->get('config');
        $pageCounts = array_values(explode('|', $config->get('fuzzySearchSelectPerPage')));
        $hitCount = $criteria->getLimit();
        $pageOffset = $criteria->getOffset();
        $bxHasOtherItems = false;
        $this->Helper()->addSearch($term, $pageOffset, $hitCount, 'product', $sort, $options);
        if($config->get('boxalino_blog_search_enabled')){
            $blogOffset = ($this->Request()->getParam('sBlogPage', 1) -1)*($hitCount);
            $this->Helper()->addSearch($term, $blogOffset, $hitCount, 'blog');
            $bxHasOtherItems = $this->Helper()->getTotalHitCount('blog') > 0;
        }

        $corrected = false;
        $articles = array();
        $no_result_articles = array();
        $sub_phrases = array();
        $totalHitCount = 0;
        $sub_phrase_limit = $config->get('boxalino_search_subphrase_result_limit');
        if ($this->Helper()->areThereSubPhrases() && $sub_phrase_limit > 0) {
            $sub_phrase_queries = array_slice(array_filter($this->Helper()->getSubPhrasesQueries()), 0, $sub_phrase_limit);
            foreach ($sub_phrase_queries as $query){
                $ids = array_slice($this->Helper()->getSubPhraseFieldValues($query, 'products_ordernumber'), 0, $config->get('boxalino_search_subphrase_product_limit'));
                $suggestion_articles = [];
                if (count($ids) > 0) {
                    $suggestion_articles = $this->Helper()->getLocalArticles($ids);
                }
                $hitCount = $this->Helper()->getSubPhraseTotalHitCount($query);
                $sub_phrases[] = array('hitCount'=> $hitCount, 'query' => $query, 'articles' => $suggestion_articles);
            }
            $facets = array();
        } else {
            if ($totalHitCount = $this->Helper()->getTotalHitCount()) {
                if ($this->Helper()->areResultsCorrected()) {
                    $corrected = true;
                    $term = $this->Helper()->getCorrectedQuery();
                }
                $ids = $this->Helper()->getHitFieldValues('products_ordernumber');
                $articles = $this->Helper()->getLocalArticles($ids);
                $category = $this->Helper()->getFacets()->getParentCategories();
                if (!empty($category)) {
                    end($category);
                    $id = (int) key($category);
                    if($id != Shopware()->Shop()->getCategory()->getId()) {
                        $this->Request()->setParam("sCategory", $id);
                        $criteria = $this->get('shopware_search.store_front_criteria_factory')
                            ->createSearchCriteria($this->Request(), $context);
                        $criteria->removeCondition("term");
                        $criteria->removeBaseCondition("search");
                        $facets['category'] = $this->createFacets($criteria, $context, 'category');
                    }
                }
                $facets = $this->updateFacetsWithResult($facets);
            } else {

                if ($config->get('boxalino_noresults_recommendation_enabled')) {
                    $this->Helper()->resetRequests();
                    $this->Helper()->flushResponses();
                    $min = $config->get('boxalino_noresults_recommendation_min');
                    $max = $config->get('boxalino_noresults_recommendation_max');
                    $choiceId = $config->get('boxalino_noresults_recommendation_name');
                    $this->Helper()->getRecommendation($choiceId, $max, $min, 0, [], '', false);
                    $hitIds = $this->Helper()->getRecommendation($choiceId);
                    $no_result_articles = $this->Helper()->getLocalArticles($hitIds);
                }
                $facets = array();
            }
        }
        $request = $this->Request();
        $params = $request->getParams();
        $params['sSearchOrginal'] = $term;
        $params['sSearch'] = $term;

        // Assign result to template
        $this->View()->loadTemplate('frontend/search/fuzzy.tpl');
        $this->View()->addTemplateDir($this->Bootstrap()->Path() . 'Views/emotion/');
        $this->View()->extendsTemplate('frontend/plugins/boxalino/listing/actions/action-pagination.tpl');
        $this->View()->extendsTemplate('frontend/plugins/boxalino/search/fuzzy.tpl');
        $this->View()->extendsTemplate('frontend/plugins/boxalino/listing/filter/facet-value-list.tpl');
        $this->View()->extendsTemplate('frontend/plugins/boxalino/listing/index.tpl');
        $no_result_title = Shopware()->Snippets()->getNamespace('boxalino/intelligence')->get('search/noresult');
        $templateProperties = array_merge(array(
            'bxFacets' => $this->Helper()->getFacets(),
            'term' => $term,
            'corrected' => $corrected,
            'bxNoResult' => count($no_result_articles) > 0,
            'BxData' => [
                'article_slider_title'=> $no_result_title,
                'no_border'=> true,
                'article_slider_type' => 'selected_article',
                'values' => $no_result_articles,
                'article_slider_max_number' => count($no_result_articles),
                'article_slider_arrows' => 1
            ],
            'criteria' => $criteria,
            'facets' => $facets,
            'sPage' => $request->getParam('sPage', 1),
            'sSort' => $request->getParam('sSort', 7),
            'sTemplate' => $params['sTemplate'],
            'sPerPage' => $pageCounts,
            'sRequests' => $params,
            'shortParameters' => $this->get('query_alias_mapper')->getQueryAliases(),
            'pageSizes' => $pageCounts,
            'ajaxCountUrlParams' => ['sCategory' => $context->getShop()->getCategory()->getId()],
            'sSearchResults' => array(
                'sArticles' => $articles,
                'sArticlesCount' => $totalHitCount
            ),
            'productBoxLayout' => $config->get('searchProductBoxLayout'),
            'bxHasOtherItemTypes' => $bxHasOtherItems,
            'bxActiveTab' => $request->getParam('bxActiveTab', 'article'),
            'bxSubPhraseResults' => $sub_phrases,
            'facetOptions' => $this->facetOptions
        ), $this->getSearchTemplateProperties($hitCount));
        $this->View()->assign($templateProperties);
        return false;
    }

    private function getSearchTemplateProperties($hitCount)
    {
        $props = array();
        $total = $this->Helper()->getTotalHitCount('blog');
        if ($total == 0) {
            return $props;
        }
        $sPage = $this->Request()->getParam('sBlogPage', 1);
        $entity_ids = $this->Helper()->getEntitiesIds('blog');
        if (!count($entity_ids)) {
            return $props;
        }
        $ids = array();
        foreach ($entity_ids as $id) {
            $ids[] = str_replace('blog_', '', $id);
        }
        $count = count($ids);
        $numberPages = ceil($count > 0 ? $total / $hitCount : 0);
        $props['bxBlogCount'] = $total;
        $props['sNumberPages'] = $numberPages;

        $repository = Shopware()->Models()->getRepository('Shopware\Models\Blog\Blog');
        $builder = $repository->getListQueryBuilder(array(), array());
        $query = $builder
            ->andWhere($builder->expr()->in('blog.id', $ids))
            ->getQuery();
        $pages = array();

        if ($numberPages > 1) {
            $params = array_merge($this->Request()->getParams(), array('bxActiveTab' => 'blog'));
            for ($i = 1; $i <= $numberPages; $i++) {
                $pages["numbers"][$i]["markup"] = $i == $sPage;
                $pages["numbers"][$i]["value"] = $i;
                $pages["numbers"][$i]["link"] = $this->assemble(array_merge($params, array('sBlogPage' => $i)));
            }
            if ($sPage > 1) {
                $pages["previous"] = $this->assemble(array_merge($params, array('sBlogPage' => $sPage - 1)));
            } else {
                $pages["previous"] = null;
            }
            if ($sPage < $numberPages) {
                $pages["next"] = $this->assemble(array_merge($params, array('sBlogPage' => $sPage + 1)));
            } else {
                $pages["next"] = null;
            }
        }

        $props['sBlogPage'] = $sPage;
        $props['sPages'] = $pages;
        $blogArticles = $this->enhanceBlogArticles($query->getArrayResult());
        $props['sBlogArticles'] = $blogArticles;
        return $props;
    }

    private function assemble($params) {
        $p = $this->Request()->getBasePath() . $this->Request()->getPathInfo();
        if (empty($params)) return $p;

        $ignore = array("module" => 1, "controller" => 1, "action" => 1);
        $kv = [];
        array_walk($params, function($v, $k) use (&$kv, &$ignore) {
            if ($ignore[$k]) return;

            $kv[] = $k . '=' . $v;
        });
        return $p . "?" . implode('&', $kv);
    }

    // mostly copied from Frontend/Blog.php#indexAction
    private function enhanceBlogArticles($blogArticles) {
        $mediaIds = array_map(function ($blogArticle) {
            if (isset($blogArticle['media']) && $blogArticle['media'][0]['mediaId']) {
                return $blogArticle['media'][0]['mediaId'];
            }
        }, $blogArticles);
        $context = $this->Bootstrap()->get('shopware_storefront.context_service')->getShopContext();
        $medias = $this->Bootstrap()->get('shopware_storefront.media_service')->getList($mediaIds, $context);

        foreach ($blogArticles as $key => $blogArticle) {
            //adding number of comments to the blog article
            $blogArticles[$key]["numberOfComments"] = count($blogArticle["comments"]);

            //adding tags and tag filter links to the blog article
//             $tagsQuery = $this->repository->getTagsByBlogId($blogArticle["id"]);
//             $tagsData = $tagsQuery->getArrayResult();
//             $blogArticles[$key]["tags"] = $this->addLinksToFilter($tagsData, "sFilterTags", "name", false);

            //adding average vote data to the blog article
//             $avgVoteQuery = $this->repository->getAverageVoteQuery($blogArticle["id"]);
//             $blogArticles[$key]["sVoteAverage"] = $avgVoteQuery->getOneOrNullResult(\Doctrine\ORM\AbstractQuery::HYDRATE_SINGLE_SCALAR);

            //adding thumbnails to the blog article
            if (empty($blogArticle["media"][0]['mediaId'])) {
                continue;
            }

            $mediaId = $blogArticle["media"][0]['mediaId'];

            if (!isset($medias[$mediaId])) {
                continue;
            }

            /**@var $media \Shopware\Bundle\StoreFrontBundle\Struct\Media*/
            $media = $medias[$mediaId];
            $media = $this->get('legacy_struct_converter')->convertMediaStruct($media);

            if (Shopware()->Shop()->getTemplate()->getVersion() < 3) {
                $blogArticles[$key]["preview"]["thumbNails"] = array_column($media['thumbnails'], 'source');
            } else {
                $blogArticles[$key]['media'] = $media;
            }
        }
        return $blogArticles;
    }

    protected function getPropertyFacetOptionIds($facets) {
        $ids = array();
        foreach ($facets as $facet) {
            if ($facet->getFacetName() == "property") {
                $ids = array_merge($ids, $this->getValueIds($facet));
            }
        }
        $query = $this->get('dbal_connection')->createQueryBuilder();
        $query->select('options.id, optionID')
            ->from('s_filter_values', 'options')
            ->where('options.id IN (:ids)')
            ->setParameter(':ids', $ids, Connection::PARAM_INT_ARRAY)
        ;

        $result = $query->execute()->fetchAll();
        $facetToOption = array();
        foreach ($result as $row) {
            $facetToOption[$row['id']] = $row['optionID'];
        }
        return $facetToOption;
    }

    protected function getValueIds($facet) {
        if ($facet instanceof Shopware\Bundle\SearchBundle\FacetResult\FacetResultGroup) {
            $ids = array();
            foreach ($facet->getfacetResults() as $facetResult) {
                $ids = array_merge($ids, $this->getValueIds($facetResult));
            }
            return $ids;
        } else {
            return array_map(function($value) { return $value->getId(); }, $facet->getValues());
        }
    }

    /**
     * Get service from resource loader
     *
     * @param string $name
     * @return mixed
     */
    public function get($name) {
        return $this->container->get($name);
    }

    /**
     * @return string
     */
    protected function getSearchTerm() {
        $term = $this->Request()->get('sSearch', '');

        $term = trim(strip_tags(htmlspecialchars_decode(stripslashes($term))));

        // we have to strip the / otherwise broken urls would be created e.g. wrong pager urls
        $term = str_replace('/', '', $term);

        return $term;
    }

    /**
     * Search product by order number
     *
     * @param string $search
     * @return string
     */
    protected function searchFuzzyCheck($search) {
        $minSearch = empty($this->Config()->sMINSEARCHLENGHT) ? 2 : (int) $this->Config()->sMINSEARCHLENGHT;
        $db = Shopware()->Db();
        if (!empty($search) && strlen($search) >= $minSearch) {
            $ordernumber = $db->quoteIdentifier('ordernumber');
            $sql = $db->select()
                ->distinct()
                ->from('s_articles_details', array('articleID'))
                ->where("$ordernumber = ?", $search)
                ->limit(2);
            $articles = $db->fetchCol($sql);

            if (empty($articles)) {
                $percent = $db->quote('%');
                $sql->orWhere("? LIKE CONCAT($ordernumber, $percent)", $search);
                $articles = $db->fetchCol($sql);
            }
        }
        if (!empty($articles) && count($articles) == 1) {
            $sql = $db->select()
                ->from(array('ac' => 's_articles_categories_ro'), array('ac.articleID'))
                ->joinInner(
                    array('c' => 's_categories'),
                    $db->quoteIdentifier('c.id') . ' = ' . $db->quoteIdentifier('ac.categoryID') . ' AND ' .
                    $db->quoteIdentifier('c.active') . ' = ' . $db->quote(1) . ' AND ' .
                    $db->quoteIdentifier('c.id') . ' = ' . $db->quote(Shopware()->Shop()->get('parentID'))
                )
                ->where($db->quoteIdentifier('ac.articleID') . ' = ?', $articles[0])
                ->limit(1);
            $articles = $db->fetchCol($sql);
        }
        if (!empty($articles) && count($articles) == 1) {
            return $this->Controller()->Front()->Router()->assemble(array('sViewport' => 'detail', 'sArticle' => $articles[0]));
        }
    }

    /**
     * @return Shopware\Bundle\SearchBundle\FacetHandlerInterface[]
     */
    protected function registerFacetHandlers() {
        // did not find a way to use the service tag "facet_handler_dba"
        // it seems the dependency injection CompilerPass is not available to plugins?
        $facetHandlerIds = [
            'vote_average',
            'shipping_free',
            'product_attribute',
            'immediate_delivery',
            'manufacturer',
            'property',
            'category',
            'price',
        ];
        $facetHandlers = [];
        foreach ($facetHandlerIds as $id) {
            $facetHandlers[] = $this->container->get("shopware_searchdbal.${id}_facet_handler_dbal");
        }
        return $facetHandlers;
    }

    /**
     * @param \Shopware\Bundle\SearchBundle\FacetInterface $facet
     * @return FacetHandlerInterface|null|\Shopware\Bundle\SearchBundle\FacetHandlerInterface
     */
    protected function getFacetHandler(Shopware\Bundle\SearchBundle\FacetInterface $facet) {
        if ($this->facetHandlers == null) {
            $this->facetHandlers = $this->registerFacetHandlers();
        }
        foreach ($this->facetHandlers as $handler) {
            if ($handler->supportsFacet($facet)) {
                return $handler;
            }
        }
        return null;
    }

    /**
     * @param $value_id
     * @return null
     */
    private function getOptionIdFromValue($value_id) {
        $db = Shopware()->Db();
        $sql = $db->select()
            ->from('s_filter_values', array('optionId'))
            ->where('s_filter_values.id = ?', $value_id);
        return $db->fetchOne($sql);
    }

    /**
     * @param \Shopware\Bundle\SearchBundle\Criteria $criteria
     * @param \Shopware\Bundle\StoreFrontBundle\Struct\ShopContext $context
     * @return array
     */
    protected function createFacets(Shopware\Bundle\SearchBundle\Criteria $criteria, Shopware\Bundle\StoreFrontBundle\Struct\ShopContext $context, $facet_type = null) {
        $facets = array();

        foreach ($criteria->getFacets() as $type => $facet) {

            $handler = $this->getFacetHandler($facet);
            if ($handler === null) continue;

            $result = $handler->generateFacet($facet, $criteria, $context);
            if (!$result) {
                continue;
            }
            if(!is_null($facet_type) && $type == $facet_type) {
                return $result;
            }
            $facets[$result->getFacetName()] = $result;
        }
        if(!is_null($facet_type)) {
            throw new \Exception("Couldn't create facet for type: " . $facet_type);
        }
        $facetOptions = $facets['property']->getFacetResults();
        unset($facets['property']);
        foreach ($facetOptions as $option) {
            $peek = reset($option->getValues());
            if ($peek){
                $optionId = $this->getOptionIdFromValue($peek->getId());
                if (is_null($optionId)) {
                    break;
                }
                $facets["products_optionID_mapped_{$optionId}"] = $option;
            }
        }
        return $facets;
    }

    /**
     * @param $facets
     * @return array
     */
    protected function getFacetConfig($facets) {
        $options = [];

        foreach ($facets as $fieldName => $facet) {
            $key = '';
            switch ($facet->getFacetName()) {
                case 'price':
                    $value = array();
                    if ($facet->isActive()) {
                        $min = $facet->getActiveMin();
                        $max = $facet->getActiveMax() == 0 ? $facet->getMax() : $facet->getActiveMax();
                        $value = ["{$min}-{$max}"];
                    }
                    $options['discountedPrice'] = [
                        'value' => $value,
                        'type' => 'ranged',
                        'bounds' => true,
                        'label' => $facet->getLabel()
                    ];
                    break;
                case 'category':
                    if ($this->Request()->getControllerName() == 'search') {
                        $id = $label = null;
                        $params = $this->Request()->getParams();
                        if (isset($_REQUEST['c']) || isset($params['sCategory'])) {
                            $value = $this->getLowestActiveTreeItem($facet->getValues());
                            if ($value instanceof Shopware\Bundle\SearchBundle\FacetResult\TreeItem) {
                                $id = $value->getId();
                            }
                        } else {
                            $id = Shopware()->Shop()->getCategory()->getId();
                        }
                        $options['category']['value'] = $id;
                    }
                    break;
                case 'property':
                    $key = $fieldName;
                case 'manufacturer':
                    if ($key == '') {
                        $key = 'products_brand';
                    }
                    if (!array_key_exists($key, $options)) {
                        $options[$key] = ['label' => trim($facet->getLabel())];
                    }
                    $selected_value = [];
                    foreach ($facet->getValues() as $value) {
                        if ($value->isActive()) {
                            $label = trim($value->getLabel());
                            if($key != 'products_brand'){
                                $selected_value[] = "{$label}_bx_{$value->getId()}";
                            } else {
                                $selected_value[] = "{$label}";
                            }
                        }
                    }
                    $options[$key]['value'] = $selected_value;
                    break;
                case 'shipping_free':
                    $key = 'products_shippingfree';
                case 'immediate_delivery':

                    if($key == '') {
                        $key = 'products_bx_purchasable';
                    }
                    $options[$key] = ['label' => $facet->getLabel()];
                    if($facet->isActive()) {
                        $options[$key]['value'] = 1;
                    }
                    break;
                default:
                    break;
            }
        }
        return $options;
    }

    /**
     * @param Shopware\Bundle\SearchBundle\FacetResult\TreeItem[] $values
     * @return null|Shopware\Bundle\SearchBundle\FacetResult\TreeItem
     */
    protected function getLowestActiveTreeItem($values) {
        foreach ($values as $value) {
            $innerValues = $value->getValues();
            if (count($innerValues)) {
                $innerValue = $this->getLowestActiveTreeItem($innerValues);
                if ($innerValue instanceof Shopware\Bundle\SearchBundle\FacetResult\TreeItem) {
                    return $innerValue;
                }
            }
            if ($value->isActive()) {
                return $value;
            }
        }
        return null;
    }

    /**
     * @param $id
     * @return Doctrine\DBAL\Query\QueryBuilder
     */
    private function getMediaById($id)
    {
        return $this->get('shopware_storefront.media_service')
            ->get($id, $this->get('shopware_storefront.context_service')->getProductContext());
    }

    /**
     * @param $bxFacets
     * @param $facet
     * @param $lang
     * @return \Shopware\Bundle\SearchBundle\FacetResult\ValueListFacetResult
     */
    private function generateManufacturerListItem($bxFacets, $facet, $lang) {
        $db = Shopware()->Db();
        $fieldName = 'products_brand';
        $where_statement = '';
        $values = $bxFacets->getFacetValues($fieldName);
        if(sizeof($values) == 0){
            return;
        }
        foreach ($values as $index => $value) {
            if($index > 0) {
                $where_statement .= ' OR ';
            }
            $where_statement .= 'a_s.name LIKE \'%'. addslashes($value) .'%\'';
        }

        $sql = $db->select()
            ->from(array('a_s' => 's_articles_supplier', array('a_s.id', 'a_s.name')))
            ->where($where_statement);
        $result = $db->fetchAll($sql);
        $showCount = $bxFacets->showFacetValueCounters($fieldName);
        $innerValues = $this->useValuesAsKeys($values);
        foreach ($result as $r) {
            $label = trim($r['name']);
            if(!isset($innerValues[$label])) {
                continue;
            }
            $selected = $bxFacets->isFacetValueSelected($fieldName, $label);
            if ($showCount) {
                $label .= ' (' . $bxFacets->getFacetValueCount($fieldName, $label) . ')';
            }
            $innerValues[trim($r['name'])] = new Shopware\Bundle\SearchBundle\FacetResult\MediaListItem(
                (int)$r['id'],
                $label,
                $selected
            );
        }
        return new Shopware\Bundle\SearchBundle\FacetResult\ValueListFacetResult(
            $facet->getFacetName(),
            $bxFacets->isSelected($fieldName),
            $bxFacets->getFacetLabel($fieldName, $lang),
            $innerValues,
            $facet->getFieldName()
        );
    }

    private function useTranslation($shop_id){
        $db = Shopware()->Db();
        $sql = $db->select()->from(array('c_t' => 's_core_translations'))
            ->where('c_t.objectlanguage = ?', $shop_id);
        $stmt = $db->query($sql);
        $use = $stmt->rowCount() == 0 ? false : true;
        return $use;
    }

    private function getFacetValuesResult($option_id, $values, $translation, $shop_id){
        $where_statement = '';
        $db = Shopware()->Db();
        foreach ($values as $index => $value) {
            $id = end(explode("_bx_", $value));
            if($index > 0) {
                $where_statement .= ' OR ';
            }
            $where_statement .= 'v.id = '. $db->quote($id);
        }
        $sql = $db->select()
            ->from(array('v' => 's_filter_values', array()))
            ->where($where_statement)
            ->where('v.optionID = ?', $option_id);
        $result = $db->fetchAll($sql);
        if($translation == true) {
            $where_statement = '';
            foreach ($values as $index => $value) {
                $id = end(explode("_bx_", $value));
                if($index > 0) {
                    $where_statement .= ' OR ';
                }
                $where_statement .= 'v.objectkey LIKE '. $db->quote($id);
            }
            $sql = $db->select()
                ->from(array('v' => 's_core_translations', array()))
                ->join(array('f_v' => 's_filter_values'), 'f_v.id = v.objectkey', array('f_v.media_id'))
                ->where($where_statement)
                ->where('f_v.optionID = ?', $option_id)
                ->where('v.objecttype = ?', 'propertyvalue')
                ->where('v.objectlanguage = ?', $shop_id);
            $result = array_merge($result, $db->fetchAll($sql));
        }
        return $result;
    }

    /**
     * @param $fieldName
     * @param $bxFacets
     * @param $facet
     * @param $lang
     * @return mixed
     */
    private function generateListItem($fieldName, $bxFacets, $facet, $lang) {
        if(is_null($facet)) {
            return;
        }
        $option_id = end(explode('_', $fieldName));
        $values = $bxFacets->getFacetValues($fieldName);
        if(sizeof($values) == 0) {
            return;
        }
        $shop_id  = Shopware()->Shop()->getId();
        $useTranslation = $this->useTranslation($shop_id);
        $result = $this->getFacetValuesResult($option_id, $values, $useTranslation, $shop_id);
        $media_class = false;
        $showCount = $bxFacets->showFacetValueCounters($fieldName);
        $values = $this->useValuesAsKeys($values);

        foreach ($result as $r) {
            if($useTranslation == true && isset($r['objectkey'])) {
                $r['id'] = $r['objectkey'];
                $r['value'] = unserialize($r['objectdata'])['optionValue'];
            }
            $label = trim($r['value']);
            $key = $label . "_bx_{$r['id']}";
            if(!isset($values[$key])) {
                continue;
            }

            $selected = $bxFacets->isFacetValueSelected($fieldName, $key);
            if ($showCount) {
                $label .= ' (' . $bxFacets->getFacetValueCount($fieldName, $key) . ')';
            }
            $media = $r['media_id'];
            if (!is_null($media)) {
                $media = $this->getMediaById($media);
                $media_class = true;
            }
            $values[$key] = new Shopware\Bundle\SearchBundle\FacetResult\MediaListItem(
                (int)$r['id'],
                $label,
                (boolean)$selected,
                $media
            );
        }
        $class = $media_class === true ? 'Shopware\Bundle\SearchBundle\FacetResult\MediaListFacetResult' :
            'Shopware\Bundle\SearchBundle\FacetResult\ValueListFacetResult';
        return new $class(
            $facet->getFacetName(),
            $bxFacets->isSelected($fieldName),
            $bxFacets->getFacetLabel($fieldName,$lang),
            $values,
            $facet->getFieldName()
        );
    }

    /**
     * @param Shopware\Bundle\SearchBundle\FacetResultInterface[] $facets
     * @param \com\boxalino\p13n\api\thrift\Variant $variant
     * @return Shopware\Bundle\SearchBundle\FacetResultInterface[]
     */
    protected function updateFacetsWithResult($facets) {
        $lang = substr(Shopware()->Shop()->getLocale()->getLocale(), 0, 2);
        $bxFacets = $this->Helper()->getFacets();
        $filters = array();
        foreach ($bxFacets->getLeftFacets() as $fieldName) {
            $key = '';
            if ($bxFacets->isFacetHidden($fieldName)) {
                continue;
            }

            switch ($fieldName) {
                case 'discountedPrice':
                    $facet = $facets['price'];
                    $label = trim($bxFacets->getFacetLabel($fieldName,$lang));
                    $this->facetOptions[$label] = [
                        'fieldName' => $fieldName,
                        'expanded' => $bxFacets->isFacetExpanded($fieldName, false)
                    ];
                    $priceRange = explode('-', $bxFacets->getPriceRanges()[0]);
                    $from = (float) $priceRange[0];
                    $to = (float) $priceRange[1];
                    $activeMin = $facet->getActiveMin();
                    if (isset($activeMin)) {
                        $activeMin = max($from, $activeMin);
                    }
                    $activeMax = $facet->getActiveMax() == 0 ? $to : $facet->getActiveMax();
                    if (isset($activeMax)) {
                        $activeMax = $activeMax == 0 ? $to : min($to, $activeMax);
                    }

                    $filters[] = new Shopware\Bundle\SearchBundle\FacetResult\RangeFacetResult(
                        $facet->getFacetName(),
                        $bxFacets->isSelected($fieldName),
                        $label,
                        $from,
                        $to,
                        $activeMin,
                        $activeMax,
                        $facet->getMinFieldName(),
                        $facet->getMaxFieldName(),
                        $facet->getAttributes(),
                        $facet->getTemplate()
                    );
                    break;
                case 'categories':
                    if ($this->Request()->getControllerName() == 'search') {
                        $facet = $facets['category'];
                        $label = $bxFacets->getFacetLabel($fieldName,$lang);
                        $this->facetOptions[$label] = [
                            'fieldName' => $fieldName,
                            'expanded' => $bxFacets->isFacetExpanded($fieldName, false)
                        ];
                        $updatedFacetValues = $this->updateTreeItemsWithFacetValue($facet->getValues(), $bxFacets);

                        $filters[] = new Shopware\Bundle\SearchBundle\FacetResult\TreeFacetResult(
                            $facet->getFacetName(),
                            $facet->getFieldName(),
                            $bxFacets->isSelected($fieldName),
                            $label,
                            $updatedFacetValues,
                            $facet->getAttributes(),
                            $facet->getTemplate()
                        );
                    }
                    break;
                case 'products_shippingfree':
                    $key = 'shipping_free';
                case 'products_bx_purchasable':
                    if($key == '') {
                        $key = 'immediate_delivery';
                    }
                    $facet = $facets[$key];
                    $filters[] = new Shopware\Bundle\SearchBundle\FacetResult\BooleanFacetResult(
                        $facet->getFacetName(),
                        $facet->getFieldName(),
                        $bxFacets->isSelected($fieldName),
                        $bxFacets->getFacetLabel($fieldName,$lang),
                        $facet->getAttributes(),
                        $facet->getTemplate()
                    );
                    break;
                case 'products_brand':
                    $facet = $facets['manufacturer'];
                    $returnFacet = $this->generateManufacturerListItem($bxFacets, $facet, $lang);
                    if($returnFacet) {
                        $this->facetOptions[$bxFacets->getFacetLabel($fieldName,$lang)] = [
                            'fieldName' => $fieldName,
                            'expanded' => $bxFacets->isFacetExpanded($fieldName, false)
                        ];
                        $filters[] = $returnFacet;
                    }
                    break;
                default:
                    if ((strpos($fieldName, 'products_optionID') !== false)) {
                        $facet = $facets[$fieldName];
                        $returnFacet = $this->generateListItem($fieldName, $bxFacets, $facet, $lang);
                        if($returnFacet) {
                            $this->facetOptions[$bxFacets->getFacetLabel($fieldName, $lang)] = [
                                'fieldName' => $fieldName,
                                'expanded' => $bxFacets->isFacetExpanded($fieldName, false)
                            ];
                            $filters[] = $returnFacet;
                        }
                    }
                    break;
            }
        }
        return $filters;
    }

    /**
     * @param $array
     * @return array
     */
    public function useValuesAsKeys($array){
        return array_combine(array_keys(array_flip($array)),$array);
    }

    /**
     * @param Shopware\Bundle\SearchBundle\FacetResult\TreeItem[] $values
     * @param com\boxalino\p13n\api\thrift\FacetValue[] $FacetValues
     * @return Shopware\Bundle\SearchBundle\FacetResult\TreeItem[]
     */
    protected function updateTreeItemsWithFacetValue($values, $resultFacet) {
        foreach ($values as $key => $value) {
            $id = (string) $value->getId();
            $label = $value->getLabel();
            $innerValues = $value->getValues();

            if (count($innerValues)) {
                $innerValues = $this->updateTreeItemsWithFacetValue($innerValues, $resultFacet);
            }

            $category = $resultFacet->getCategoryById($id);
            $showCounter = $resultFacet->showFacetValueCounters('categories');
            if ($category && $showCounter) {
                $label .= ' (' . $resultFacet->getCategoryValueCount($category) . ')';
            } else {
                if (sizeof($innerValues)==0) {
                    continue;
                }
            }

            $finalVals[$key] = new Shopware\Bundle\SearchBundle\FacetResult\TreeItem(
                $value->getId(),
                $label,
                $value->isActive(),
                $innerValues,
                $value->getAttributes()
            );
        }
        return $finalVals;
    }

    /**
     * @param Shopware\Bundle\SearchBundle\Criteria $criteria
     * @return array
     */
    public function getSortOrder(Shopware\Bundle\SearchBundle\Criteria $criteria, $default_sort = null, $listing = false) {
        /* @var Shopware\Bundle\SearchBundle\Sorting\Sorting $sort */
        $sort = current($criteria->getSortings());
        $dir = null;
        switch ($sort->getName()) {
            case 'popularity':
                $field = 'products_sales';
                break;
            case 'prices':
                $field = 'products_bx_grouped_price';
                break;
            case 'product_name':
                $field = 'title';
                break;
            case 'release_date':
                $field = 'products_releasedate';
                break;
            default:
                if ($listing == true) {
                    $default_sort = is_null($default_sort) ? $this->getDefaultSort() : $default_sort;
                    switch ($default_sort) {
                        case 1:
                            $field = 'products_releasedate';
                            break 2;
                        case 2:
                            $field = 'products_sales';
                            break 2;
                        case 3:
                        case 4:
                            if ($default_sort == 3) {
                                $dir = false;
                            }
                            $field = 'products_bx_grouped_price';
                            break 2;
                        case 5:
                        case 6:
                            if ($default_sort == 5) {
                                $dir = false;
                            }
                            $field = 'title';
                            break 2;
                        default:
                            if ($this->Config()->get('boxalino_navigation_sorting') == false) {
                                $field = 'products_releasedate';
                                break 2;
                            }
                            break;
                    }
                }
                return array();
        }

        return array(
            'field' => $field,
            'reverse' => (is_null($dir) ? $sort->getDirection() == Shopware\Bundle\SearchBundle\SortingInterface::SORT_DESC : $dir)
        );
    }

    /**
     * @return mixed|null
     */
    protected function getDefaultSort(){
        $db = Shopware()->Db();
        $sql = $db->select()
            ->from(array('c_e' => 's_core_config_elements', array('c_v.value')))
            ->join(array('c_v' => 's_core_config_values'), 'c_v.element_id = c_e.id')
            ->where("name = ?", "defaultListingSorting");
        $result = $db->fetchRow($sql);
        return isset($result) ? unserialize($result['value']) : null;

    }

    /**
     * @param $supplier
     * @return null
     */
    protected function getSupplierName($supplier) {
        $supplier = $this->get('dbal_connection')->fetchColumn(
            'SELECT name FROM s_articles_supplier WHERE id = :id',
            ['id' => $supplier]
        );

        if ($supplier) {
            return $supplier;
        }

        return null;
    }

    /**
     * @param int $categoryId
     * @return int|null
     */
    private function findStreamIdByCategoryId($categoryId)
    {
        $streamId = $this->get('dbal_connection')->fetchColumn(
            'SELECT stream_id FROM s_categories WHERE id = :id',
            ['id' => $categoryId]
        );

        if ($streamId) {
            return (int)$streamId;
        }

        return null;
    }

}