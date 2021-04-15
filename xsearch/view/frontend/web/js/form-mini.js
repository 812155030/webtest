define([
    'jquery',
    'underscore',
    'uiRegistry',
    'quickSearch-original',
    'mage/cookies',
    'pageCache',
    'Amasty_Base/vendor/slick/slick.min',
    'catalogAddToCart'
], function ($, _, uiRegistry) {
    'use strict';

    $.widget('mage.amXsearchFormMini', $.mage.quickSearch, {
        ajaxRequest: null,
        queryString: '',
        timer: null,
        delay: 500,
        minSizePopup: 700,
        minLeftSidePopup: 250,
        slidersBlockSize: 450,
        sizePopupBreakpoint: 550,
        mobileView: 768,
        documentWidth: $(document).width(),
        proportionSide: 0.33,
        options: {
            url: null,
            responseFieldElements: '.amsearch-item',
            currentUrlEncoded: null,
            minChars: 5
        },
        selectors: {
            loader: '[data-amsearch-js="loader"]',
            inputWrapper: '[data-amsearch-js="search-wrapper-input"]',
            productItem: '[data-amsearch-js="product-item"]',
            splitWishlistButton: '.split.wishlist [data-toggle="dropdown"]',
            postNewWishlist: '[data-post-new-wishlist]',
            pageWrapper: '.page-wrapper',
            wishlistTemplates: {
                popup: '#popup-tmpl',
                splitButton: '#split-btn-tmpl',
                form: '#form-tmpl-multiple'
            },
            searchAutocomplete: '.search-autocomplete',
            itemContainer: '.amsearch-item-container',
            form: '.form.minisearch',
            preloadSection: '[data-amsearch-js="preload"]',
            productResults: '[data-amsearch-js="products"]',
            leftSide: '[data-amsearch-js="left-side"]',
            rightsideContent: '[data-amsearch-js="rightside-content"]',
            leftsideSidebar: '[data-amsearch-js="leftside-sidebar"]',
            resultsBlockSlider: '[data-amsearch-js="results"] [data-amsearch-js="slider-block"]',
            addToCartForm: '[data-amsearch-js="add-to-cart-form"]'
        },
        classes: {
            active: 'active',
            searchContainer: 'amsearch-form-container',
            searchContainerResult: '-result',
            searchContainerHistory: '-history',
            columns: '-columns',
            positions: {
                top: '-top-position',
                right: '-right-position',
                bottom: '-bottom-position',
                left: '-left-position'
            }
        },

        _create: function () {
            var self = this,
                timer;

            self.currentView = self.documentWidth >= self.mobileView ? 'desktop' : 'mobile';

            if (window.xsearch_options == undefined) {
                self.updateOptions();
            }

            self.options = $.extend(true, self.options, window.xsearch_options);
            self.responseList = {
                indexList: null,
                selected: null
            };
            self.searchForm = self.element.parents(self.options.formSelector);
            self.autoComplete = this.searchForm.find(self.options.destinationSelector);
            self.submitBtn = self.searchForm.find(self.options.submitBtn)[0];
            self.searchLabel = this.searchForm.find(self.options.searchLabel);
            self.redirectUrl = null;

            self.createCloseIcon();
            self.createLoupeIcon();
            self.createSearchWrapper();
            self.defineHideOrClear();
            self.createLoader();

            window.addEventListener('resize', function () {
                _.throttle(self.checkCurrentView(), self.delay);
            }, false);

            _.bindAll(self, '_onKeyDown', '_onPropertyChange', '_onSubmit', 'onClick');
            self.submitBtn.disabled = true;
            self.element.attr('autocomplete', this.options.autocomplete);

            self.element.on('blur', $.proxy(function () {
                timer = setTimeout($.proxy(function () {
                    this._updateAriaHasPopup(false);
                }, self), 250);
            }, self));

            self.element.trigger('blur');
            self.element.on('focus', $.proxy(function () {
                if (timer != null) {
                    clearTimeout(timer);
                }

                self.searchLabel.addClass('active');
            }, self));

            self.element.one('focus', self.setInputPosition());

            self.element.on('keydown', self._onKeyDown);
            var ua = window.navigator.userAgent,
                msie = ua.indexOf("MSIE ");

            if (msie > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./)) {
                $(self.element).keyup(self._onPropertyChange);
            } else {
                self.element.on('input propertychange', self._onPropertyChange);
            }

            self.element.on('click', this.onClick);
            self.searchForm.on('submit', $.proxy(function (e) {
                self._onSubmit(e);
                self._updateAriaHasPopup(false);
            }, self));

            self.updatePreloadSection();

            self.searchForm.find(this.selectors.searchAutocomplete).attr('tabindex', -1);
        },

        /**
         * This function is called after the block was inserted
         *
         * @param {jQuery} insertedBlock
         */
        popupDataInsertAfter: function(insertedBlock) {
            insertedBlock.formKey();

            switch (true) {
                case $.fn.lazyload instanceof Function:
                    insertedBlock.find('img.lazy, img.lazyload').lazyload({
                        effect: 'fadeIn'
                    });
                    break;
                case $.fn.Lazy instanceof Function:
                    insertedBlock.find('img.lazy').Lazy({
                        effect: 'fadeIn'
                    });
                    break;
            }
        },

        checkCurrentView: function () {
            var currentWith = $(document).width(),
                nextView;

            if (currentWith >= this.mobileView) {
                nextView = 'desktop';
            } else {
                nextView = 'mobile';
            }

            if (this.currentView !== nextView) {
                this.currentView = nextView;
                this.hidePopup();
            }
        },

        updateOptions: function () {
            $.ajax({
                url: this.options.url.replace("search/ajax/suggest", "amasty_xsearch/autocomplete/options"),
                type: 'POST',
                data: {},
                async: false,
                success: function (data) {
                    window.xsearch_options = JSON.parse(data);
                }
            });
        },

        setInputPosition: function () {
            var positions = this.classes.positions,
                targets = this.element.closest(this.selectors.form)
                    .find(this.selectors.inputWrapper + ', ' + this.selectors.searchAutocomplete),
                elementOffsetLeft = Math.round(this.element.offset().left),
                elementOffsetTop = Math.round(this.element.offset().top),
                halfWindowWidth = $(window).width() / 2,
                windowHeight = document.documentElement.scrollHeight,
                autocompletePopupMaxHeight = window.innerHeight * 0.8; // autocomplete popup max height inherited with the window height

            // set left or right position based on the half of window width
            if (elementOffsetLeft && elementOffsetLeft < halfWindowWidth) {
                targets.removeClass(positions.right).addClass(positions.left);
            } else if (elementOffsetLeft && elementOffsetLeft > halfWindowWidth) {
                targets.removeClass(positions.left).addClass(positions.right);
            }

            // set top or bottom autocomplete popup position based on the document height minus max popup height
            if (elementOffsetTop > (windowHeight - autocompletePopupMaxHeight)) {
                targets.removeClass(positions.bottom).addClass(positions.top);
            } else if (elementOffsetTop < (windowHeight - autocompletePopupMaxHeight)) {
                targets.removeClass(positions.top).addClass(positions.bottom);
            }
        },

        updatePreloadSection: function () {
            var preloadBlock = this.searchForm.find(this.selectors.preloadSection);

            $.ajax({
                url: this.options.url.slice(0, -1) + 'recent',
                data: {uenc: this.options.currentUrlEncoded},
                dataType: "html",
                success: function (html) {
                    var preloadBody = $(html),
                        preloadBlocks = preloadBody.children();

                    preloadBlocks = preloadBlocks.filter(function (index, element) {
                        var preloadSections = $(element).children(),
                            sectionsContent = preloadSections.html();

                        return preloadSections.length !== 0 && sectionsContent && sectionsContent.trim() !== '';
                    });

                    if (preloadBlocks.length !== 0) {
                        preloadBody.html(preloadBlocks);
                        preloadBody.formKey();
                        preloadBlock.html(preloadBody);
                    }
                }.bind(this)
            });
        },

        onClick: function () {
            if (this.element[0] != document.activeElement && !!window.MSInputMethodContext && !!document.documentMode) {
                return false;//fix for IE(IE trigger input event when placeholder changed)
            }

            var preload = this.searchForm.find('[data-amsearch-js="preload"]');
            if (preload && preload.length > 0) {
                this.showPopup(preload.html());
            } else {
                this.getEmptyRequest();
            }

            var value = this.element.val().trim(),
                minChars = this.options.minChars ? this.options.minChars : this.options.minSearchLength;
            if (value.length >= parseInt(minChars, 10)
                && this.ajaxRequest
                && this.ajaxRequest.readyState !== 1
            ) {
                this._onPropertyChangeCallBack();
            }
        },

        _onSubmit: function (e) {
            var value = this.element.val().trim();

            if (value.length === 0 || value == null || /^\s+$/.test(value)) {
                e.preventDefault();
            }

            if (this.redirectUrl) {
                e.preventDefault();
                window.location.assign(this.redirectUrl);
            }
        },

        _onKeyDown: function (e) {
            var keyCode = e.keyCode || e.which;

            switch (keyCode) {
                case $.ui.keyCode.ESCAPE:
                    this._resetResponseList(true);
                    this.autoComplete.hide();
                    break;

                case $.ui.keyCode.ENTER:
                    this.searchForm.trigger('submit');
                    e.preventDefault();
                    break;

                case $.ui.keyCode.DOWN:
                    if (this.responseList.indexList) {
                        this.searchForm.find(this.selectors.searchAutocomplete).focus();
                    }

                    break;
                default:
                    return true;
            }
        },

        showPopup: function (data) {
            var dropdown = $('<div class="amsearch-results" data-amsearch-js="results"></div>'),
                searchResults = $('<div class="amsearch-leftside" data-amsearch-js="left-side"></div>'),
                leftSide = '[data-amsearch-js="left-side"]',
                defaultSearchBlock = this.searchForm.width(),
                currentWidth = $(document).width(),
                popularSearch = '[data-search-block-type="popular_searches"]',
                recentSearch = '[data-search-block-type="recent_searches"]',
                closeLoupeIcons = '[data-amsearch-js = "close"], [data-amsearch-js="loupe"]';

            dropdown.append(searchResults);

            this.searchForm.removeClass(this.classes.searchContainerResult + ' ' + this.classes.searchContainerHistory);
            this.searchForm.addClass(this.classes.searchContainer);

            if ($.type(data) == 'string') {
                searchResults.append(data);
                this.searchForm.addClass(this.classes.searchContainerHistory);
            } else {
                this.searchForm.addClass(this.classes.searchContainerResult);
                for (var i in data) {
                    if (data[i].type === 'product'
                        && this.options.width >= this.sizePopupBreakpoint
                        && currentWidth >= this.mobileView
                    ) {
                        dropdown.append(data[i].html);
                    } else {
                        searchResults.append($(data[i].html).addClass(data[i].type));
                    }
                }
            }

            this.changePopupFlow();
            this.responseList.indexList = this.autoComplete.empty().append(dropdown)
                .addClass('amsearch-clone-position')
                .show()
                .find(this.options.responseFieldElements + ':visible');

            this.autoComplete.trigger('contentUpdated');
            this.searchForm.find(popularSearch).parent(this.selectors.itemContainer).addClass('popular_searches');
            this.searchForm.find(recentSearch).parent(this.selectors.itemContainer).addClass('recent_searches');
            this.resizePopup();
            this.searchForm.find(closeLoupeIcons).appendTo(this.searchForm.find(this.selectors.inputWrapper)).show();
            this.searchForm.addClass('-opened').find('.input-text').attr('placeholder', $.mage.__('Enter Keyword or Item'));

            if (!this.searchForm.find(leftSide).children().length) {
                this.searchForm.find(leftSide).hide();
            }

            this._resetResponseList(false);
            this.element.removeAttr('aria-activedescendant');

            if (this.responseList.indexList.length) {
                this._updateAriaHasPopup(true);
                this.initSlickSlider($(this.selectors.resultsBlockSlider, this.searchForm));
                $(this.selectors.addToCartForm, this.searchForm).catalogAddToCart();
            } else {
                this._updateAriaHasPopup(false);
            }

            this.responseList.indexList
                .on('click', function (e) {
                    var $target = $(e.target);

                    if ($target.hasClass('amasty-xsearch-block-header')) {
                        return false;
                    }

                    if (!$target.attr('data-click-url')) {
                        $target = $(e.target).closest('[data-click-url]');
                    }
                    if ($(e.target).closest('[data-amsearch-js="item-actions"]').length === 0
                        && $(e.target).closest('[data-amsearch-js="product-item"]').length
                    ) {
                        document.location.href = $target.attr('data-click-url');
                    } else {
                        this.element.focus();
                        this.element.trigger('focus');
                        this.element.blur();
                    }
                }.bind(this))
                .on('mouseenter mouseleave', function (e) {
                    if (this.responseList && this.responseList.indexList) {
                        this.responseList.indexList.removeClass(this.options.selectClass);
                    }

                    $(e.target).addClass(this.options.selectClass);
                    this.responseList.selected = $(e.target);
                    this.element.attr('aria-activedescendant', $(e.target).attr('id'));
                }.bind(this));

            if (data !== undefined) {
                this.popupDataInsertAfter(dropdown);
            }

            return defaultSearchBlock;
        },

        /**
         * @param {Object} element - DOM jQuery element
         * @param {Object} options - slick slider options
         * @public
         * @returns {void}
         */
        initSlickSlider: function (element, options) {
            var defaultOptions = {
                    slidesToShow: 3,
                    slidesToScroll: 1,
                    infinite: false,
                    dots: true,
                    arrows: true,
                    responsive: [
                        {
                            breakpoint: this.mobileView,
                            settings: {
                                slidesToShow: 1,
                                arrows: false
                            }
                        }
                    ]
                };

            element.slick(_.extend(defaultOptions, options));
        },

        resizePopup: function () {
            var searchField = this.element,
                sideProportion = this.proportionSide,
                productResults = '[data-amsearch-js="products"]',
                productResultsBlock = this.searchForm.find(this.selectors.productResults),
                leftSideBlock = this.searchForm.find(this.selectors.leftSide),
                leftSideWidth,
                productsWidth,
                calculatedSizePopup,
                defaultSearchBlock = this.searchForm.width(),
                currentWidth = $(document).width(),
                rightsideContentBlock = this.searchForm.find(this.selectors.rightsideContent),
                leftsideSidebarBlock = this.searchForm.find(this.selectors.leftsideSidebar),
                searchAutocompleteBlock = this.searchForm.find(this.selectors.searchAutocomplete),
                inputWrapperBlock = this.searchForm.find(this.selectors.inputWrapper);

            calculatedSizePopup = leftsideSidebarBlock.length ? this.minSizePopup : this.slidersBlockSize;

            if (this.options.width >= this.sizePopupBreakpoint) {
                leftSideWidth = $(productResults).length ? this.options.width * sideProportion : searchField.outerWidth();
                productsWidth = this.options.width ? this.options.width * (1 - sideProportion) : searchField.outerWidth();
                productResultsBlock.addClass(this.classes.columns);
            } else {
                leftSideWidth = productResultsBlock.length ? this.options.width : searchField.outerWidth();
            }

            if (!this.options.isProductBlockEnabled) {
                leftSideWidth = this.options.width;
            }

            if (currentWidth >= this.mobileView) {
                inputWrapperBlock.css('width', '100%');
                searchAutocompleteBlock.css('width', defaultSearchBlock);
            }

            if (productResultsBlock.length) {
                leftSideBlock.css('width', leftSideWidth);
                productResultsBlock.css('width', productsWidth);
            } else if (rightsideContentBlock.length) {
                inputWrapperBlock.css('width', (this.options.isDynamicWidth && currentWidth >= this.mobileView)
                    ? calculatedSizePopup : '100%');
                searchAutocompleteBlock.css('width', currentWidth >= this.mobileView ? calculatedSizePopup : 'auto');
                leftSideBlock.css('width', calculatedSizePopup);
                rightsideContentBlock.css(leftsideSidebarBlock.length ? 'width' : 'max-width',
                    leftsideSidebarBlock.length ? this.minSizePopup - this.minLeftSidePopup : '100%');
                leftsideSidebarBlock.css('width', this.minLeftSidePopup);
            } else {
                leftSideBlock.css('width', leftSideWidth);
            }

            if (!leftSideBlock.children(this.selectors.itemContainer).length) {
                productResultsBlock.css('width', '100%');
            }
        },

        changePopupFlow: function () {
            if (this.options.width < this.sizePopupBreakpoint) {
                this.searchForm.addClass('-small');
            } else if (this.options.width >= this.minSizePopup) {
                this.searchForm.addClass('-large');
            }
        },

        hidePopup: function () {
            var defaultSearchBlock = this.showPopup(),
                currentWidth = $(document).width();

            this.autoComplete.hide();

            if (this.autoComplete.is(':hidden')) {
                this.searchLabel.removeClass('active');
            }

            this.searchForm.find('[data-amsearch-js="close"], [data-amsearch-js="loupe"]').hide();
            this.searchForm.find('.input-text').attr('placeholder', $.mage.__('Search entire store here...'));
            this.searchForm.removeClass('-opened');
            this.searchForm.removeClass(this.classes.searchContainer);

            if (currentWidth >= this.mobileView) {
                $(this.selectors.inputWrapper).css('width', '100%');
                this.searchForm.find(this.selectors.searchAutocomplete).css('width', defaultSearchBlock);
            }
        },

        outputNotFound: function () {
            var result = $('[data-amsearch-js="products"]').length,
                dropdown = $('[data-amsearch-js="results"]'),
                message = $.mage.__('Your search returned no products.'),
                leftSide = '[data-amsearch-js="left-side"]';

            if (!result) {
                $('<div class="amsearch-products -waste">' + message + '</div>').appendTo(dropdown);

                if (this.options.width >= this.sizePopupBreakpoint) {
                    $(leftSide).css('width', this.options.width * this.proportionSide);
                } else {
                    $(leftSide).css('width', this.options.width);
                }
            }
        },

        getEmptyRequest: function () {
            var defaultSearchBlock = this.showPopup(),
                currentWidth = $(document).width(),
                closeLoupeIcons = '[data-amsearch-js = "close"], [data-amsearch-js="loupe"]';

            this.searchForm.find(closeLoupeIcons).appendTo(this.searchForm.find(this.selectors.inputWrapper)).show();

            if (currentWidth >= this.mobileView) {
                this.searchForm.find(this.selectors.searchAutocomplete).css('width', defaultSearchBlock);
                $(this.selectors.inputWrapper).css('width', '100%');
            }

            this.searchForm.addClass(this.classes.searchContainer);
            this.searchForm.addClass('-opened').find('.input-text').attr('placeholder', $.mage.__('Enter Keyword or Item'));
            this.defineExistencePopup();
        },

        defineExistencePopup: function () {
            var leftSide = '[data-amsearch-js="left-side"]';

            if (!this.searchForm.find(leftSide).children().length) {
                this.searchForm.find(this.selectors.searchAutocomplete).hide();
            }
        },

        _onPropertyChange: function () {
            var self = this;
            if (this.timer != null) {
                clearTimeout(self.timer);
            }

            self.timer = setTimeout(function () {
                self._onPropertyChangeCallBack.call(this);
            }.bind(this), self.delay);
        },

        _onPropertyChangeCallBack: function () {
            var self = this,
                minChars = this.options.minChars ? this.options.minChars : this.options.minSearchLength,
                popupWidth = $(document).width() >= self.mobileView ? self.options.width : 'auto',
                value = this.element.val().trim();

            // check if value is empty
            this.submitBtn.disabled = (value.length === 0) || (value == null) || /^\s+$/.test(value);
            if (value.length >= parseInt(minChars, 10) && this.queryString != value) {
                this.showLoader();

                if (this.ajaxRequest) {
                    this.ajaxRequest.abort();
                }

                this.ajaxRequest = $.get(
                    self.options.url,
                    {q: value, uenc: self.options.currentUrlEncoded, form_key: $.mage.cookies.get('form_key')},
                    $.proxy(function (data) {
                        this.showPopup(data);
                        this.hideLoader();

                        if (self.options.isDynamicWidth == 1) {
                            $(this.selectors.inputWrapper).css('width', popupWidth);
                        }

                        this.searchForm.find(this.selectors.searchAutocomplete).css('width', popupWidth);

                        if (this.options.isProductBlockEnabled) {
                            this.outputNotFound();
                        }

                        if (data.redirect_url) {
                            this.redirectUrl = data.redirect_url;
                        } else {
                            this.redirectUrl = null;
                        }

                        if (!data.redirect_url && data.behavior) {
                            this.updateMultipleWishlist(data.behavior);
                            this.resetWishlistDropdown();
                        }

                        this.queryString = '';
                    }, this)
                );
                this.queryString = value;
            } else {
                this._resetResponseList(true);
                this.autoComplete.hide();
                this._updateAriaHasPopup(false);
                this.element.removeAttr('aria-activedescendant');
            }
        },

        updateMultipleWishlist: function (data) {
            var selectors = this.selectors;

            $(selectors.wishlistTemplates.popup).remove();
            $(selectors.wishlistTemplates.splitButton).remove();
            $(selectors.wishlistTemplates.form).replaceWith(data);
            $('body').off('click', selectors.postNewWishlist);
            uiRegistry.remove('multipleWishlist');
            $(selectors.pageWrapper).trigger('contentUpdated');
        },

        resetWishlistDropdown: function () {
            var self = this;

            $(this.selectors.productItem).mouseleave(function () {
                if ($(self.selectors.splitWishlistButton, $(this)).hasClass(self.classes.active)) {
                    $(self.selectors.splitWishlistButton, $(this)).dropdown().reset();
                }
            });
        },

        defineHideOrClear: function () {
            var self = this,
                mmItem = $('.ammenu-item');

            /* Mega Menu Hide Search Popop */
            if (mmItem.length) {
                mmItem.on('mouseover', function () {
                    self.hidePopup();
                });
            }

            this.searchForm.keydown(function (eventObject) {
                if (eventObject.which == 27) {
                    self.hidePopup();
                }
            });

            $('body').on('click', function (e) {
                var target = $(e.target);
                if (target.hasClass('amsearch-close')
                    || (target.is('[for="search"][data-role="minisearch-label"]') && self.element.is('[aria-haspopup="true"]'))
                ) {
                    self.element.val('').focus();
                    if (self.ajaxRequest) {
                        self.ajaxRequest.abort();
                    }
                    self.hideLoader();
                    self.hidePopup();
                    return false;
                }

                if (!target.is('#search, #search_autocomplete *, .xsearch-mini-form-widget *')) {
                    if (self.ajaxRequest) {
                        self.ajaxRequest.abort();
                    }
                    self.hideLoader();
                    self.hidePopup();
                }
            });
        },

        createSearchWrapper: function () {
            var wrapper = $('<div/>', {
                class: 'amsearch-wrapper-input',
                'data-amsearch-js': 'search-wrapper-input'
            }).appendTo($(this.searchForm.find('.control')));
            $(this.searchForm.find('.input-text')).appendTo($(this.searchForm.find('[data-amsearch-js="search-wrapper-input"]')));
        },

        createCloseIcon: function () {
            var closeIcon = $('<div/>', {
                class: 'amsearch-close',
                title: $.mage.__('Clear Field'),
                'data-amsearch-js': 'close'
            }).appendTo(this.searchForm.find('.control'));
        },

        createLoupeIcon: function () {
            var loupeIcon = $('<button/>', {
                class: 'amsearch-loupe',
                title: $.mage.__('Search'),
                type: 'submit',
                'data-amsearch-js': 'loupe'
            }).appendTo(this.searchForm.find('.control'));
        },

        createLoader: function () {
            var loader = $('<div/>', {
                'data-amsearch-js': "loader",
                class: 'amasty-xsearch-loader amasty-xsearch-hide'
            }).appendTo(this.searchForm.find(this.selectors.inputWrapper));
        },

        showLoader: function () {
            var $loader = this.searchForm.find(this.selectors.loader);
            $loader.removeClass('amasty-xsearch-hide');
            this.searchForm.find(this.submitBtn).addClass('amasty-xsearch-hide');
        },

        hideLoader: function () {
            var $loader = $(this.selectors.loader);

            $loader.addClass('amasty-xsearch-hide');
            $(this.submitBtn).removeClass('amasty-xsearch-hide');
        }
    });

    return $.mage.amXsearchFormMini;
});
