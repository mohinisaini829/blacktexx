import Iterator from 'src/helper/iterator.helper';

export default class zenitNotificationBarSlider extends window.PluginBaseClass {
    static options = {
        /**
         * CSS selector for the collapse content element
         *
         * @type {String}
         */
        collapseContent: '.notification-bar-collapse-content',

        /**
         * ID selector for the collapse toggler target
         *
         * @type {String}
         */
        collapseTarget: '#notification-bar-collapse',

        /**
         * text slider items selector
         */
        sliderItems: '.text-slider-items',

        /**
         * text slider item selector
         */
        sliderItem: '.text-slider-item',

        /**
         * text slider item selector
         */
        sliderItemEntry: '.item-entry',

        /**
         * animation in class
         */
        animationIn: 'js-animate-in',

        /**
         * animation out class
         */
        animationOut: 'js-animate-out',

        /**
         * helperclass to get non-collapsed height of a collapsed element
         */
        hidden: 'js-hidden',

        /**
         * Visible Class for Bootstrap collapse
         *
         * @type {String}
         */
        bsVisibleClass: 'show',

        /**
         * slider interval
         */
        interval: 1000,
    };

    init() {
        const opts = this.options;

        this._sliderItems = this.el.querySelector(opts.sliderItems);
        this._sliderItem = this.el.querySelectorAll(opts.sliderItem);
        this._sliderItemEntry = this.el.querySelectorAll(opts.sliderItemEntry);
        this._collapseContent = document.querySelector(opts.collapseContent);
        this._counter = 0;
        this._sliderSetTimeout = null;
        this._sliderSetTimeoutResize = null;
        this._sliderItemPrev = null;
        this._sliderFirstLoad = false;
        this._sliderDestroyed = false;

        if (this._sliderItem.length > 1) {
            this._registerEvents();
        }
    }

    _registerEvents() {
        const opts = this.options;
        const shownEvent = 'shown.bs.collapse';
        const hiddenEvent = 'hidden.bs.collapse';
        const collapseTarget = document.querySelector(opts.collapseTarget);

        document.fonts.ready.then(() => {
            this._getHolderHeight();
            this._loadSlide();
        });

        collapseTarget.addEventListener(shownEvent, () => {
            this._rebuildSlider();
        });

        collapseTarget.addEventListener(hiddenEvent, () => {
            this._destroySlider();
        });

        window.addEventListener('resize', this._resizeHandler.bind(this));
        window.addEventListener('orientationchange', this._resizeHandler.bind(this));

        this.$emitter.publish('registerEvents');
    }

    _getHolderHeight() {
        const opts = this.options;

        const collapseContent = document.querySelector(opts.collapseContent);
        const itemsHeight = [];

        // add helperclass to get non-collapsed height of a collapsed element
        collapseContent.classList.add(opts.hidden);

        for (let i = 0; i < this._sliderItemEntry.length; i++) {
            itemsHeight.push(this._sliderItemEntry[i].clientHeight);
        }

        // remove helperclass
        collapseContent.classList.remove(opts.hidden);

        this.el.style.height = Math.max.apply(null, itemsHeight) + 'px';

        this.$emitter.publish('onGetHolderHeight');
    }

    _resizeHandler() {
        if (!this._collapseContent.classList.contains(this.options.bsVisibleClass)) return;

        clearTimeout(this._sliderSetTimeout);
        clearTimeout(this._sliderSetTimeoutResize);
        this._sliderSetTimeoutResize = setTimeout(
            this._rebuildSlider.bind(this),
            50
        );
    }

    _loadSlide() {
        const opts = this.options;

        // ... checks if collapse is visible
        if (
            this._sliderDestroyed ||
            !this._collapseContent.classList.contains(
                this.options.bsVisibleClass
            )
        )
            return;

        clearTimeout(this._sliderSetTimeout);
        this._sliderSetTimeout = setTimeout(
            this._animateSlide.bind(this),
            this._sliderFirstLoad ? opts.interval : 100
        );

        this.$emitter.publish('onLoadSlide');
    }

    _animateSlide() {
        const opts = this.options;

        this._sliderFirstLoad = true;

        if (this._sliderItemPrev !== null) {
            this._sliderItemPrev.classList.add(opts.animationOut);
        }

        const sliderItemNext = this._sliderItems.children[this._counter];
        sliderItemNext.classList.remove(opts.animationOut);
        sliderItemNext.classList.add(opts.animationIn);

        this._sliderItemPrev = sliderItemNext;

        if (this._counter === this._sliderItem.length - 1) {
            this._counter = 0;
        } else {
            this._counter++;
        }

        this._loadSlide();

        this.$emitter.publish('onAnimateSlide');
    }

    _rebuildSlider() {
        this._sliderDestroyed = false;
        this._getHolderHeight();
        this._animateSlide();

        this.$emitter.publish('onRebuildSlider');
    }

    _destroySlider() {
        const opts = this.options;

        clearTimeout(this._sliderSetTimeout);
        clearTimeout(this._sliderSetTimeoutResize);

        // ... remove animation classes
        Iterator.iterate(this._sliderItem, (element) => {
            element.classList.remove(opts.animationOut, opts.animationIn);
        });

        // ... set initial values
        this._counter = 0;
        this._sliderFirstLoad = false;
        this._sliderDestroyed = true;

        this.$emitter.publish('onDestroySlider');
    }
}
