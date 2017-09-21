{extends file='frontend/index/index.tpl'}
{block name="frontend_index_header_javascript_jquery_lib"}
    {$smarty.block.parent}
    <script>
        document.asyncReady(function() {
            $(document).ready(function() {
                var facetOptions = {$facetOptions|json_encode};
                if($('.panel--paging').length) {
                    $('.panel--paging').addClass('listing--paging');
                }
                if($('.sidebar-filter--content').length) {
                    expandFacets(facetOptions);
                }
                $.subscribe('plugin/swListingActions/onOpenFilterPanel', function() {
                    expandFacets(facetOptions);
                });
                $.subscribe('plugin/swListingActions/onGetFilterResultFinished', function(m,r,i) {
                    var el = $('.bx-tab-article-count');
                    if(el.length){
                        el.text(i.totalCount);
                    }
                });
                $.overridePlugin('swFilterComponent',{
                    open: function(closeSiblings) {
                        var me = this;
                        me.$el.addClass(me.opts.collapseCls);
                        $.publish('plugin/swFilterComponent/onOpen', [ me ]);
                    },
                    updateValueList: function(data) {
                        var me = this, $elements, values, ids, activeIds, checkedIds;

                        $elements = me.convertToElementList(me.$inputs);
                        values = me.getValues(data, $elements);
                        values = me.convertValueIds(values);
                        ids = me.getValueIds(values);
                        activeIds = me.getActiveValueIds(values);
                        checkedIds = me.getElementValues(
                            me.getCheckedElements($elements)
                        );

                        if (me.validateComponentShouldBeDisabled(data, values, checkedIds)) {
                            me.disableAll($elements, values);
                            return;
                        }
                        $elements.each(function(index, $element) {
                            var val = $element.val() + '';
                            var value = me.findValue(val, values);
                            var disable = me.validateElementShouldBeDisabled($element, activeIds, ids, checkedIds, value);
                            me.disable($element, disable);
                            me.setDisabledClass($element.parents('.filter-panel--input'), disable);
                            if(!disable && value !== null) {
                                $element.parents('.filter-panel--input').next().text(value['label']);
                            }
                        });

                        me.disableComponent(me.allDisabled($elements));
                    }
                });
                StateManager.destroyPlugin('*[data-filter-type]','swFilterComponent');
                StateManager.updatePlugin('*[data-filter-type]','swFilterComponent');

                var snippetValues = {
                    "more": '{s namespace="boxalino/intelligence" name="filter/morevalues"}{/s}',
                    "less": '{s namespace="boxalino/intelligence" name="filter/lessvalues"}{/s}'
                };
                $(".show-more-values").on('click', function () {
                    var header = $(this);
                    var content = header.parent().find('.hidden-items');
                    content.slideToggle(500, function () {
                        header.text(function () {
                            return content.is(":visible") ? snippetValues['less'] : snippetValues['more'];
                        });
                    });
                });
                $('.search-remove').on('click', function() {
                    if($(this).hasClass('icon--cross')) {
                        var searchInput = $(this).prev();
                        if(searchInput.val() !== ''){
                            toggleSearchIcon($(this));
                        }
                        searchInput.val("");
                        $(this).parent().next().find('.show-more-values').show();
                        $(this).parent().next().find('.filter-panel--option').each(function(i, e) {
                            var label = $(e).find('label');
                            label.html(label.text());
                            if($(e).hasClass('hidden-items')){
                                $(e).hide();
                            } else {
                                $(e).show();
                            }
                        });
                    }
                });
                $(".bx--facet-search").on('keyup', function() {
                    var text = $(this).val(),
                        iconElement =  $(this).next();
                    if(text === ''){
                        iconElement.trigger('click');
                    } else {
                        var options = $(this).parent().next().find('.filter-panel--option');
                        var regMatch = new RegExp(escapeRegExp(text), 'gi'),
                            regMatch2 = new RegExp(escapeRegExp(text.slice(0, text.length-1)), 'gi');
                        $(this).parent().next().find('.show-more-values').hide();
                        options.each(function(i, e) {
                            var label = $(e).find('label').text(),
                                match = null,
                                m = label.match(regMatch),
                                m2 = label.match(/\'/g);
                            if(Array.isArray(m2)){
                                if(m){
                                    match = m;
                                }else {
                                    if(text.length > 1){
                                        var quoteMatches = [],
                                            reg =  /(\')/g;
                                        while((m2 = reg.exec(label)) !== null) {
                                            quoteMatches.push(m2);
                                        }
                                        var label2 = label.replace(reg, '');
                                        quoteMatches.forEach(function (m) {
                                            if(match === null){
                                                while((m2 = regMatch2.exec(label2)) !== null) {
                                                    if(m2.index < m.index && match === null){
                                                        match = label.slice(m2.index, m2.index + text.length+1);
                                                    }
                                                }
                                            }
                                        });
                                    }
                                }
                            } else {
                                if(m){
                                    match = m[0];
                                }
                            }
                            if(match) {
                                $(e).find('label').html(label.replace(match, '<strong>'+match+'</strong>'));
                                $(e).show();
                            } else {
                                $(e).hide();
                            }
                        });
                    }
                    if(text.length > 0 && iconElement.hasClass('icon--search')) {
                        toggleSearchIcon(iconElement);
                    } else if(text.length === 0 && iconElement.hasClass('icon--cross')) {
                        toggleSearchIcon(iconElement);
                    }
                });
                function escapeRegExp(text) {
                    text.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&');
                    text = text.replace(/[a|\u00E0|\u00E1|\u00E2|\u00E3|\u00E4](?![^[]*\])/gi, '[a|ä|\u00E0|\u00E1|\u00E2|\u00E3|\u00E4]');
                    text = text.replace(/[(e|\u00E8|\u00E9|\u00EA|\u00EB)](?![^[]*\])/gi, '[e|é|\u00E8|\u00E9|\u00EA|\u00EB]');
                    text = text.replace(/[i|\u00EC|\u00ED|\u00EE|\u00EF](?![^[]*\])/gi, '[i|\u00EC|\u00ED|\u00EE|\u00EF]');
                    text = text.replace(/[o|\u00F2|\u00F3|\u00F4|\u00F5|\u00F6](?![^[]*\])/gi, '[o|\u00F2|\u00F3|\u00F4|\u00F5|\u00F6]');
                    text = text.replace(/[u|\u00F9|\u00FA|\u00FB|\u00FC](?![^[]*\])/gi, '[u|\u00F9|\u00FA|\u00FB|\u00FC]');
                    return text;
                }
                function expandFacets(facetOptions) {
                    var filters = $('#filter').find('.filter-panel');
                    setTimeout(function(filters, facetOptions){
                        filters.each(function(i, e) {
                            var fieldName = $.trim($(e).find('.filter-panel--title').text());
                            if(facetOptions.hasOwnProperty(fieldName) && facetOptions[fieldName]['expanded'] === true) {
                                $(this).addClass("is--collapsed");
                            }
                        });
                    }, 1, filters, facetOptions);
                }
                function toggleSearchIcon(iconElement) {
                    iconElement.toggleClass('icon--search');
                    iconElement.toggleClass('icon--cross');
                }
            });
        });
    </script>
{/block}