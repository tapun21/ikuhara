<?php
class Shopware_Controllers_Frontend_RecommendationSlider extends Enlight_Controller_Action {

    /**
     * @var sMarketing
     */
    protected $marketingModule;

    /**
     * @var array
     */
    private $_productRecommendations = array(
        'sRelatedArticles' => 'boxalino_accessories_recommendation',
        'sSimilarArticles' => 'boxalino_similar_recommendation',
        'boughtArticles' => 'boxalino_complementary_recommendation',
        'viewedArticles' => 'boxalino_related_recommendation'
    );

    /**
     * @var Shopware_Components_Config
     */
    protected $config;

    public function indexAction() {
        $this->productStreamSliderRecommendationsAction();
    }

    public function detailAction() {
        $choiceIds = array();
        $this->config = Shopware()->Config();
        $this->marketingModule = Shopware()->Modules()->Marketing();
        $id = $this->request->getParam('articleId');
        if($id == 'sCategory') {
            $exception = new \Exception("Request with empty parameters from : " . $_SERVER['HTTP_REFERER']);
            Shopware()->Plugins()->Frontend()->Boxalino()->logException($exception, __FUNCTION__, $this->request->getRequestUri());
            return;
        } else if($id == '') {
            return;
        }
        $categoryId = $this->request->getParam('sCategory');
        $number = $this->Request()->getParam('number', null);
        $selection = $this->Request()->getParam('group', array());
        if (!$this->isValidCategory($categoryId)) {
            $categoryId = 0;
        }
        $this->config->offsetSet('similarLimit', 0);
        $sArticles = Shopware()->Modules()->Articles()->sGetArticleById(
            $id,
            $categoryId,
            $number,
            $selection
        );
        $boughtArticles = [];
        $viewedArticles = [];
        $sRelatedArticles = isset($sArticles['sRelatedArticles']) ? $sArticles['sRelatedArticles'] : [];
        $sSimilarArticles = isset($sArticles['sSimilarArticles']) ? $sArticles['sSimilarArticles'] : [];

        $helper = Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper::instance();
        $helper->setRequest($this->Request());
        foreach ($this->_productRecommendations as $var_name => $recommendation) {
            if ($this->config->get("{$recommendation}_enabled")) {
                $choiceId = $this->config->get("{$recommendation}_name");
                $max = $this->config->get("{$recommendation}_max");
                $min = $this->config->get("{$recommendation}_min");
                $excludes = array();
                if ($var_name == 'sRelatedArticles' ||$var_name == 'sSimilarArticles') {
                    foreach ($$var_name as $article) {
                        $excludes[] = $article['articleID'];
                    }
                }
                $helper->getRecommendation($choiceId, $max, $min, 0, $id, 'product', false, $excludes);
                $choiceIds[$recommendation] = $choiceId;
            }
        }

        foreach ($this->_productRecommendations as $var_name => $recommendation) {
            if (isset($choiceIds[$recommendation])) {
                $hitIds = $helper->getRecommendation($choiceIds[$recommendation]);
                $articles = array_merge($$var_name, $helper->getLocalArticles($hitIds));
                $sArticles[$var_name] = $articles;
            }
        }

        $path = Shopware()->Plugins()->Frontend()->Boxalino()->Path();
        $this->View()->addTemplateDir($path . 'Views/emotion/');
        $this->View()->loadTemplate('frontend/plugins/boxalino/detail/recommendation.tpl');
        $this->View()->assign('sArticle', $sArticles);
    }

    /**
     * Recommendation for boxalino emotion slider
     */
    public function productStreamSliderRecommendationsAction() {

        $helper = Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper::instance();
        $helper->setRequest($this->request);
        $choiceId = $this->Request()->getQuery('bxChoiceId');
        $count = $this->Request()->getQuery('bxCount');
        $context = $this->Request()->getQuery('category_id');
        $context = Shopware()->Shop()->getCategory()->getId() == $context ? null : $context;
        $helper->getRecommendation($choiceId, $count, $count, 0, $context, 'category', false);
        $hitsIds = $helper->getRecommendation($choiceId);
        if($hitsIds) {
            $this->View()->loadTemplate('frontend/_includes/product_slider_items.tpl');
            $this->View()->assign('articles', $helper->getLocalArticles($hitsIds));
            $this->View()->assign('productBoxLayout', "emotion");
        }
    }

    private function isValidCategory($categoryId) {
        $defaultShopCategoryId = Shopware()->Shop()->getCategory()->getId();

        /**@var $repository \Shopware\Models\Category\Repository*/
        $repository = Shopware()->Models()->getRepository('Shopware\Models\Category\Category');
        $categoryPath = $repository->getPathById($categoryId);

        if (!$categoryPath) {
            return true;
        }

        if (!array_key_exists($defaultShopCategoryId, $categoryPath)) {
            return false;
        }

        return true;
    }

}