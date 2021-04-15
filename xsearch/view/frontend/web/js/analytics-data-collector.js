define([
    'jquery',
    'mage/url',
    'underscore',
    'mage/translate',
    'jquery-ui-modules/widget',
    'mage/cookies',
], function ($, urlBuilder, _) {
    'use strict';

    $.widget('mage.amXsearchAnalyticsDataCollector', {
        dataCollectorPool: [],
        dataForSend: [],

        options: {
            baseUrl: window.BASE_URL,
            backendUrl: 'amasty_xsearch/analytics/collect',
            searchPopupSelector: '.amsearch-results',
            productActionsSelector: '[data-amsearch-js="item-actions"]',
            throttleTime: 500,
            searchClickSelectors: {
                selectorSearchPageLink: '.catalogsearch-result-index .search.results .product-item',
                selectorSearchPageAddToCart: '.catalogsearch-result-index .search.results .tocart',
                selectorPopupLink: '.search-autocomplete .amsearch-results .product-item-link',
                selectorPopupAddToCart: '.search-autocomplete .amsearch-results .tocart',
                selectorPopupCategories: '.search-autocomplete .amsearch-results .amsearch-item[data-search-block-type="category"]',
                selectorPopupCMS: '.search-autocomplete .amsearch-results .amsearch-item[data-search-block-type="brand"]',
                selectorPopupBrand: '.search-autocomplete .amsearch-results .amsearch-item[data-search-block-type="page"]'
            },
            popupProductSelector: '[data-search-block-type="product"]'
        },

        _create: function () {
            this.initUrls();
            this.addListener();
            this.sendData = _.throttle(this.sendData.bind(this), this.options.throttleTime);
            this.initDataCollectors();
        },


        addListener: function () {
            $(document).on('click', this.options.searchPopupSelector, this.handleClick.bind(this));
            $(document).on('amXsearchAnalyticsAddDataCollector', function (event, collector) {
               this.addDataCollector(collector);
            }.bind(this));
        },

        initDataCollectors: function () {
          this.addDataCollector(this.handleSearchClick.bind(this));
        },

        initUrls: function () {
            urlBuilder.setBaseUrl(this.options.baseUrl);
            this.options.backendUrl = urlBuilder.build(this.options.backendUrl);
        },

        /**
         * Add telemetry collector function to queue
         *
         * @param {function(jQuery): object|false} collector
         */
        addDataCollector: function (collector) {
            if (false === collector instanceof Function) {
                throw new Error($.mage.__('The argument must be a function'))
            }

            this.dataCollectorPool.push(collector);
        },

        /**
         * @param {Event} event
         */
        handleClick: function (event) {
            var clickedElement = $(event.target);

            this.dataCollectorPool.forEach(function (dataCollector) {
                var result = dataCollector(clickedElement);

                if (result !== false) {
                    this.dataForSend.push(result);
                }
            }.bind(this));

            this.sendData();
        },

        sendData: function () {
            if (this.dataForSend.length > 0) {
                $.ajax({
                    url: this.options.backendUrl,
                    data: {
                        form_key: $.mage.cookies.get('form_key'),
                        telemetry: this.dataForSend
                    },
                    method: 'POST',
                    success: function () {
                        this.dataForSend = []
                    }.bind(this)
                });
            }
        },

        /**
         *
         * @param {string} type
         * @param {object} additionalData
         */
        getTelemetryObject: function (type, additionalData) {
            return Object.assign(
                {type: type},
                additionalData
            );
        },

        /**
         *
         * @param {jQuery} element
         * @returns {boolean|object}
         */
        handleSearchClick: function (element) {
            var result = false,
                acceptableElementSelector = Object.values(this.options.searchClickSelectors).join(', ');

            if (element.closest(acceptableElementSelector).length) {
                result = this.getTelemetryObject('search_click', {});
            }

            return result;
        }
    });

    return $.mage.amXsearchAnalyticsDataCollector;
});
