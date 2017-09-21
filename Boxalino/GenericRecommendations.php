<?php

class Shopware_Plugins_Frontend_Boxalino_GenericRecommendations
{
    /**
     * @var Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper
     */
    private $helper;

    /**
     * constructor
     */
    public function __construct()
    {
        $this->helper = Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper::instance();
    }

    /**
     * Request article recommendations for one or multiple choiceIds
     *
     * If one $choiceId is given, the result will be an array with the keys
     * 'results' and 'count'. For multiple $choiceId, the result is an array
     * with the choiceIds as the first dimension and 'results' and 'count' as
     * the second one.
     *
     * For generic recommendations, no special context is necessary. If you
     * display recommendations associated to products (i.e. on category or
     * basket pages) you should send along the articles detail id as 'contextItem'.
     * Boxalino might request you to send additional context parameters in special
     * cases.
     *
     * @param string|array $choiceId one choiceId of an array of multiple choiceIds
     * @param int $amount of products to recommend, defaults to 5
     * @param array $context parameters to add to the request, i.e. array('contextItem' => 123), where 123 is the article id (not the detail id)
     * @param int $offset to start result from, i.e. 5 to start with the 6th item (0 based index), defaults to 0
     * @return array
     */
    public function getArticlesForChoice($choiceId, $amount = 5, $context = array(), $offset = 0)
    {
        $results = [];
        if(Shopware()->Config()->get('boxalino_active')) {
            $choiceIds = is_array($choiceId) ? $choiceId : array($choiceId);
            if (array_key_exists('contextItem', $context)) {
                $id = $context['contextItem'];
                $type = 'product';
            } else if(array_key_exists('category_id', $context)) {
                $id = $context['category_id'];
                $type = 'category';
            } else {
                $id = null;
                $type = '';
            }
            foreach ($choiceIds as $choiceId){
                $this->helper->flushResponses();
                $this->helper->resetRequests();
                $this->helper->getRecommendation($choiceId, $amount, $amount, $offset, $id, $type, false);
            }

            foreach ($choiceIds as $choiceId){
                $hitIds = $this->helper->getRecommendation($choiceId);
                $result = $this->helper->getLocalArticles($hitIds);
                if(!is_array($choiceId)) return $result;
                $results[] = $result;
            }
        }
        return $results;
    }
}