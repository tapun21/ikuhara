{extends file="frontend/listing/listing_actions"}

{* Pagination - Frist page *}
{block name="frontend_listing_actions_paging_first"}
    {if $sPage > 1 && $bxPageType != 'blog'}
        <a href="{$baseUrl}?p=1&bxActiveTab=product" title="{"{s name='ListingLinkFirst'}{/s}"|escape}" class="paging--link paging--prev" data-action-link="true">
            <i class="icon--arrow-left"></i>
            <i class="icon--arrow-left"></i>
        </a>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}

{* Pagination - Previous page *}
{block name='frontend_listing_actions_paging_previous'}
    {if $sPage > 1 && $bxPageType != 'blog'}
        <a href="{$baseUrl}?p={$sPage-1}&bxActiveTab=product" title="{"{s name='ListingLinkPrevious'}{/s}"|escape}" class="paging--link paging--prev" data-action-link="true">
            <i class="icon--arrow-left"></i>
        </a>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}

{* Pagination - current page *}
{block name='frontend_listing_actions_paging_numbers'}
    {if $pages > 1 && $bxPageType != 'blog'}
        <a title="{$sCategoryContent.name|escape}" class="paging--link is--active">{$sPage}</a>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}

{* Pagination - Next page *}
{block name='frontend_listing_actions_paging_next'}
    {if $sPage < $pages && $bxPageType != 'blog'}
        <a href="{$baseUrl}?p={$sPage+1}&bxActiveTab=product" title="{"{s name='ListingLinkNext'}{/s}"|escape}" class="paging--link paging--next" data-action-link="true">
            <i class="icon--arrow-right"></i>
        </a>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}

{* Pagination - Last page *}
{block name="frontend_listing_actions_paging_last"}
    {if $sPage < $pages && $bxPageType != 'blog'}
        <a href="{$baseUrl}?p={$pages}&bxActiveTab=product" title="{"{s name='ListingLinkLast'}{/s}"|escape}" class="paging--link paging--next" data-action-link="true">
            <i class="icon--arrow-right"></i>
            <i class="icon--arrow-right"></i>
        </a>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}

{* Pagination - Number of pages *}
{block name='frontend_listing_actions_count'}
    {if $pages > 1 && $bxPageType != 'blog'}
        <span class="paging--display">
                {s name="ListingTextFrom"}{/s} <strong>{$pages}</strong>
            </span>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}

{* Products per page selection *}
{block name='frontend_listing_actions_items_per_page'}
    {if $bxPageType != 'blog'}
        {include file="frontend/listing/actions/action-per-page.tpl"}
    {else}
        {$smarty.block.parent}
    {/if}
{/block}

</div>