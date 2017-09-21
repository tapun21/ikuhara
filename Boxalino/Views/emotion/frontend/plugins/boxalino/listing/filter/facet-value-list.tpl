{extends file="parent:frontend/listing/filter/facet-value-list.tpl"}
{if $bxFacets}
    {block name="frontend_listing_filter_facet_value_list"}
        <div class="filter-panel filter--property facet--{$facet->getFacetName()|escape:'htmlall'}"
             data-filter-type="value-list"
             data-field-name="{$facet->getFieldName()|escape:'htmlall'}">

            {block name="frontend_listing_filter_facet_value_list_flyout"}
                <div class="filter-panel--flyout">

                    {block name="frontend_listing_filter_facet_value_list_title"}
                        <label class="filter-panel--title">
                            {$facet->getLabel()|escape}
                        </label>
                    {/block}

                    {block name="frontend_listing_filter_facet_value_list_icon"}
                        <span class="filter-panel--icon"></span>
                    {/block}

                    {block name="frontend_listing_filter_facet_value_list_search_field"}
                        {if $bxFacets->getFacetExtraInfo({$facetOptions.{$facet->getLabel()|trim}.fieldName|trim}, 'visualisation') == 'search'}
                            <div class="bx-{$facet->getFacetName()|escape:'htmlall'}-search" style="padding: 0rem 0rem 1rem 0rem;position:relative;0.5px solid">
                                <input class="bx--facet-search" type="search" style="width: 100%;border: 1px solid;">
                                <span class="icon--search search-remove"
                                      style="padding: 0.3rem .8rem 0.3rem 0.8rem;
                                    position: absolute;
                                    top:0rem;right:0rem;z-index:2;
                                    border: 0 none; background: transparent;outline:none;
                                    text-transform: none;font-size: 2rem;">
                            </span>
                            </div>
                        {/if}
                    {/block}
                    {block name="frontend_listing_filter_facet_value_list_content"}
                        <div class="filter-panel--content">

                            {block name="frontend_listing_filter_facet_value_list_list"}
                                <ul class="filter-panel--option-list">
                                    {assign var="optionStyle" value=''}
                                    {assign var="hiddenClass" value=''}
                                    {assign var="showMore" value=false}
                                    {foreach $facet->getValues() as $option}
                                        {if $showMore == false}
                                            {if $facet->getFacetName() == 'property'}
                                                {if $bxFacets->isFacetValueHidden({$facetOptions.{$facet->getLabel()|trim}.fieldName|trim},
                                                {$option->getLabel()|substr:0:{{$option->getLabel()|strrpos:'('}-1}|cat:'_bx_'|cat:$option->getId()|trim})}
                                                    {assign var="showMore" value=true}
                                                {/if}
                                            {else}
                                                {if $bxFacets->isFacetValueHidden({$facetOptions.{$facet->getLabel()|trim}.fieldName},
                                                {$option->getLabel()|substr:0:{{$option->getLabel()|strrpos:'('}-1}|trim})}
                                                    {assign var="showMore" value=true}
                                                {/if}
                                            {/if}
                                            {if $showMore == true}
                                                {assign var="hiddenClass" value=' hidden-items'}
                                                {assign var="optionStyle" value='style="display:none"'}
                                            {/if}
                                        {/if}
                                        {block name="frontend_listing_filter_facet_value_list_option"}
                                            <li class="filter-panel--option{$hiddenClass}" {$optionStyle}>
                                                {block name="frontend_listing_filter_facet_value_list_option_container"}
                                                    <div class="option--container">
                                                        {block name="frontend_listing_filter_facet_value_list_input"}
                                                            <span class="filter-panel--checkbox">
                                                                <input type="checkbox"
                                                                       id="__{$facet->getFieldName()|escape:'htmlall'}__{$option->getId()|escape:'htmlall'}"
                                                                       name="__{$facet->getFieldName()|escape:'htmlall'}__{$option->getId()|escape:'htmlall'}"
                                                                       value="{$option->getId()|escape:'htmlall'}"
                                                                       {if $option->isActive()}checked="checked" {/if}/>
                                                                <span class="checkbox--state">&nbsp;
                                                                </span>
                                                            </span>
                                                        {/block}
                                                        {block name="frontend_listing_filter_facet_value_list_label"}
                                                            <label class="filter-panel--label"
                                                                   for="__{$facet->getFieldName()|escape:'htmlall'}__{$option->getId()|escape:'htmlall'}">
                                                                {$option->getLabel()|escape}
                                                            </label>
                                                        {/block}
                                                    </div>
                                                {/block}
                                            </li>
                                        {/block}
                                    {/foreach}
                                    {if $showMore == true}
                                        <li style="cursor:pointer" class="show-more-values">{s namespace="boxalino/intelligence" name="filter/morevalues"}{/s}</li>
                                    {/if}
                                </ul>
                            {/block}
                        </div>
                    {/block}
                </div>
            {/block}
        </div>
    {/block}
{else}
    {$smarty.block.parent}
{/if}