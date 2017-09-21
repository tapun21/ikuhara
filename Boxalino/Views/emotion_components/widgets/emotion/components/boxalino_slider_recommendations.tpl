{extends file="parent:widgets/emotion/components/component_article_slider.tpl"}

{* Slider content based on the configuration *}
{block name="widget_emotion_component_product_slider_content"}
    {$sliderMode = 'ajax'}
      {include file="frontend/_includes/product_slider.tpl"
    articles=$articles
    productSliderCls="product-slider--content"
    sliderMode=$sliderMode
    sliderAjaxCtrlUrl=$Data.ajaxFeed
    sliderAjaxCategoryID=$Data.article_slider_category
    sliderAjaxMaxShow=$Data.article_slider_max_number
    sliderArrowControls={($Data.article_slider_arrows != 1) ? 'false' : ''}
    sliderAnimationSpeed=$Data.article_slider_scrollspeed
sliderAutoSlideSpeed={($Data.article_slider_rotatespeed) ? ($Data.article_slider_rotatespeed / 1000) : ''}
sliderAutoSlide={($Data.article_slider_rotation) ? 'true' : ''}
productBoxLayout="emotion"
fixedImageSize="true"}
{/block}