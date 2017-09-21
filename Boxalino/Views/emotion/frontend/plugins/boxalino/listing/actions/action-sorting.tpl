{extends file="frontend/listing/listing_actions"}

{block name="frontend_listing_actions_sort_field_relevance"}
    <option value="7"{if $sSort eq 7} selected="selected"{/if}>{s namespace="frontend/listing/listing_actions" name='ListingSortRelevance'}{/s}</option>
{/block}