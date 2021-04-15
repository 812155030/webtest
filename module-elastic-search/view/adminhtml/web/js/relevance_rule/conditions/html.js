define([
    'jquery',
    'Magento_Ui/js/form/components/html',
    'ko',
    'uiRegistry'
], function ($, html, ko, uiRegistry) {
    'use strict';

    return html.extend({
        afterRenderCallback: function () {
            var ns = this.ns,
                gridName = ns + '.' + ns + '.conditions_fieldset.products_grid';

            uiRegistry.get(gridName, this.initChangeListener.bind(this));
        },

        initChangeListener: function (productsGrid) {
            var observer = new MutationObserver(subscriber);

            function subscriber(mutations) {
                mutations.forEach(function (mutation) {
                    if (mutation.type === 'childList') {
                        productsGrid.visible(false);
                    }
                });
            }

            observer.observe($('.rule-tree')[0], {
                childList: true,
                subtree: true
            });
        }
    });
});
