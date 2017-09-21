{* Crossselling tab panel *}
{block name="frontend_detail_index_tabs_cross_selling"}

    {$showAlsoViewed = true}
    {$showAlsoBought = true}

        {* Tab navigation *}
        {block name="frontend_detail_index_tabs_navigation"}
            <div class="tab--navigation">
                {block name="frontend_detail_index_tabs_navigation_inner"}
                    {block name="frontend_detail_index_related_similiar_tabs"}

                        {* Tab navigation - Accessory products *}
                        {block name="frontend_detail_tabs_entry_related"}
                            {if $sArticle.sRelatedArticles && !$sArticle.crossbundlelook}
                                <a href="#content--related-products" title="{s namespace="frontend/detail/tabs" name='DetailTabsAccessories'}{/s}" class="tab--link">
                                    {s namespace="frontend/detail/tabs" name='DetailTabsAccessories'}{/s}
                                    <span class="product--rating-count-wrapper">
                                        <span class="product--rating-count">{$sArticle.sRelatedArticles|@count}</span>
                                    </span>
                                </a>
                            {/if}
                        {/block}

                        {* Similar products *}
                        {block name="frontend_detail_index_recommendation_tabs_entry_similar_products"}
                            {if count($sArticle.sSimilarArticles) > 0}
                                <a href="#content--similar-products" title="{s namespace="frontend/detail/index" name="DetailRecommendationSimilarLabel"}{/s}" class="tab--link">{s namespace="frontend/detail/index" name="DetailRecommendationSimilarLabel"}{/s}</a>
                            {/if}
                        {/block}
                    {/block}

                    {* Customer also bought *}
                    {block name="frontend_detail_index_tabs_entry_also_bought"}
                        {if $sArticle.boughtArticles}
                            <a href="#content--also-bought" title="{s namespace="frontend/detail/index" name="DetailRecommendationAlsoBoughtLabel"}{/s}" class="tab--link">{s namespace="frontend/detail/index" name="DetailRecommendationAlsoBoughtLabel"}{/s}</a>
                        {/if}
                    {/block}

                     {*Customer also viewed *}
                    {block name="frontend_detail_index_tabs_entry_also_viewed"}
                        {if $sArticle.viewedArticles}
                            <a href="#content--customer-viewed" title="{s namespace="frontend/detail/index" name="DetailRecommendationAlsoViewedLabel"}{/s}" class="tab--link">{s namespace="frontend/detail/index" name="DetailRecommendationAlsoViewedLabel"}{/s}</a>
                        {/if}
                    {/block}

                     {*Related product streams *}
                    {block name="frontend_detail_index_tabs_entry_related_product_streams"}
                        {foreach $sArticle.relatedProductStreams as $key => $relatedProductStream}
                            <a href="#content--related-product-streams-{$key}" title="{$relatedProductStream.name}" class="tab--link">{$relatedProductStream.name}</a>
                        {/foreach}
                    {/block}
                {/block}
            </div>
        {/block}

        {*Tab content container*}
        {block name="frontend_detail_index_outer_tabs"}
            <div class="tab--container-list">
                {block name="frontend_detail_index_inner_tabs"}
                    {block name='frontend_detail_index_before_tabs'}{/block}

                    {*Accessory articles*}
                    {block name="frontend_detail_index_tabs_related"}
                        {if $sArticle.sRelatedArticles && !$sArticle.crossbundlelook}
                            <div class="tab--container" data-tab-id="related">
                                {block name="frontend_detail_index_tabs_related_inner"}
                                    <div class="tab--header">
                                        <a href="#" class="tab--title" title="{s namespace="frontend/detail/tabs" name='DetailTabsAccessories'}{/s}">
                                            {s namespace="frontend/detail/tabs" name='DetailTabsAccessories'}{/s}
                                            <span class="product--rating-count-wrapper">
                                                        <span class="product--rating-count">{$sArticle.sRelatedArticles|@count}</span>
                                                    </span>
                                        </a>
                                    </div>
                                    <div class="tab--content content--related">{include file="frontend/detail/tabs/related.tpl"}</div>
                                {/block}
                            </div>
                        {/if}
                    {/block}

                    {*Similar products slider*}
                    {if $sArticle.sSimilarArticles}
                        {block name="frontend_detail_index_tabs_similar"}
                            <div class="tab--container" data-tab-id="similar">
                                {block name="frontend_detail_index_tabs_similar_inner"}
                                    <div class="tab--header">
                                        <a href="#" class="tab--title" title="{s namespace="frontend/detail/index" name="DetailRecommendationSimilarLabel"}{/s}">{s namespace="frontend/detail/index" name="DetailRecommendationSimilarLabel"}{/s}</a>
                                    </div>
                                    <div class="tab--content content--similar">{include file='frontend/detail/tabs/similar.tpl'}</div>
                                {/block}
                            </div>
                        {/block}
                    {/if}

                    {*"Customers bought also" slider*}
                    {if $sArticle.boughtArticles}
                        {block name="frontend_detail_index_tabs_also_bought"}
                            <div class="tab--container" data-tab-id="alsobought">
                                {block name="frontend_detail_index_tabs_also_bought_inner"}
                                    <div class="tab--header">
                                        <a href="#" class="tab--title" title="{s namespace="frontend/detail/index" name='DetailRecommendationAlsoBoughtLabel'}{/s}">{s namespace="frontend/detail/index" name='DetailRecommendationAlsoBoughtLabel'}{/s}</a>
                                    </div>
                                    <div class="tab--content content--also-bought">{include file='widget/recommendation/bought.tpl' boughtArticles=$sArticle.boughtArticles}</div>
                                {/block}
                            </div>
                        {/block}
                    {/if}

                    {*"Customers similar viewed" slider*}
                    {if $sArticle.viewedArticles}
                        {block name="frontend_detail_index_tabs_also_viewed"}
                            <div class="tab--container" data-tab-id="alsoviewed">
                                {block name="frontend_detail_index_tabs_also_viewed_inner"}
                                    <div class="tab--header">
                                        <a href="#" class="tab--title" title="{s namespace="frontend/detail/index" name='DetailRecommendationAlsoViewedLabel'}{/s}">{s namespace="frontend/detail/index" name='DetailRecommendationAlsoViewedLabel'}{/s}</a>
                                    </div>
                                    <div class="tab--content content--also-viewed">{include file='widget/recommendation/viewed.tpl' viewedArticles=$sArticle.viewedArticles}</div>
                                {/block}
                            </div>
                        {/block}
                    {/if}

                    {*Related product streams*}
                    {foreach $sArticle.relatedProductStreams as $key => $relatedProductStream}
                        {block name="frontend_detail_index_tabs_related_product_streams"}
                            <div class="tab--container" data-tab-id="productStreamSliderId-{$relatedProductStream.id}">
                                {block name="frontend_detail_index_tabs_related_product_streams_inner"}
                                    <div class="tab--header">
                                        <a href="#" class="tab--title" title="{$relatedProductStream.name}">{$relatedProductStream.name}</a>
                                    </div>
                                    <div class="tab--content content--related-product-streams-{$key}">
                                        {include file='frontend/detail/tabs/product_streams.tpl'}
                                    </div>
                                {/block}
                            </div>
                        {/block}
                    {/foreach}

                    {block name='frontend_detail_index_after_tabs'}{/block}
                {/block}
            </div>
        {/block}
{/block}