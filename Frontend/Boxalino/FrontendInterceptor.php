<?php

/**
 * frontend interceptor
 */
class Shopware_Plugins_Frontend_Boxalino_FrontendInterceptor
    extends Shopware_Plugins_Frontend_Boxalino_Interceptor {
    
    private $_productRecommendations = array(
        'sRelatedArticles' => 'boxalino_accessories_recommendation',
        'sSimilarArticles' => 'boxalino_similar_recommendation'
    );
    
    private $_productRecommendationsGeneric = array(
        'sCrossBoughtToo' => 'boxalino_complementary_recommendation',
        'sCrossSimilarShown' => 'boxalino_related_recommendation'
    );

    /**
     * add tracking, product recommendations
     * @param Enlight_Event_EventArgs $arguments
     * @return boolean
     */
    public function intercept(Enlight_Event_EventArgs $arguments) {
        
        $this->init($arguments);
        if (!$this->Config()->get('boxalino_active')) {
            return null;
        }

        $script = null;
        switch ($this->Request()->getParam('controller')) {
            case 'detail':
                $sArticle = $this->View()->sArticle;
                if(is_null($sArticle) || !isset($sArticle['articleID']))break;
                if ($this->Config()->get('boxalino_detail_recommendation_ajax')) {
                    $this->View()->addTemplateDir($this->Bootstrap()->Path() . 'Views/emotion/');
                    $this->View()->extendsTemplate('frontend/plugins/boxalino/detail/index_ajax.tpl');
                } else {
                    $id = trim(strip_tags(htmlspecialchars_decode(stripslashes($this->Request()->sArticle))));
                    $choiceIds = array();
                    $recommendations = array_merge($this->_productRecommendations, $this->_productRecommendationsGeneric);
                    foreach ($recommendations as $articleKey => $configOption) {
                        if($this->Config()->get("{$configOption}_enabled")){
                            $excludes = array();
                            if ($articleKey == 'sRelatedArticles' || $articleKey == 'sSimilarArticles') {
                                if (isset($sArticle[$articleKey]) && is_array($sArticle[$articleKey])) {
                                    foreach ($sArticle[$articleKey] as $article) {
                                        $excludes[] = $article['articleID'];
                                    }
                                }
                            }
                            $choiceId = $this->Config()->get("{$configOption}_name");
                            $max = $this->Config()->get("{$configOption}_max");
                            $min = $this->Config()->get("{$configOption}_min");
                            $this->Helper()->getRecommendation($choiceId, $max, $min, 0, $id, 'product', false, $excludes);
                            $choiceIds[$configOption] = $choiceId;
                        }
                    }

                    if (count($choiceIds)) {
                        foreach ($this->_productRecommendations as $articleKey => $configOption) {
                            if (array_key_exists($configOption, $choiceIds)) {
                                $hitIds = $this->Helper()->getRecommendation($choiceIds[$configOption]);
                                $sArticle[$articleKey] = array_merge($sArticle[$articleKey], $this->Helper()->getLocalArticles($hitIds));
                            }
                        }
                    }
                    $this->View()->assign('sArticle', $sArticle);
                }
                $script = Shopware_Plugins_Frontend_Boxalino_EventReporter::reportProductView($sArticle['articleDetailsID']);
                break;
            case 'search':
                $script = Shopware_Plugins_Frontend_Boxalino_EventReporter::reportSearch($this->Request());
                break;
            case 'cat':
                $script = Shopware_Plugins_Frontend_Boxalino_EventReporter::reportCategoryView($this->Request()->sCategory);
                break;
            case 'recommendation':
                $action = $this->Request()->getParam('action');
                if ($action == 'viewed' || $action == 'bought') {
                    $configOption = $action == 'viewed' ? $this->_productRecommendationsGeneric['sCrossSimilarShown'] :
                        $this->_productRecommendationsGeneric['sCrossBoughtToo'];
                    if ($this->Config()->get("{$configOption}_enabled")) {
                        $hitIds = $this->Helper()->getRecommendation($this->Config()->get("{$configOption}_name"));
                        $this->View()->assign("{$action}Articles", $this->Helper()->getLocalArticles($hitIds));
                    }
                } else {
                    return null;
                }
                break;
            case 'checkout':
            case 'account':
                if ($_SESSION['Shopware']['sUserId'] != null) {
                    $script = Shopware_Plugins_Frontend_Boxalino_EventReporter::reportLogin($_SESSION['Shopware']['sUserId']);
                }
            default:
                $param = $this->Request()->getParam('callback');
                // skip ajax calls
                if (empty($param) && strpos($this->Request()->getPathInfo(), 'ajax') === false) {
                    $script = Shopware_Plugins_Frontend_Boxalino_EventReporter::reportPageView();
                }
        }
        $this->addScript($script);
        return false;
    }

    /**
     * @return mixed|string
     */
    protected function getSearchTerm() {
        $term = $this->Request()->get('sSearch', '');

        $term = trim(strip_tags(htmlspecialchars_decode(stripslashes($term))));

        // we have to strip the / otherwise broken urls would be created e.g. wrong pager urls
        $term = str_replace('/', '', $term);

        return $term;
    }
    
    /**
     * basket recommendations
     * @param Enlight_Event_EventArgs $arguments
     * @return boolean
     */
    public function basket(Enlight_Event_EventArgs $arguments) {

        $this->init($arguments);
        if (!$this->Config()->get('boxalino_active') || !$this->Config()->get('boxalino_cart_recommendation_enabled')) {
            return null;
        }

        if($this->Request()->getActionName() != 'ajaxCart'){
            return null;
        }

        $choiceId = $this->Config()->get('boxalino_cart_recommendation_name');
        $basket = $this->Helper()->getBasket($arguments);
        $contextItems = $basket['content'];
        if (empty($contextItems)) return null;
        
        usort($contextItems, function($a, $b) {
            return $b['price'] - $a['price'];
        });
        $contextItems = array_map(function($contextItem) {
            return ['id' => $contextItem['articleID'] ,'price' => $contextItem['price']];
        }, $contextItems);
        $max = $this->Config()->get('boxalino_cart_recommendation_max');
        $min = $this->Config()->get('boxalino_cart_recommendation_min');
        $this->Helper()->getRecommendation($choiceId, $max, $min, 0, $contextItems, 'basket', false);
        $hitIds = $this->Helper()->getRecommendation($choiceId);
        $this->View()->addTemplateDir($this->Bootstrap()->Path() . 'Views/emotion/');
        $this->View()->extendsTemplate('frontend/plugins/boxalino/checkout/ajax_cart.tpl');
        $this->View()->assign('sRecommendations', $this->Helper()->getLocalArticles($hitIds));
        return null;
    }

    /**
     * add "add to basket" tracking
     * @param Enlight_Event_EventArgs $arguments
     * @return boolean
     */
    public function addToBasket(Enlight_Event_EventArgs $arguments) {

        if (!$this->Config()->get('boxalino_active')) {
            return null;
        }
        if ($this->Config()->get('boxalino_tracking_enabled')) {
            $article = $arguments->getArticle();
            $price = $arguments->getPrice();
            Shopware_Plugins_Frontend_Boxalino_EventReporter::reportAddToBasket(
                $article['articledetailsID'],
                $arguments->getQuantity(),
                $price['price'],
                Shopware()->Shop()->getCurrency()
            );
        }
        return $arguments->getReturn();
    }

    /**
     * add purchase tracking
     * @param Enlight_Event_EventArgs $arguments
     * @return boolean
     */
    public function purchase(Enlight_Event_EventArgs $arguments) {
        if (!$this->Config()->get('boxalino_active')) {
            return null;
        }
        if ($this->Config()->get('boxalino_tracking_enabled')) {
            $products = array();
            foreach ($arguments->getDetails() as $detail) {
                $products[] = array(
                    'product' => $detail['articleDetailId'],
                    'quantity' => $detail['quantity'],
                    'price' => $detail['priceNumeric'],
                );
            }
            Shopware_Plugins_Frontend_Boxalino_EventReporter::reportPurchase(
                $products,
                $arguments->getSubject()->sOrderNumber,
                $arguments->getSubject()->sAmount,
                Shopware()->Shop()->getCurrency()
            );
        }
        return $arguments->getReturn();
    }

    /**
     * add script if tracking enabled
     * @param string $script
     * @return void
     */
    protected function addScript($script) {
        if ($script != null && $this->Config()->get('boxalino_tracking_enabled')) {
            $this->View()->addTemplateDir($this->Bootstrap()->Path() . 'Views/emotion/');
            $this->View()->extendsTemplate('frontend/plugins/boxalino/index.tpl');
            $this->View()->assign('report_script', $script);
        }
    }
    
}