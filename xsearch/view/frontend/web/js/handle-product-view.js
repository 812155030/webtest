define([
    'jquery',
    'mage/url',
    'mage/cookies',
    'domReady!'
], function ($, urlBuilder) {
    'use strict';

    $.widget('mage.amXsearchCollectProductView', {
        options: {
            backendUrl: 'amasty_xsearch/analytics/collect',
            detectProductConfig: [
                {selector: '.price-container [id^="product-price-"]', attribute: 'id'},
                {selector: '[data-price-box^="product-id-"]', attribute: 'data-price-box'},
                {selector: '[data-product-id]', attribute: 'data-product-id'},
                {selector: '[name="product"]', attribute: 'value'},
                {selector: '#review-form', attribute: 'action', regex: /\/(\d+)\/$/g},
            ]
        },

        _create: function () {
            var productId = this.getProductId();

            if (!isNaN(productId)) {
                this.logProductView(productId);
            }
        },

        /**
         * @return {number|NaN}
         */
        getProductId: function () {
            var result = NaN,
                config = this.options.detectProductConfig;

            for (var i = 0; i < config.length; ++i) {
                var selector = config[i].selector,
                    attribute = config[i].attribute,
                    regex = config[i].regex || /\d+$/,
                    element = $(selector)

                if (element.length) {
                    var attributeValue = element.attr(attribute),
                    productIdParsingResult = regex.exec(attributeValue);

                    if (productIdParsingResult !== null) {
                        result = Number(productIdParsingResult[1] || productIdParsingResult[0]);
                        break;
                    }
                }
            }

            return result;
        },

        /**
         * @param {number} productId
         */
        logProductView: function (productId) {
            $.ajax({
                url: urlBuilder.build(this.options.backendUrl),
                method: 'POST',
                data: {
                    form_key: $.mage.cookies.get('form_key'),
                    telemetry: [{type: 'product_view', product_id: productId}]
                }
            });
        }
    });

    return $.mage.amXsearchCollectProductView;
});
