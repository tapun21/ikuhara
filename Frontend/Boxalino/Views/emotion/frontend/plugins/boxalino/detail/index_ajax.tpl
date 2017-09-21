{extends name="parent:frontend/detail/index.tpl"}

{block name="frontend_detail_index_tabs_cross_selling"}
    <div class="tab-menu--cross-selling"{if $sArticle.relatedProductStreams} data-scrollable="true"{/if}>
        <div id="bx-loader"></div>
    </div>
{/block}

{block name="frontend_index_header_javascript_jquery_lib"}
    {$smarty.block.parent}
    <script>
        $(document).ready(function() {
            var el = $('<div>', {
                'class': 'js--loading-indicator indicator--relative',
                'html': $('<i>', {
                    'class': 'icon--default'
                })
            });
            $('#bx-loader').append(el);
            var controller  = '{url controller=RecommendationSlider action=detail articleId=$sArticle.articleID sCategory=$sArticle.categoryID}';
            $.ajax({
                type: "GET",
                url: controller
            }).done(function(res) {
                $('.tab-menu--cross-selling').html(res);
                StateManager.addPlugin('.tab-menu--cross-selling .tab--header', 'swCollapsePanel', {
                    'contentSiblingSelector': '.tab--content'
                }, ['xs', 's']);
                StateManager.updatePlugin('*[data-product-slider="true"]','swProductSlider');
                StateManager.destroyPlugin('.tab-menu--cross-selling','swTabMenu');
                StateManager.addPlugin('.tab-menu--cross-selling','swTabMenu', {}, ['xs','s','m','l','xl']);
            }, function (err) {
            });
        });
    </script>
{/block}