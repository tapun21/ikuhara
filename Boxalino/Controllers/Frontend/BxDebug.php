<?php
use com\boxalino\bxclient\v1\BxClient;
use com\boxalino\bxclient\v1\BxRecommendationRequest;
class Shopware_Controllers_Frontend_BxDebug extends Enlight_Controller_Action {

    public function indexAction() {
        $this->test();
    }

    public function test() {
        $ids = array();
        $account = "shopware_test_3"; // your account name
        $password = "shopware_test_3"; // your account password
        $domain = ""; // your web-site domain (e.g.: www.abc.com)
        $isDev = true;
        $host = isset($host) ? $host : "cdn.bx-cloud.com";
        $bxClient = new BxClient($account, $password, $domain, $isDev, $host);
        $bxClient->setTimeout(3600);
        $start = microtime(true);
        $language = "de";
        $choiceId = "home"; 
        $hitCount = 10;
        $bxRequest = new BxRecommendationRequest($language, $choiceId, $hitCount);
        $bxFilters = new \com\boxalino\bxclient\v1\BxFilter('products_bx_type', array('product'));
        $bxRequest->addFilter($bxFilters);
        $bxRequest->setReturnFields(['products_group_id', 'products_ordernumber']);
        $bxRequest->setGroupBy('products_group_id');
        $bxClient->addRequest($bxRequest);

        $bxResponse = $bxClient->getResponse();
        $current_stop = (microtime(true) - $start) * 1000;
        $logs[] = "response in : {$current_stop}ms";
        foreach($bxResponse->getHitIds() as $i => $id) {
			$logs[] = "$i: returned id $id";
			$ids[] = $id;
        }

        $current_stop_start = (microtime(true) - $start) * 1000;
        $logs[] = "[{$current_stop_start}] start - getById";
        $articlesById = $this->getById($ids);
        $current_stop_end= (microtime(true) - $start) * 1000;
        $logs[] = "[{$current_stop_end}] end - getById";
        $count = count($articlesById);
        $logs[] = "count for getById: {$count}";
        $duration = $current_stop_end - $current_stop_start;
        $logs[] = "time spent: {$duration}ms";

        $order_numbers = array();
        foreach ($bxResponse->getHitIds($choiceId, true, 0, 10, 'products_ordernumber') as $order_number) {
            $order_numbers[] = $order_number;
        }

        $current_stop_start = (microtime(true) - $start) * 1000;
        $logs[] = "[{$current_stop_start}] start - getProductList";
        $articlesList = $this->getProductList($order_numbers);
        $current_stop_end = (microtime(true) - $start) * 1000;
        $logs[] = "[{$current_stop_end}] end - getProductList";
        $count = count($articlesList);
        $logs[] = "count for getProductList: {$count}";
        $duration = $current_stop_end - $current_stop_start;
        $logs[] = "time spent: {$duration}ms";

        $this->View()->addTemplateDir(Shopware()->Plugins()->Frontend()->Boxalino()->Path() . 'Views/emotion/');
        $this->View()->loadTemplate('frontend/plugins/boxalino/debug/debug.tpl');
        $this->View()->assign('logs', $logs);
    }

    private function getProductList($ids) {
        return Shopware()->Container()->get('legacy_struct_converter')->convertListProductStructList(
            Shopware()->Container()->get('shopware_storefront.list_product_service')->getList(
                $ids,
                Shopware()->Container()->get('shopware_storefront.context_service')->getProductContext()
            )
        );
    }

    /**
     * @param array $ids
     * @return array
     */
    private function getById($ids) {

        $articles = array();
        foreach ($ids as $id) {

            $articleNew = Shopware()->Modules()->Articles()->sGetPromotionById('fix', 0, $id);
            if (!empty($articleNew['articleID'])) {
                $articles[] = $articleNew;
            }
        }
        return $articles;
    }
}