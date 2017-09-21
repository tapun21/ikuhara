<h2>Portfolio</h2>

{foreach $Data.portfolio as $portfolio}
    <h3>{$portfolio.group_name}</h3>
    <div id="" style="width:100%;height: 100%">
        <div class="top" style="width: 100%; float:left;">
            <span class="image--media" style="float:left">
                <img srcset="{$portfolio.picture.{$Data.lang}}">
            </span>
            <div class="bx-re-buy" style="float:left;width:25%">
                {include file="frontend/_includes/product_slider.tpl"
                articles=$portfolio.rebuy
                productSliderCls="product-slider--content"
                sliderMode={''}
                sliderArrowControls={''}
                sliderAnimationSpeed=500
                sliderAutoSlideSpeed={''}
                sliderAutoSlide={''}
                productBoxLayout="emotion"
                fixedImageSize="true"}
            </div>
        </div>

        <div class="bottom" style="width: 100%; float:left;">
            <div class="bx-re-orient" >
                {include file="frontend/_includes/product_slider.tpl"
                articles=$portfolio.reorient
                productSliderCls="product-slider--content"
                sliderMode={''}
                sliderArrowControls={''}
                sliderAnimationSpeed=500
                sliderAutoSlideSpeed={''}
                sliderAutoSlide={''}
                productBoxLayout="emotion"
                fixedImageSize="true"}
            </div>
        </div>

    </div>
{/foreach}

