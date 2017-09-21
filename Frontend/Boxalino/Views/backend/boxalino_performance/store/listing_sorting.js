//{block name="backend/performance/store/listing_sorting"}
// {$smarty.block.parent}
Ext.define('Shopware.apps.BoxalinoPerformance.store.ListingSorting', {
    override: 'Shopware.apps.Performance.store.ListingSorting',

    fields: [ 'id', 'name' ],

    proxy: {
        type: 'ajax',
        url: '{url controller=BoxalinoPerformance action=getListingSortings}',
        reader: {
            type: 'json',
            root: 'data'
        }
    }
});
//{/block}
