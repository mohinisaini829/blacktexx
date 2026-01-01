import Plugin from 'src/plugin-system/plugin.class';
import { tns } from 'tiny-slider';

export default class SolidAseContentSliderPlugin extends Plugin {
    init() {
        const slider = this.el;
        let config = { ...slider.dataset };

        // Get element id
        const elementId = config.elementId;
        delete config.elementId;

        // Set default config
        const defaultKeys = [
            'loop',
            'rewind',
            'center',
            'controls',
            'mouseDrag',
            'nav',
            'autoplay',
            'autoplayHoverpause',
        ];

        defaultKeys.forEach((key) => {
            if (!(key in config)) {
                config[key] = false;
            }
        });

        // Set config for animation variants
        if (config.animation.includes('slider')) {
            config.mode = 'carousel';
        } else if (config.animation.includes('gallery')) {
            config.mode = 'gallery';
        }

        if (config.animation.includes('horizontal')) {
            config.axis = 'horizontal';
        } else if (config.animation.includes('vertical')) {
            config.axis = 'vertical';
        }

        // Set config for items mode
        if (config.itemsMode === 'responsive-custom') {
            config.responsive = {
                0: {
                    items: config.itemsMobile,
                    slideBy: config.slideByMobile,
                },
                768: {
                    items: config.itemsTablet,
                    slideBy: config.slideByTablet,
                },
                1200: {
                    items: config.itemsDesktop,
                    slideBy: config.slideByDesktop,
                },
            };
        }

        delete config.itemsMode;
        delete config.itemsMobile;
        delete config.itemsTablet;
        delete config.itemsDesktop;
        delete config.slideByMobile;
        delete config.slideByTablet;
        delete config.slideByDesktop;

        // Set dependend config
        if (config.mode === 'gallery') {
            delete config.slideBy;
            delete config.center;
        }

        if (config.controls) {
            config.controlsContainer =
                '.solid-ase-content-slider-' +
                elementId +
                ' .solid-ase-content-slider-controls-container';
        }

        if (config.nav) {
            config.navPosition = 'bottom';
        }

        // Set automatic responsive config
        if (!config.responsive && config.items > 1) {
            switch (config.items) {
                case '2':
                    config.responsive = {
                        0: {
                            items: 1,
                        },
                        992: {
                            items: 2,
                        },
                    };

                    if (config.slideBy > 1) {
                        config.responsive['0'].slideBy = 1;
                        config.responsive['992'].slideBy = 2;
                    }

                    break;

                case '3':
                    config.responsive = {
                        0: {
                            items: 1,
                        },
                        992: {
                            items: 2,
                        },
                        1200: {
                            items: 3,
                        },
                    };

                    if (config.slideBy > 1) {
                        config.responsive['0'].slideBy = 1;
                        config.responsive['992'].slideBy = 2;
                    }

                    if (config.slideBy > 2) {
                        config.responsive['1200'].slideBy = 3;
                    }

                    break;

                case '4':
                    config.responsive = {
                        0: {
                            items: 1,
                        },
                        992: {
                            items: 2,
                        },
                        1200: {
                            items: 3,
                        },
                        1400: {
                            items: 4,
                        },
                    };

                    if (config.slideBy > 1) {
                        config.responsive['0'].slideBy = 1;
                        config.responsive['992'].slideBy = 2;
                    }

                    if (config.slideBy > 2) {
                        config.responsive['1200'].slideBy = 3;
                    }

                    if (config.slideBy > 3) {
                        config.responsive['1400'].slideBy = 4;
                    }

                    break;

                case '5':
                    config.responsive = {
                        0: {
                            items: 1,
                        },
                        992: {
                            items: 2,
                        },
                        1200: {
                            items: 4,
                        },
                        1400: {
                            items: 5,
                        },
                    };

                    if (config.slideBy > 1) {
                        config.responsive['0'].slideBy = 1;
                        config.responsive['992'].slideBy = 2;
                    }

                    if (config.slideBy > 3) {
                        config.responsive['1200'].slideBy = 4;
                    }

                    if (config.slideBy > 4) {
                        config.responsive['1400'].slideBy = 5;
                    }

                    break;

                case '6':
                    config.responsive = {
                        0: {
                            items: 1,
                        },
                        992: {
                            items: 2,
                        },
                        1200: {
                            items: 4,
                        },
                        1400: {
                            items: 6,
                        },
                    };

                    if (config.slideBy > 1) {
                        config.responsive['0'].slideBy = 1;
                        config.responsive['992'].slideBy = 2;
                    }

                    if (config.slideBy > 3) {
                        config.responsive['1200'].slideBy = 4;
                    }

                    if (config.slideBy > 5) {
                        config.responsive['1400'].slideBy = 6;
                    }

                    break;

                case '7':
                    config.responsive = {
                        0: {
                            items: 1,
                        },
                        768: {
                            items: 2,
                        },
                        992: {
                            items: 3,
                        },
                        1200: {
                            items: 5,
                        },
                        1400: {
                            items: 7,
                        },
                    };

                    if (config.slideBy > 1) {
                        config.responsive['0'].slideBy = 1;
                    }

                    if (config.slideBy > 2) {
                        config.responsive['768'].slideBy = 2;
                    }

                    if (config.slideBy > 3) {
                        config.responsive['992'].slideBy = 3;
                    }

                    if (config.slideBy > 5) {
                        config.responsive['1200'].slideBy = 5;
                    }

                    if (config.slideBy > 7) {
                        config.responsive['1400'].slideBy = 7;
                    }

                    break;

                case '8':
                    config.responsive = {
                        0: {
                            items: 2,
                        },
                        768: {
                            items: 3,
                        },
                        992: {
                            items: 4,
                        },
                        1200: {
                            items: 6,
                        },
                        1400: {
                            items: 8,
                        },
                    };

                    if (config.slideBy > 2) {
                        config.responsive['0'].slideBy = 2;
                    }

                    if (config.slideBy > 3) {
                        config.responsive['768'].slideBy = 3;
                    }

                    if (config.slideBy > 4) {
                        config.responsive['992'].slideBy = 4;
                    }

                    if (config.slideBy > 6) {
                        config.responsive['1200'].slideBy = 6;
                    }

                    if (config.slideBy > 8) {
                        config.responsive['1400'].slideBy = 8;
                    }

                    break;

                case '9':
                    config.responsive = {
                        0: {
                            items: 2,
                        },
                        768: {
                            items: 3,
                        },
                        992: {
                            items: 4,
                        },
                        1200: {
                            items: 7,
                        },
                        1400: {
                            items: 9,
                        },
                    };

                    if (config.slideBy > 2) {
                        config.responsive['0'].slideBy = 2;
                    }

                    if (config.slideBy > 3) {
                        config.responsive['768'].slideBy = 3;
                    }

                    if (config.slideBy > 4) {
                        config.responsive['992'].slideBy = 4;
                    }

                    if (config.slideBy > 7) {
                        config.responsive['1200'].slideBy = 7;
                    }

                    if (config.slideBy > 9) {
                        config.responsive['1400'].slideBy = 9;
                    }

                    break;

                case '10':
                    config.responsive = {
                        0: {
                            items: 2,
                        },
                        768: {
                            items: 4,
                        },
                        992: {
                            items: 6,
                        },
                        1200: {
                            items: 8,
                        },
                        1400: {
                            items: 10,
                        },
                    };

                    if (config.slideBy > 2) {
                        config.responsive['0'].slideBy = 2;
                    }

                    if (config.slideBy > 4) {
                        config.responsive['768'].slideBy = 4;
                    }

                    if (config.slideBy > 6) {
                        config.responsive['992'].slideBy = 6;
                    }

                    if (config.slideBy > 8) {
                        config.responsive['1200'].slideBy = 8;
                    }

                    if (config.slideBy > 10) {
                        config.responsive['1400'].slideBy = 10;
                    }

                    break;
            }
        }

        config = {
            container:
                '.solid-ase-content-slider-' +
                elementId +
                ' .solid-ase-content-slider',
            autoplayButtonOutput: false,
            navAsThumbnails: false,
            autoHeight: false,
            ...config,
        };

        const onInit = (sliderInfo) => {
            window.PluginManager.initializePlugins();
            this._initAccessibilityTweaks(sliderInfo, config, this.el.parentNode);
        };

        this._slider = tns({
            ...config,
            onInit,
        });

        this.el.classList.remove('is-loading');
    }

    /**
     * Initializes some accessibility improvements for the tiny-slider package.
     *
     * @param {Object} sliderInfo
     * @param {HTMLElement} wrapperEl
     * @private
     */
    _initAccessibilityTweaks(sliderInfo, config, wrapperEl) {
        const sliderItems = sliderInfo.slideItems;

        if (sliderInfo.controlsContainer) {
            // Remove controls div container from tab index for better accessibility.
            sliderInfo.controlsContainer.setAttribute('tabindex', '-1');
        }

        for (let index = 0; index < sliderItems.length; index++) {
            const item = sliderItems.item(index);

            if (item.classList.contains('tns-slide-cloned')
                || item.classList.contains('solid-ase-content-slider-slide-clone')) {
                const selectableElements = item.querySelectorAll('a, button');

                // Hide selectable elements within cloned elements from screen readers.
                for (const selectableEl of selectableElements) {
                    selectableEl.setAttribute('tabindex', '-1');
                }
            } else {
                // Tracking the focus within slider elements to keep them in view when navigating via keyboard.
                item.addEventListener('keyup', (event) => {
                    if (event.key !== 'Tab') {
                        return;
                    }

                    // Stop autoplay if an element gets focus via keyboard navigation.
                    if (config.autoplay) {
                        this._slider.pause();
                    }
                });
            }
        }
    }
}
