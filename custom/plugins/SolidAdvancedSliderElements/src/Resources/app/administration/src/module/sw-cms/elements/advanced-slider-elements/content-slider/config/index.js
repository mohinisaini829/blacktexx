import template from './sw-cms-el-config-solid-ase-content-slider.html.twig';
import './sw-cms-el-config-solid-ase-content-slider.scss';

const { Component, Mixin, Context, Utils, Data } = Shopware;
const { Criteria, EntityCollection } = Data;

Component.register('sw-cms-el-config-solid-ase-content-slider', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    inject: [
        'repositoryFactory'
    ],

    computed: {
        tabItems() {
            return [
                {
                    name: 'slides',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.slides.label'),
                },
                {
                    name: 'general',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.general.label'),
                },
                {
                    name: 'content',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.content.label'),
                },
                {
                    name: 'navigation',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.navigation.label'),
                },
                {
                    name: 'autoplay',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.autoplay.label'),
                },
                {
                    name: 'custom',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.custom.label'),
                },
            ];
        },

        slideTabItems() {
            return [
                {
                    name: 'content',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.slides.content.label'),
                },
                {
                    name: 'background',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.slides.background.label'),
                },
                {
                    name: 'link',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.slides.link.label'),
                },
                {
                    name: 'publishing',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.slides.publishing.label'),
                },
            ];
        },

        slideContentTypeOptions() {
            return [
                {
                    value: 'default',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.slides.content.fields.content-type.options.default.label'),
                },
                {
                    value: 'custom',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.slides.content.fields.content-type.options.custom.label'),
                },
            ];
        },

        slideButtonLinkTypeOptions() {
            return [
                {
                    value: 'internal',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.slides.content.fields.button-link.type.options.internal.label'),
                },
                {
                    value: 'external',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.slides.content.fields.button-link.type.options.external.label'),
                },
            ];
        },

        slideButtonLinkEntityOptions() {
            return [
                {
                    value: 'category',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.slides.content.fields.button-link.entity.options.category.label'),
                },
                {
                    value: 'landing-page',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.slides.content.fields.button-link.entity.options.landingPage.label'),
                },
                {
                    value: 'product',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.slides.content.fields.button-link.entity.options.product.label'),
                },
            ];
        },

        slideBackgroundSizingModeOptions() {
            return [
                {
                    value: 'contain',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.slides.background.fields.background-sizing-mode.options.contain.label'),
                },
                {
                    value: 'cover',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.slides.background.fields.background-sizing-mode.options.cover.label'),
                },
            ];
        },

        slideBackgroundPositionOptions() {
            return [
                {
                    value: 'center',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.slides.background.fields.background-position.options.center.label'),
                },
                {
                    value: 'top',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.slides.background.fields.background-position.options.top.label'),
                },
                {
                    value: 'right',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.slides.background.fields.background-position.options.right.label'),
                },
                {
                    value: 'bottom',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.slides.background.fields.background-position.options.bottom.label'),
                },
                {
                    value: 'left',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.slides.background.fields.background-position.options.left.label'),
                },
            ];
        },

        slideBackgroundAnimationOptions() {
            return [
                {
                    value: 'none',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.slides.background.fields.background-animation.options.none.label'),
                },
                {
                    value: 'zoom',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.slides.background.fields.background-animation.options.zoom.label'),
                },
                {
                    value: 'move',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.slides.background.fields.background-animation.options.move.label'),
                },
            ];
        },

        slideLinkTypeOptions() {
            return [
                {
                    value: 'internal',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.slides.link.fields.type.options.internal.label'),
                },
                {
                    value: 'external',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.slides.link.fields.type.options.external.label'),
                },
            ];
        },

        slideLinkEntityOptions() {
            return [
                {
                    value: 'category',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.slides.link.fields.entity.options.category.label'),
                },
                {
                    value: 'landing-page',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.slides.link.fields.entity.options.landingPage.label'),
                },
                {
                    value: 'product',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.slides.link.fields.entity.options.product.label'),
                },
            ];
        },

        slidePublishingTypeOptions() {
            return [
                {
                    value: 'instant',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.slides.publishing.fields.type.options.instant.label'),
                },
                {
                    value: 'scheduled',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.slides.publishing.fields.type.options.scheduled.label'),
                },
            ];
        },

        settingsAnimationOptions() {
            return [
                {
                    value: 'slider-horizontal-ease-in-out-sine',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.general.fields.animation.options.slider-horizontal-ease-in-out-sine.label'),
                },
                {
                    value: 'slider-horizontal-ease-out-sine',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.general.fields.animation.options.slider-horizontal-ease-out-sine.label'),
                },
                {
                    value: 'slider-horizontal-ease-in-out-quad',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.general.fields.animation.options.slider-horizontal-ease-in-out-quad.label'),
                },
                {
                    value: 'slider-horizontal-ease-out-quad',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.general.fields.animation.options.slider-horizontal-ease-out-quad.label'),
                },
                {
                    value: 'slider-horizontal-ease-in-out-cubic',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.general.fields.animation.options.slider-horizontal-ease-in-out-cubic.label'),
                },
                {
                    value: 'slider-horizontal-ease-out-cubic',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.general.fields.animation.options.slider-horizontal-ease-out-cubic.label'),
                },
                {
                    value: 'slider-horizontal-ease-in-out-quart',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.general.fields.animation.options.slider-horizontal-ease-in-out-quart.label'),
                },
                {
                    value: 'slider-horizontal-ease-out-quart',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.general.fields.animation.options.slider-horizontal-ease-out-quart.label'),
                },
                {
                    value: 'slider-horizontal-ease-in-out-quint',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.general.fields.animation.options.slider-horizontal-ease-in-out-quint.label'),
                },
                {
                    value: 'slider-horizontal-ease-out-quint',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.general.fields.animation.options.slider-horizontal-ease-out-quint.label'),
                },
                {
                    value: 'slider-horizontal-ease-in-out-expo',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.general.fields.animation.options.slider-horizontal-ease-in-out-expo.label'),
                },
                {
                    value: 'slider-horizontal-ease-out-expo',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.general.fields.animation.options.slider-horizontal-ease-out-expo.label'),
                },
                {
                    value: 'slider-horizontal-ease-in-out-circ',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.general.fields.animation.options.slider-horizontal-ease-in-out-circ.label'),
                },
                {
                    value: 'slider-horizontal-ease-out-circ',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.general.fields.animation.options.slider-horizontal-ease-out-circ.label'),
                },
                {
                    value: 'slider-horizontal-ease-in-out-back',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.general.fields.animation.options.slider-horizontal-ease-in-out-back.label'),
                },
                {
                    value: 'slider-horizontal-ease-out-back',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.general.fields.animation.options.slider-horizontal-ease-out-back.label'),
                },
                {
                    value: 'gallery-fade',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.general.fields.animation.options.gallery-fade.label'),
                },
            ];
        },

        settingsItemsMode() {
            return [
                {
                    value: 'responsive-automatic',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.general.fields.itemsMode.options.responsive-automatic.label'),
                },
                {
                    value: 'responsive-custom',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.general.fields.itemsMode.options.responsive-custom.label'),
                },
            ];
        },

        settingsSizingMode() {
            return [
                {
                    value: 'responsive-min-height',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.general.fields.sizing-mode.options.responsive-min-height.label')
                },
                {
                    value: 'min-aspect-ratio',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.general.fields.sizing-mode.options.min-aspect-ratio.label'),
                },
            ];
        },

        settingsLayoutVariant() {
            return [
                {
                    value: 'overlay-center',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.content.fields.layout-variant.options.overlay-center.label')
                },
                {
                    value: 'overlay-center-right',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.content.fields.layout-variant.options.overlay-center-right.label')
                },
                {
                    value: 'overlay-center-left',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.content.fields.layout-variant.options.overlay-center-left.label')
                },
                {
                    value: 'overlay-top-right',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.content.fields.layout-variant.options.overlay-top-right.label')
                },
                {
                    value: 'overlay-top-left',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.content.fields.layout-variant.options.overlay-top-left.label')
                },
                {
                    value: 'overlay-bottom-right',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.content.fields.layout-variant.options.overlay-bottom-right.label')
                },
                {
                    value: 'overlay-bottom-left',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.content.fields.layout-variant.options.overlay-bottom-left.label')
                },
                {
                    value: 'gradient-top',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.content.fields.layout-variant.options.gradient-top.label')
                },
                {
                    value: 'gradient-bottom',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.content.fields.layout-variant.options.gradient-bottom.label')
                },
                {
                    value: 'boxed-center',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.content.fields.layout-variant.options.boxed-center.label')
                },
                {
                    value: 'boxed-center-right',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.content.fields.layout-variant.options.boxed-center-right.label')
                },
                {
                    value: 'boxed-center-left',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.content.fields.layout-variant.options.boxed-center-left.label')
                },
                {
                    value: 'boxed-top-right',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.content.fields.layout-variant.options.boxed-top-right.label')
                },
                {
                    value: 'boxed-top-left',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.content.fields.layout-variant.options.boxed-top-left.label')
                },
                {
                    value: 'boxed-bottom-right',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.content.fields.layout-variant.options.boxed-bottom-right.label')
                },
                {
                    value: 'boxed-bottom-left',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.content.fields.layout-variant.options.boxed-bottom-left.label')
                }
            ];
        },

        settingsContentAnimation() {
            return [
                {
                    value: 'none',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.content.fields.content-animation.options.none.label'),
                },
                {
                    value: 'fade-in',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.content.fields.content-animation.options.fade-in.label'),
                },
                {
                    value: 'fade-in-up',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.content.fields.content-animation.options.fade-in-up.label'),
                },
                {
                    value: 'fade-in-right',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.content.fields.content-animation.options.fade-in-right.label'),
                },
                {
                    value: 'fade-in-down',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.content.fields.content-animation.options.fade-in-down.label'),
                },
                {
                    value: 'fade-in-left',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.content.fields.content-animation.options.fade-in-left.label'),
                },
            ];
        },

        settingsButtonVariantOptions() {
            return [
                {
                    value: 'primary',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.content.fields.button-variant.options.primary.label'),
                },
                {
                    value: 'secondary',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.content.fields.button-variant.options.secondary.label'),
                },
                {
                    value: 'outline-primary',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.content.fields.button-variant.options.outline-primary.label'),
                },
                {
                    value: 'outline-secondary',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.content.fields.button-variant.options.outline-secondary.label'),
                },
            ];
        },

        settingsControlsVariantOptions() {
            return [
                {
                    value: 'icon',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.navigation.fields.controls-variant.options.icon.label'),
                },
                {
                    value: 'round',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.navigation.fields.controls-variant.options.round.label'),
                },
                {
                    value: 'rounded',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.navigation.fields.controls-variant.options.rounded.label'),
                },
                {
                    value: 'square',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.navigation.fields.controls-variant.options.square.label'),
                },
                {
                    value: 'pill',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.navigation.fields.controls-variant.options.pill.label'),
                },
                {
                    value: 'block',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.navigation.fields.controls-variant.options.block.label'),
                },
            ];
        },

        settingsControlsIconVariantOptions() {
            return [
                {
                    value: 'caret',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.navigation.fields.controls-icon-variant.options.caret.label'),
                },
                {
                    value: 'caret-circle',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.navigation.fields.controls-icon-variant.options.caret-circle.label'),
                },
                {
                    value: 'arrow',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.navigation.fields.controls-icon-variant.options.arrow.label'),
                },
            ];
        },

        settingsControlsPositionOptions() {
            return [
                {
                    value: 'horizontal-inside-center-edges',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.navigation.fields.controls-position.options.horizontal-inside-center-edges.label'),
                },
                {
                    value: 'horizontal-outside-center-edges',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.navigation.fields.controls-position.options.horizontal-outside-center-edges.label'),
                },
            ];
        },

        settingsNavVariantOptions() {
            return [
                {
                    value: 'dots-opacity',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.navigation.fields.nav-variant.options.dots-opacity.label'),
                },
                {
                    value: 'dots-fill',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.navigation.fields.nav-variant.options.dots-fill.label'),
                },
                {
                    value: 'dots-size',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.navigation.fields.nav-variant.options.dots-size.label'),
                },
            ];
        },

        settingsNavSizeOptions() {
            return [
                {
                    value: 'small',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.navigation.fields.nav-size.options.small.label'),
                },
                {
                    value: 'medium',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.navigation.fields.nav-size.options.medium.label'),
                },
                {
                    value: 'large',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.navigation.fields.nav-size.options.large.label'),
                },
            ];
        },

        settingsNavPositionOptions() {
            return [
                {
                    value: 'vertical-center-right',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.navigation.fields.nav-position.options.vertical-center-right.label'),
                },
                {
                    value: 'vertical-center-left',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.navigation.fields.nav-position.options.vertical-center-left.label'),
                },
                {
                    value: 'horizontal-bottom-center',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.navigation.fields.nav-position.options.horizontal-bottom-center.label'),
                },
                {
                    value: 'horizontal-bottom-right',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.navigation.fields.nav-position.options.horizontal-bottom-right.label'),
                },
                {
                    value: 'horizontal-bottom-left',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.navigation.fields.nav-position.options.horizontal-bottom-left.label'),
                },
            ];
        },

        settingsAutoplayDirection() {
            return [
                {
                    value: 'forward',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.autoplay.fields.autoplay-direction.options.forward.label'),
                },
                {
                    value: 'backward',
                    label: this.$t('sw-cms.elements.solid-ase.content-slider.config.autoplay.fields.autoplay-direction.options.backward.label'),
                },
            ];
        },

        mediaRepository() {
            return this.repositoryFactory.create('media');
        },

        categoryRepository() {
            return this.repositoryFactory.create('category');
        },

        categoryCriteria() {
            const criteria = new Criteria(1, null);
            criteria.addFilter(Criteria.equals('type', 'page'));

            return criteria;
        },

        productCriteria() {
            const criteria = new Criteria(1, 25);
            criteria.addAssociation('options.group');

            return criteria;
        },

        activeSlides() {
            return this.element.config.slides.value.filter((slide) => {
                return slide.active;
            });
        },

        adminWorkerEnabled() {
            return Context.app.config.adminWorker.enableAdminWorker;
        },
    },

    watch: {
        /**
         * Hotfix for changes introduced in NEXT-16511, part of 6.4.7.0.
         * Array values will be unnecessarily merged with the old translated value on initElementConfig(), see
         * https://github.com/shopware/shopware/commit/5089ac6eef6616a184424e66940219bdae1bbee8#diff-e48327c4768e14cf98f79b73cc2f3029a6bc7ecc610c59f7c12f1ce94fa9b238L53
         */
        'element.config.slides.value': {
            deep: true,
            handler() {
                this.element.translated.config.slides.value = this.element.config.slides.value;
            },
        },
    },

    data() {
        return {
            visibilityObserver: null,
            currentTabName: null,
            currentSlideTabNames: [],
            slideBackgroundMediaPreviews: [],
            controlsCustomImagePreviousPreview: null,
            controlsCustomImageNextPreview: null,
            slideButtonLinkCategoryCollections: {},
            slideLinkCategoryCollections: {},
            visibilityObserver: null,
        };
    },

    created() {
        this.createdComponent();
    },

    mounted() {
        this.getMediaEntityPreviews();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('solid-ase-content-slider');
            this.currentTabName = 'slides';
            this.createSlideCurrentTabNames();
            this.createSlideButtonLinkCategoryCollections();
            this.createSlideLinkCategoryCollections();
        },

        createSlideCurrentTabNames() {
            this.element.config.slides.value.forEach((slide) => {
                if (!this.currentSlideTabNames[slide.id]) {
                    this.currentSlideTabNames[slide.id] = 'content';
                }
            });
        },

        async getMediaEntityPreviews() {
            if (this.element.config.slides.value) {
                this.slideBackgroundMediaPreviews = [];

                for await (const slide of this.element.config.slides.value) {
                    if (slide.backgroundMedia) {
                        const mediaEntity = await this.mediaRepository.get(
                            slide.backgroundMedia,
                            Context.api
                        );

                        this.slideBackgroundMediaPreviews.push(mediaEntity);
                    } else {
                        this.slideBackgroundMediaPreviews.push(null);
                    }
                }
            }

            if (this.element.config.settings.value.controlsCustomImagePrevious) {
                const mediaEntity = await this.mediaRepository.get(
                    this.element.config.settings.value
                        .controlsCustomImagePrevious,
                    Context.api
                );

                this.controlsCustomImagePreviousPreview = mediaEntity;
            }

            if (this.element.config.settings.value.controlsCustomImageNext) {
                const mediaEntity = await this.mediaRepository.get(
                    this.element.config.settings.value.controlsCustomImageNext,
                    Context.api
                );

                this.controlsCustomImageNextPreview = mediaEntity;
            }
        },

        async refreshMediaEntityPreviews(mediaEntityId) {
            const mediaEntity = await this.mediaRepository.get(
                mediaEntityId,
                Context.api
            );

            if (this.element.config.slides.value) {
                this.element.config.slides.value.forEach((slide, index) => {
                    if (slide.backgroundMedia === mediaEntityId) {
                        this.slideBackgroundMediaPreviews[index] = mediaEntity;
                    }
                });
            }

            if (this.element.config.settings.value.controlsCustomImagePrevious === mediaEntityId) {
                this.controlsCustomImagePreviousPreview = mediaEntity;
            }

            if (this.element.config.settings.value.controlsCustomImageNext === mediaEntityId) {
                this.controlsCustomImageNextPreview = mediaEntity;
            }
        },

        onChangeTab(name) {
            this.currentTabName = name;
        },

        onChangeSlideTab(slide, name) {
            this.currentSlideTabNames[slide.id] = name;
        },

        getIntOptions(count) {
            return Array.from({ length: count }, (value, key) => {
                return {
                    value: (key + 1).toString(),
                    label: (key + 1).toString(),
                };
            });
        },

        getFontWeightOptions(withDefault = true, defaultLabel = 'DEFAULT') {
            const options = Array.from({ length: 9 }, (value, key) => {
                return {
                    value: (key + 1).toString() + '00',
                    label: (key + 1).toString() + '00',
                };
            });

            if (withDefault) {
                options.unshift({
                    value: '',
                    label: defaultLabel,
                });
            }

            return options;
        },

        onClickAddSlide() {
            this.element.config.slides.value.push({
                active: true,
                id: Utils.createId(),
                name: this.$t(
                    'sw-cms.elements.solid-ase.content-slider.config.slides.fields.name.default'
                ),
                contentType: 'default',

                // Content
                smallHeadline: 'Lorem ipsum',
                headline: 'Lorem ipsum dolor sit amet',
                text: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor.',
                buttonLabel: 'Lorem ipsum',
                buttonLinkType: 'external',
                buttonLinkEntity: 'category',
                buttonLink: null,
                buttonLinkInternalQuery: '',
                buttonTargetBlank: false,
                buttonTitle: '',
                customContent: '',

                // Background
                backgroundColor: '',
                backgroundMedia: null,
                backgroundSizingMode: 'cover',
                backgroundPosition: 'center',
                backgroundAnimation: 'move',

                // Link
                linkType: 'external',
                linkEntity: 'category',
                link: null,
                linkInternalQuery: '',
                linkTargetBlank: false,
                linkTitle: '',

                // Publishing
                publishingType: 'instant',
                scheduledPublishingDateTime: null,
                scheduledUnpublishingDateTime: null
            });

            this.getMediaEntityPreviews();
            this.createSlideCurrentTabNames();
            this.createSlideButtonLinkCategoryCollections();
            this.createSlideLinkCategoryCollections();
        },

        onClickMoveSlide(slide, direction) {
            const slides = this.element.config.slides.value;
            const index = slides.indexOf(slide);

            let newIndex = null;

            if (direction === 'up') {
                newIndex = index - 1;
            } else if (direction === 'down') {
                newIndex = index + 1;
            }

            if (newIndex !== null) {
                slides.splice(newIndex, 0, slides.splice(index, 1)[0]);
            }

            this.element.config.slides.value = slides;
            this.getMediaEntityPreviews();
        },

        onClickDuplicateSlide(slide) {
            const slides = this.element.config.slides.value;
            const index = slides.indexOf(slide);
            const slideCopy = { ...slide };

            slideCopy.id = Utils.createId();
            slideCopy.name +=
                ' ' +
                this.$t(
                    'sw-cms.elements.solid-ase.content-slider.config.slides.copy-slide.suffix'
                );
            slides.splice(index + 1, 0, slideCopy);
            this.element.config.slides.value = slides;
            this.getMediaEntityPreviews();
            this.createSlideCurrentTabNames();
            this.createSlideButtonLinkCategoryCollections();
            this.createSlideLinkCategoryCollections();
        },

        onClickRemoveSlide(slide) {
            const slides = this.element.config.slides.value;
            const index = slides.indexOf(slide);

            slides.splice(index, 1);

            if (slides.length === 1) {
                slides[0].active = true;
            }

            this.element.config.slides.value = slides;

            // TODO: Clear slide data in tab names, button category collection and link category collection

            this.getMediaEntityPreviews();
        },

        onRemoveSlideBackgroundMedia(slide) {
            const slides = this.element.config.slides.value;
            const index = slides.indexOf(slide);

            this.slideBackgroundMediaPreviews[index] = null;
            slide.backgroundMedia = null;
        },

        onChangeSlideBackgroundMedia(mediaEntity, slide) {
            const slides = this.element.config.slides.value;
            const index = slides.indexOf(slide);

            this.slideBackgroundMediaPreviews[index] = mediaEntity[0];
            slide.backgroundMedia = mediaEntity[0].id;
        },

        async onFinishUploadSlideBackgroundMedia(mediaItem, slide) {
            const mediaEntity = await this.mediaRepository.get(
                mediaItem.targetId,
                Context.api
            );
            const slides = this.element.config.slides.value;
            const index = slides.indexOf(slide);

            this.slideBackgroundMediaPreviews[index] = mediaEntity;
            slide.backgroundMedia = mediaEntity.id;
            this.refreshMediaEntityPreviews(mediaEntity.id);
        },

        onChangeItems() {
            this.element.config.sliderSettings.value.slideBy = '1';
        },

        onChangeItemsMobile() {
            this.element.config.sliderSettings.value.slideByMobile = '1';
        },

        onChangeItemsTablet() {
            this.element.config.sliderSettings.value.slideByTablet = '1';
        },

        onChangeItemsDesktop() {
            this.element.config.sliderSettings.value.slideByDesktop = '1';
        },

        createSlideButtonLinkCategoryCollections() {
            this.element.config.slides.value.forEach((slide) => {
                const criteria = this.slideButtonLinkCategoriesCollectionCriteria(slide);

                this.categoryRepository.search(criteria, Context.api).then(result => {
                    this.slideButtonLinkCategoryCollections[slide.id] = result;
                });
            });
        },

        createSlideLinkCategoryCollections() {
            this.element.config.slides.value.forEach((slide) => {
                const criteria = this.slideLinkCategoriesCollectionCriteria(slide);

                this.categoryRepository.search(criteria, Context.api).then(result => {
                    this.slideLinkCategoryCollections[slide.id] = result;
                });
            });
        },

        slideButtonLinkCategoriesCollectionCriteria(slide) {
            const criteria = new Criteria(1, 1);

            let categoryId = null;

            if (slide.buttonLinkType === 'internal' && slide.buttonLinkEntity === 'category' && slide.buttonLink !== null) {
                categoryId = slide.buttonLink;
            }

            criteria.addFilter(Criteria.equals('id', categoryId));

            return criteria;
        },

        slideLinkCategoriesCollectionCriteria(slide) {
            const criteria = new Criteria(1, 1);

            let categoryId = null;

            if (slide.linkType === 'internal' && slide.linkEntity === 'category' && slide.link !== null) {
                categoryId = slide.link;
            }

            criteria.addFilter(Criteria.equals('id', categoryId));

            return criteria;
        },

        slideButtonLinkCategoryPlaceholder(slide) {
            return slide.buttonLink ? '' : this.$t('sw-cms.elements.solid-ase.content-slider.config.slides.content.fields.button-link.entity.types.category.placeholder');
        },

        slideLinkCategoryPlaceholder(slide) {
            return slide.link ? '' : this.$t('sw-cms.elements.solid-ase.content-slider.config.slides.link.fields.entity.types.category.placeholder');
        },

        onChangeSlideButtonLinkType(slide) {
            slide.buttonLink = null;
            slide.buttonLinkEntity = 'category';
        },

        onChangeSlideLinkType(slide) {
            slide.link = null;
            slide.linkEntity = 'category';
        },

        onChangeSlideButtonLinkEntity(slide) {
            slide.buttonLink = null;
        },

        onChangeSlideLinkEntity(slide) {
            slide.link = null;
        },

        onAddSlideButtonLinkCategory(slide, category) {
            slide.buttonLink = category.id;
            this.updateSlideButtonLinkCategoryCollections(slide);
        },

        onAddSlideLinkCategory(slide, category) {
            slide.link = category.id;
            this.updateSlideLinkCategoryCollections(slide);
        },

        onRemoveSlideButtonLinkCategory(slide) {
            slide.buttonLink = null;
            this.updateSlideButtonLinkCategoryCollections(slide);
        },

        onRemoveSlideLinkCategory(slide) {
            slide.link = null;
            this.updateSlideLinkCategoryCollections(slide);
        },

        updateSlideButtonLinkCategoryCollections(slide) {
            const newCollection = EntityCollection.fromCollection(this.slideButtonLinkCategoryCollections[slide.id]);
            this.slideButtonLinkCategoryCollections[slide.id] = newCollection;
        },

        updateSlideLinkCategoryCollections(slide) {
            const newCollection = EntityCollection.fromCollection(this.slideLinkCategoryCollections[slide.id]);
            this.slideLinkCategoryCollections[slide.id] = newCollection;
        },

        onChangeSlidePublishingType(slide) {
            switch (slide.publishingType) {
                case 'instant':
                    slide.scheduledPublishingDateTime = null;
                    slide.scheduledUnpublishingDateTime = null;
                    slide.active = true;
                    break;

                case 'scheduled':
                    slide.active = false;
                    break;
            }
        },

        onChangeSlideScheduledPublishingDateTime(slide) {
            if (slide.scheduledPublishingDateTime === null) {
                slide.active = false;
                return;
            }

            const currentUtcDate = this.getUtcDate(new Date());
            const scheduledPublishingDateTime = new Date(slide.scheduledPublishingDateTime);
            const scheduledPublishingUtcDateTime = this.getUtcDate(scheduledPublishingDateTime);

            if (scheduledPublishingUtcDateTime < currentUtcDate) {
                slide.active = true;
            } else {
                slide.active = false;
            }
        },

        onChangeSlideScheduledUnpublishingDateTime(slide) {
            const currentUtcDate = this.getUtcDate(new Date());
            const scheduledUnpublishingDateTime = new Date(slide.scheduledUnpublishingDateTime);
            const scheduledUnpublishingUtcDateTime = this.getUtcDate(scheduledUnpublishingDateTime);

            if (scheduledUnpublishingUtcDateTime <= currentUtcDate) {
                slide.active = false;
            } else if (slide.scheduledPublishingDateTime !== null) {
                const scheduledPublishingDateTime = new Date(slide.scheduledPublishingDateTime);
                const scheduledPublishingUtcDateTime = this.getUtcDate(scheduledPublishingDateTime);

                if (scheduledPublishingUtcDateTime < currentUtcDate) {
                    slide.active = true;
                } else {
                    slide.active = false;
                }
            }
        },

        getSlideScheduledPublishingDateTimeConfig(slide) {
            const defaultConfig = {
                minuteIncrement: 15
            };

            if (!slide.scheduledUnpublishingDateTime) {
                return defaultConfig;
            }

            return {
                ...defaultConfig,
                maxDate: slide.scheduledUnpublishingDateTime
            };
        },

        getSlideScheduledUnpublishingDateTimeConfig(slide) {
            const defaultConfig = {
                minuteIncrement: 15
            };

            if (!slide.scheduledPublishingDateTime) {
                return defaultConfig;
            }

            const scheduledPublishingDateTime = new Date(slide.scheduledPublishingDateTime);
            const scheduledPublishingUtcDateTime = this.getUtcDate(scheduledPublishingDateTime);

            return {
                ...defaultConfig,
                minDate: scheduledPublishingUtcDateTime
            };
        },

        getUtcDate(date) {
            return new Date(
                date.getUTCFullYear(),
                date.getUTCMonth(),
                date.getUTCDate(),
                date.getUTCHours(),
                date.getUTCMinutes(),
                date.getUTCSeconds(),
            );
        },
    },
});
