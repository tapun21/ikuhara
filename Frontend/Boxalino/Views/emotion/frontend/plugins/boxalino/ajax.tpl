{block name='search_ajax_inner' prepend}
    {if $bxNoResult === true}
        <ul class="results--list suggestion--no-result">
            <li class="list--entry block-group result--item">
                <strong class="search-result--link" style="text-align: center;">{s namespace="boxalino/intelligence" name="search/noresult"}{/s}</strong>
            </li>
            {foreach $sSearchResults.sResults as $search_result}
                {block name="search_ajax_list_entry"}
                    <li class="list--entry block-group result--item">
                        <a class="search-result--link" href="{$search_result.link}" title="{$search_result.name|escape}">
                            {block name="search_ajax_list_entry_media"}
                                <span class="entry--media block">
									{if $search_result.image.thumbnails[0]}
                                        <img srcset="{$search_result.image.thumbnails[0].sourceSet}" alt="{$search_result.name|escape}" class="media--image">
									{else}
										<img src="{link file='frontend/_public/src/img/no-picture.jpg'}" alt="{"{s name='ListingBoxNoPicture'}{/s}"|escape}" class="media--image">
                                    {/if}
								</span>
                            {/block}
                            {block name="search_ajax_list_entry_name"}
                                <span class="entry--name block">
									{$search_result.name|escapeHtml}
								</span>
                            {/block}
                            {block name="search_ajax_list_entry_price"}
                                <span class="entry--price block">
                                    {$sArticle = $search_result}
                                    {$sArticle.has_pseudoprice = 0}
                                    {include file="frontend/listing/product-box/product-price.tpl" sArticle=$sArticle}
								</span>
                            {/block}
                        </a>
                    </li>
                {/block}
            {/foreach}
        </ul>
    {else}
        <ul class="results--list">
            {foreach $sSearchResults.sSuggestions as $suggestion}
                <li class="list--entry block-group result--item">
                    <a class="search-result--link" href="{url controller='search' sSearch=$suggestion.text}" title="{$suggestion.text|escape}">
                        {$suggestion.html} ({$suggestion.hits})
                    </a>
                </li>
            {/foreach}
        </ul>
        {$smarty.block.parent}
        {if $bxBlogSuggestionTotal > 0}
            <ul class="results--list suggestions--blog">
                <li class="entry-heading list--entry block-group result--item">
                    <strong class="search-result--heading">{s name='bx_blog_results_heading'}Blog Beitr&auml;ge{/s}</strong>
                </li>
                {foreach $bxBlogSuggestions as $blog}
                    <li class="list--entry block-group result--item">
                        <a class="search-result--link" href="{$blog.link}" title="{$blog.title}">
                            {$blog.title}
                        </a>
                    </li>
                {/foreach}
                <li class="entry--all-results block-group result--item">
                    <a href="{url controller="search"}?sSearch={$sSearchRequest.sSearch}&bxActiveTab=blog" class="search-result--link entry--all-results-link block">
                        <i class="icon--arrow-right"></i>
                        {s name='bx_show_all_blog_results'}Alle Blog-Ergebnisse anzeigen{/s}
                    </a>
                <span class="entry--all-results-number block">
                    {$bxBlogSuggestionTotal} {s name='bx_blog_result_count'}Treffer{/s}
                </span>
                </li>
            </ul>
        {/if}
        {if $bxCategorySuggestionTotal > 0}
            <ul class="results--list suggestions--category">
                <li class="entry-heading list--entry block-group result--item">
                    <strong class="search-result--heading">{s name='bx_category_results_heading'}Kategorien{/s}</strong>
                </li>
                {foreach $bxCategorySuggestions as $category}
                    <li class="list--entry block-group result--item">
                        <a class="search-result--link" href="{$category.link}" title="{$category.value}">
                            {$blog.value} {if $category.total > -1}($category.total){/if}
                        </a>
                    </li>
                {/foreach}
            </ul>
        {/if}
    {/if}
{/block}