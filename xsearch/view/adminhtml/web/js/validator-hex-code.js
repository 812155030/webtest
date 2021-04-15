define([
    'jquery',
    'mage/translate',
    'jquery/validate'
], function ($) {
    'use strict';

    return function (hexCodeValidator) {
        $.validator.addMethod(
            'validate-hex-color',
            function (value) {
                return /^([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/i.test(value);
            },
            $.mage.__('Field must have valid hex color code.')
        );

        return hexCodeValidator;
    };
});
