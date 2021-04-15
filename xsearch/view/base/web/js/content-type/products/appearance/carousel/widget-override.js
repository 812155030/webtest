/**
 * Override Magento module-page-builder/view/base/web/js/content-type/products/appearance/carousel/widget.js
 */

define([
    'jquery',
    'underscore',
    'matchMedia',
    'Magento_PageBuilder/js/utils/breakpoints',
    'Magento_PageBuilder/js/events',
    'Amasty_Base/vendor/slick/slick.min'
], function ($, _, mediaCheck, breakpointsUtils, events) {
    'use strict';

    /**
     * Initialize slider.
     *
     * @param {jQuery} $carouselElement
     * @param {Object} config
     */
    function buildSlick($carouselElement, config) {
        /**
         * Prevent each slick slider from being initialized more than once which could throw an error.
         */
        if ($carouselElement.hasClass('slick-initialized')) {
            $carouselElement.slick('unslick');
        }

        config.slidesToScroll = config.slidesToShow;
        $carouselElement.slick(config);
    }

    return function (config, element) {
        var $element = $(element),
            $carouselElement = $($element.children()),
            productCount = $(element).find('.product-item').length,
            centerModeClass = 'center-mode',
            amSearchClass = 'amsearch-pagebuilder-carousel',
            carouselMode = $element.data('carousel-mode'),
            slickConfig = {
                autoplay: $element.data('autoplay'),
                autoplaySpeed: $element.data('autoplay-speed') || 0,
                arrows: $element.data('show-arrows'),
                dots: $element.data('show-dots')
            };

        $element.addClass(amSearchClass);

        _.each(config.breakpoints, function (breakpoint) {
            mediaCheck({
                media: breakpointsUtils.buildMedia(breakpoint.conditions),

                /** @inheritdoc */
                entry: function () {
                    var slidesToShow = breakpoint.options.products[carouselMode] ?
                        breakpoint.options.products[carouselMode].slidesToShow :
                        breakpoint.options.products.default.slidesToShow;

                    slickConfig.slidesToShow = parseFloat(slidesToShow);

                    if (carouselMode === 'continuous' && productCount > slickConfig.slidesToShow) {
                        $element.addClass(centerModeClass);
                        slickConfig.centerPadding = $element.data('center-padding');
                        slickConfig.centerMode = config.breakpoints['mobile-small'].conditions !== breakpoint.conditions;
                    } else {
                        $element.removeClass(centerModeClass);
                        slickConfig.infinite = $element.data('infinite-loop');
                    }

                    buildSlick($carouselElement, slickConfig);
                }
            });
        });

        // Redraw slide after content type gets redrawn
        events.on('contentType:redrawAfter', function (args) {
            if ($carouselElement.closest(args.element).length) {
                $carouselElement.slick('setPosition');
            }
        });
    };
});
