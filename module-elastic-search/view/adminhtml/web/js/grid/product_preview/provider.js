define([
    'jquery',
    'Magento_Ui/js/grid/provider',
    'ko'
], function ($, provider, ko) {
    'use strict';

    return provider.extend({
        defaults: {
            externalLinks: {
                relevanceRuleWebsite: null
            }
        },

        /**
         * @inheritDoc
         */
        initObservable: function () {
            this.externalLinks.relevanceRuleWebsite = ko.observable(this.externalLinks.relevanceRuleWebsite);

            return this._super();
        },

        /**
         *
         * @param {object} options
         */
        reload: function (options) {
            var relevanceRuleCondition = $('[data-form-part="amasty_elastic_relevancerule_form"]').serialize(),
                selectedWebsite = +this.externalLinks.relevanceRuleWebsite();

            if (typeof this.params.filters === 'undefined') {
                this.params.filters = {};
            }

            if (selectedWebsite) {
                relevanceRuleCondition += '&websites[]=' + selectedWebsite;
            }

            this.params.filters.elastic_rule_condition = relevanceRuleCondition;
            return this._super({'refresh': true});
        }
    });
});
