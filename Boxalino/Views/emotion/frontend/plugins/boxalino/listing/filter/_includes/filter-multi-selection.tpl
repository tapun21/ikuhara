{extends file="parent:frontend/listing/filter/_includes/filter-multi-selection.tpl"}
{if $bxFacets}
    {block name="frontend_listing_filter_facet_multi_selection"}
    <div class="filter-panel filter--multi-selection filter-facet--{$filterType} facet--{$facet->getFacetName()|escape:'htmlall'}"
         data-filter-type="{$filterType}"
         data-facet-name="{$facet->getFacetName()}"
         data-field-name="{$facet->getFieldName()|escape:'htmlall'}">

        {block name="frontend_listing_filter_facet_multi_selection_flyout"}
            <div class="filter-panel--flyout">

            {block name="frontend_listing_filter_facet_multi_selection_title"}
                <label class="filter-panel--title" for="{$facet->getFieldName()|escape:'htmlall'}">
                    {$facet->getLabel()|escape}
                </label>
            {/block}

            {block name="frontend_listing_filter_facet_multi_selection_icon"}
                <span class="filter-panel--icon"></span>
            {/block}
            {block name="frontend_listing_filter_facet_value_list_search_field"}
                {if $bxFacets->getFacetExtraInfo({$facetOptions.{$facet->getLabel()|trim}.fieldName|trim}, 'visualisation') == 'search'}
                    <div class="bx-{$facet->getFacetName()|escape:'htmlall'}-search" style="padding: 0rem 0rem 1rem 0rem;position:relative;0.5px solid">
                        <input class="bx--facet-search" type="search" style="width: 100%;border: 1px solid;" />
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
            {block name="frontend_listing_filter_facet_multi_selection_content"}
                {$inputType = 'checkbox'}

                {if $filterType == 'radio'}
                    {$inputType = 'radio'}
                {/if}

                {$indicator = $inputType}

                {$isMediaFacet = false}
                {if $facet|is_a:'\Shopware\Bundle\SearchBundle\FacetResult\MediaListFacetResult'}
                    {$isMediaFacet = true}

                    {$indicator = 'media'}
                {/if}

            <div class="filter-panel--content input-type--{$indicator}">

                {block name="frontend_listing_filter_facet_multi_selection_list"}
                    <ul class="filter-panel--option-list">

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
                        {block name="frontend_listing_filter_facet_multi_selection_option"}
                        <li class="filter-panel--option{$hiddenClass}" {$optionStyle}>

                            {block name="frontend_listing_filter_facet_multi_selection_option_container"}
                                <div class="option--container">

                                {block name="frontend_listing_filter_facet_multi_selection_input"}
                                <span class="filter-panel--input filter-panel--{$inputType}">
                                    {$name = "__{$facet->getFieldName()|escape:'htmlall'}__{$option->getId()|escape:'htmlall'}"}
                                                                {if $filterType == 'radio'}
                                    {$name = {$facet->getFieldName()|escape:'htmlall'} }
                                {/if}

                                <input type="{$inputType}"
                                                                       id="__{$facet->getFieldName()|escape:'htmlall'}__{$option->getId()|escape:'htmlall'}"
                                                                       name="{$name}"
                                                                       value="{$option->getId()|escape:'htmlall'}"
                                                                       {if $option->isActive()}checked="checked" {/if}/>

                                                                <span class="input--state {$inputType}--state">&nbsp;</span>
                                                            </span>
                                                        {/block}

                                                        {block name="frontend_listing_filter_facet_multi_selection_label"}
                                                            <label class="filter-panel--label"
                                                                   for="__{$facet->getFieldName()|escape:'htmlall'}__{$option->getId()|escape:'htmlall'}">

                                                                {if $facet|is_a:'\Shopware\Bundle\SearchBundle\FacetResult\MediaListFacetResult'}
                                                                    {$mediaFile = {link file='frontend/_public/src/img/no-picture.jpg'}}
                                                                    {if $option->getMedia()}
                                                                        {$mediaFile = $option->getMedia()->getFile()}
                                                                    {/if}

                                                                    <img class="filter-panel--media-image" src="{$mediaFile}" alt="{$option->getLabel()|escape:'htmlall'}" />
                                                                {else}
                                                                    {$option->getLabel()|escape}
                                                                {/if}
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