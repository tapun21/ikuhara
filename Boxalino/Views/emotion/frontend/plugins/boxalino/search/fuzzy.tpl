{extends file="parent:frontend/search/fuzzy.tpl"}
{block name="frontend_index_content_left"}
    {if count($bxSubPhraseResults) == 0 && !$bxNoResult}
        {$smarty.block.parent}
    {/if}
{/block}
{block name="frontend_index_start" append}
    {if $corrected == true}
        {$sBreadcrumb = [['name' => "{s namespace="boxalino/intelligence" name="search/correctedresultsfor"}{/s} $term"]]}
    {/if}
{/block}
{block name='frontend_index_content_wrapper'}
    {if !empty($bxSubPhraseResults) || $bxNoResult}
        <div style="padding-left: 2em!important; padding-right: 2em!important;">
            {$smarty.block.parent}
        </div>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}

{block name='frontend_index_content'}
    {if $bxHasOtherItemTypes}
        <div class="tab-menu--search">
            <div class="tab--navigation">
                <a href="#" title="{s name='bx_search_tab_articles'}Artikel{/s}" class="tab--link">
                    <h2>{s name='bx_search_tab_articles'}Artikel{/s}</h2>
                    <span class="product--rating-count bx-tab-article-count">{$sSearchResults.sArticlesCount}</span>
                </a>
                {if $sBlogArticles}
                    <a href="#" title="{s name='bx_search_tab_blogs'}Blog-Beitr&auml;ge{/s}" class="tab--link tab--blog{if $bxActiveTab == 'blog'} is--active{/if}">
                        <h2>{s name='bx_search_tab_blogs'}Blog-Beitr&auml;ge{/s}</h2>
                        <span class="product--rating-count">{$bxBlogCount}</span>
                    </a>
                {/if}
            </div>
            <div class="tab--container-list">
                <div class="tab--container">
                    <div class="tab--content">
                        {$smarty.block.parent}
                    </div>
                </div>
                {if $sBlogArticles}
                    <div class="tab--container">
                        <div class="tab--content">
                            <div class="blog--content block-group">
                                {block name='frontend_bx_search_blog_headline'}
                                    <h1 class="search--headline">
                                        {s name='bx_search_blog_headline'}Zu "{$term}" wurden {$bxBlogCount} Blog-Beitr&auml;ge gefunden!{/s}
                                    </h1>
                                {/block}
                                {block name='frontend_bx_search_blog_content'}
                                    {include file='frontend/blog/listing.tpl' sPage=$sBlogPage bxPageType='blog' sNumberPages=$sNumberPages}
                                {/block}
                                <script>

                                </script>
                            </div>
                        </div>
                    </div>
                {/if}
            </div>
        </div>
    {else}
        {$smarty.block.parent}
    {/if}

    {if !empty($bxSubPhraseResults)}
        <h2>
            {s namespace='boxalino/intelligence' name='relaxation/didyoumean'}Did you mean...{/s}
        </h2>
        {foreach $bxSubPhraseResults as $suggestion}
            <h2>
                <u>
                    <a href="{url controller='search' sSearch=$suggestion.query}" title="{$suggestion.query|escape}">
                        {s namespace='boxalino/intelligence' name='search/resultsfor'}{/s} '{$suggestion.query|escape}' ({$suggestion.hitCount})

                    </a>
                </u>
            </h2>
            {if count($suggestion.articles) > 0}
                <div class="listing--wrapper">
                    <div class="listing--container">
                        <div class="listing" data-compare-ajax="true">
                            {foreach $suggestion.articles as $article}
                                {include file="frontend/listing/box_article.tpl" sArticle=$article productBoxLayout='minimal'}
                            {/foreach}
                        </div>
                    </div>
                </div>
            {/if}
            <br />
        {/foreach}
    {/if}
    {if $bxNoResult}
        <div class="content no-result" style="height:400px">
            {include file="widgets/emotion/components/component_article_slider.tpl" Data=$BxData}
        </div>
    {/if}
{/block}