import template from './sw-cms-el-solid-ase-content-slider.html.twig';
import './sw-cms-el-solid-ase-content-slider.scss';

const { Component, Mixin, Context, Utils } = Shopware;
const { cloneDeep } = Utils.object;

Component.register('sw-cms-el-solid-ase-content-slider', {
    template,

    mixins: [Mixin.getByName('cms-element')],

    inject: ['repositoryFactory'],

    data() {
        return {
            previewBackgroundMedia: [],
            previewBackgroundMediaHasInitLoaded: false,
        };
    },

    computed: {
        mediaRepository() {
            return this.repositoryFactory.create('media');
        },

        previewSlides() {
            if (this.element.config && this.element.config.slides.value) {
                const activeSlides = this.element.config.slides.value.filter(
                    (slide) => {
                        return slide.active;
                    }
                );

                if (activeSlides.length === 0) {
                    return activeSlides;
                }

                if (
                    this.element.config.sliderSettings.value.loop &&
                    activeSlides.length <
                        this.element.config.sliderSettings.value.items
                ) {
                    const missingSlideCount =
                        this.element.config.sliderSettings.value.items -
                        activeSlides.length;

                    for (let i = 0; i < missingSlideCount; i++) {
                        activeSlides.push(
                            activeSlides[i % activeSlides.length]
                        );
                    }
                }

                if (this.element.config.sliderSettings.value.items > 6) {
                    const visibleActiveSlides = activeSlides.slice(0, 5);

                    return [
                        ...visibleActiveSlides,
                        {
                            id: 'more',
                            isMoreSlide: true,
                        }
                    ];
                }

                if (
                    activeSlides.length >
                    this.element.config.sliderSettings.value.items
                ) {
                    return activeSlides.slice(
                        0,
                        this.element.config.sliderSettings.value.items
                    );
                }

                return activeSlides;
            }
        },

        previewSlidesProxy() {
            return cloneDeep(this.previewSlides);
        },
    },

    watch: {
        previewSlidesProxy: {
            deep: true,
            handler: function (newSlides, oldSlides) {
                newSlides.forEach(async (slide, index) => {
                    if (
                        this.previewBackgroundMediaHasInitLoaded &&
                        index < oldSlides.length
                    ) {
                        if (
                            slide.backgroundMedia &&
                            slide.backgroundMedia !==
                                oldSlides[index].backgroundMedia
                        ) {
                            this.loadAndReplacePreviewBackgroundMedia(
                                slide.backgroundMedia
                            );
                        }

                        return;
                    }

                    if (slide.backgroundMedia) {
                        const mediaEntity = this.previewBackgroundMedia.find(
                            (mediaEntity) => {
                                return mediaEntity.id === slide.backgroundMedia;
                            }
                        );

                        if (!mediaEntity) {
                            this.loadAndReplacePreviewBackgroundMedia(
                                slide.backgroundMedia
                            );
                        }
                    }
                });

                if (!this.previewBackgroundMediaHasInitLoaded) {
                    this.previewBackgroundMediaHasInitLoaded = true;
                }
            },
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('solid-ase-content-slider');
            this.initElementData('solid-ase-content-slider');

            if (!this.element.config.slides.value[0].id) {
                this.element.config.slides.value[0].id = Utils.createId();
            }
        },

        loadAndReplacePreviewBackgroundMedia(id) {
            this.mediaRepository.get(id, Context.api).then((mediaEntity) => {
                const newPreviewBackgroundMedia =
                    this.previewBackgroundMedia.filter((mediaEntity) => {
                        return mediaEntity.id !== id;
                    });

                newPreviewBackgroundMedia.push(mediaEntity);

                this.previewBackgroundMedia = newPreviewBackgroundMedia;
            });
        },

        async loadMedia(id) {
            const mediaEntity = await this.mediaRepository.get(id, Context.api);
            return mediaEntity;
        },
    },
});
