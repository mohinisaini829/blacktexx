import CookieStorageHelper from 'src/helper/storage/cookie-storage.helper';
import { COOKIE_CONFIGURATION_UPDATE } from 'src/plugin/cookie/cookie-configuration.plugin';

export default class zenitNotificationBar extends window.PluginBaseClass {
    static options = {
        /**
         * ID of banner
         *
         */
        id: null,

        /**
         * ID selector for the toggler target
         *
         * @type {String}
         */
        collapseTarget: '#notification-bar-collapse',

        /**
         * CSS selector for the collapse header element
         *
         * @type {String}
         */
        collapseControl: '.notification-bar-collapse-control',

        /**
         * CSS selector for the collapse content element
         *
         * @type {String}
         */
        collapseContent: '.notification-bar-collapse-content',

        /**
         * toggles button
         *
         * @type {boolean}
         */
        btnCloseBanner: false,

        /**
         * CSS selector for the button element
         *
         * @type {String}
         */
        btnClass: '.notification-btn',

        /**
         * Visible Class
         *
         * @type {String}
         */
        visibleClass: 'is-visible',

        /**
         * Visible Class for Bootstrap collapse
         *
         * @type {String}
         */
        bsVisibleClass: 'show',

        /**
         * Indicates if notification banner is collapsable
         *
         * @type {boolean}
         */
        collapsable: true,

        /**
         * Indicates if notification banner is expandable
         *
         * @type {boolean}
         */
        expandable: true,

        /**
         * Name of the cookie
         *
         * @type {string}
         */
        cookieName: 'zen-notification-bar',

        /**
         * Expiration time of the cookie
         *
         * @type {number}
         */
        expiration: 365,
    };

    init() {
        const opts = this.options;

        this._collapseTarget = this.el.querySelector(opts.collapseTarget);
        this._collapseControl = this.el.querySelector(opts.collapseControl);
        this._collapseContent = this.el.querySelector(opts.collapseContent);
        this._saleButton = this.el.querySelector(opts.btnClass);
        this.cookieAllowed = !CookieStorageHelper.getItem(opts.cookieName) === false;

        this._registerEvents();
        this._handleOpenState();
    }

    _registerEvents() {
        const hiddenEvent = 'hide.bs.collapse';
        const shownEvent = 'show.bs.collapse';

        this._handleCookieChangeEvent();

        this._collapseTarget.addEventListener(hiddenEvent, () => {
            if (this.cookieAllowed) {
                this._setHiddenCookie();
            }
            this._setControlsState('hidden');
        });

        this._collapseTarget.addEventListener(shownEvent, () => {
            if (this.cookieAllowed) {
                this._setVisibleCookie();
            }
            this._setControlsState('visible');
        });

        if (this._saleButton) {
            this._saleButton.addEventListener('click', this._onButtonClick.bind(this));
        }

        this.$emitter.publish('registerEvents');
    }

    _handleCookieChangeEvent() {
        document.$emitter.subscribe(COOKIE_CONFIGURATION_UPDATE, this._onCookieChange.bind(this));
    }

    _onCookieChange(updatedCookies) {
        const opts = this.options;
        this.cookieAllowed = updatedCookies.detail[opts.cookieName] === true;

        if (!this.cookieAllowed) {
            CookieStorageHelper.removeItem(opts.cookieName);
        } else {
            this._saveOpenState();
        }

        this.$emitter.publish('onCookieChange');
    }

    _saveOpenState() {
        if (this._collapseContent.classList.contains(this.options.bsVisibleClass)) {
            this._setVisibleCookie();
        } else {
            this._setHiddenCookie();
        }
    }

    _handleOpenState() {
        const opts = this.options;
        const cookie = CookieStorageHelper.getItem(opts.cookieName);

        // ... cookie not set
        if (cookie !== 'hidden-' + opts.id && cookie !== 'visible-' + opts.id) {
            // ... collapsable and expanded
            if (opts.collapsable || (!opts.collapsable && !opts.expandable)) {
                if (this.cookieAllowed) {
                    this._setVisibleCookie();
                }
                this._setInitialState('visible');

                // ... expandable but not collapsable
            } else {
                if (this.cookieAllowed) {
                    this._setHiddenCookie();
                }
                this._setInitialState('hidden');
            }
        } else if (cookie === 'visible-' + opts.id) {
            // prevent using bootstrap API methods to skip transition
            this._setInitialState('visible');
        } else if (cookie === 'hidden-' + opts.id) {
            // prevent using bootstrap API methods to skip transition
            this._setInitialState('hidden');
        }

        this.$emitter.publish('onHandleOpenState');
    }

    _onButtonClick() {
        const opts = this.options;

        if (opts.btnCloseBanner && !this._saleButton.classList.contains('is--disabled')) {
            if (this.cookieAllowed) {
                this._setHiddenCookie();
            }
        }

        this.$emitter.publish('onButtonClick');
    }

    /**
     * add initial state without animation
     */
    _setInitialState(state) {
        if (state === 'visible') {
            this._collapseContent.classList.add(this.options.bsVisibleClass);
            this._setControlsState('visible');

            this.$emitter.publish('onAddInitialVisibleClasses');
        } else if (state === 'hidden') {
            this._collapseContent.classList.remove(this.options.bsVisibleClass);
            this._setControlsState('hidden');

            this.$emitter.publish('onAddInitialHiddenClasses');
        }
    }

    _setControlsState(state) {
        const opts = this.options;

        if (state === 'visible') {
            if (opts.collapsable || opts.expandable) {
                this._collapseControl.classList.add(opts.visibleClass);
                this._collapseControl.setAttribute('aria-expanded', 'true');
            }
        } else if (state === 'hidden') {
            if (opts.collapsable || opts.expandable) {
                this._collapseControl.classList.remove(opts.visibleClass);
                this._collapseControl.setAttribute('aria-expanded', 'false');
            }

            if (!opts.expandable) {
                this._collapseControl.setAttribute('aria-hidden', 'true');
                this._collapseControl.setAttribute('tabindex', '-1');
            }
        }
    }

    _setVisibleCookie() {
        if (typeof this.options.expiration === 'number') {
            CookieStorageHelper.setItem(
                'zen-notification-bar',
                'visible-' + this.options.id,
                this.options.expiration
            );
        } else {
            CookieStorageHelper.setItem(
                'zen-notification-bar',
                'visible-' + this.options.id
            );
        }
    }

    _setHiddenCookie() {
        if (typeof this.options.expiration === 'number') {
            CookieStorageHelper.setItem(
                'zen-notification-bar',
                'hidden-' + this.options.id,
                this.options.expiration
            );
        } else {
            CookieStorageHelper.setItem(
                'zen-notification-bar',
                'hidden-' + this.options.id
            );
        }
    }
}
