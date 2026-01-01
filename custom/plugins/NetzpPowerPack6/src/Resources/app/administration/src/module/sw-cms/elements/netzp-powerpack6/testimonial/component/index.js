const { Component, Mixin, Filter } = Shopware;
import template from './sw-cms-el-netzp-powerpack6-testimonial.html.twig';
import './sw-cms-el-netzp-powerpack6-testimonial.scss';

Component.register('sw-cms-el-netzp-powerpack6-testimonial', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    data() {
        return {
            editable: true,
            demoMedia: null,
            demoValueTitle: '',
            demoValueContents: '',
            demoValueName: '',
            demoValueName2: '',
        };
    },

    watch: {
        cmsPageState: {
            deep: true,
            handler() {
                this.updateDemoValues();
            },
        },

        'element.config.media.source': {
            handler() {
                this.updateDemoValues();
            },
        },

        'element.config.title.source': {
            handler() {
                this.updateDemoValues();
            },
        },

        'element.config.contents.source': {
            handler() {
                this.updateDemoValues();
            },
        },

        'element.config.name.source': {
            handler() {
                this.updateDemoValues();
            },
        },

        'element.config.name2.source': {
            handler() {
                this.updateDemoValues();
            },
        },
    },

    created() {
        this.createdComponent();
    },

    computed: {
        mediaUrl()
        {
            const elemData = this.element.data.media;
            const mediaSource = this.element.config.media.source;

            if (mediaSource === 'mapped') {
                const demoMedia = this.getDemoValue(this.element.config.media.value);

                if (demoMedia?.url) {
                    return demoMedia.url;
                }

                return this.assetFilter('administration/static/img/cms/preview_mountain_large.jpg');
            }

            if (elemData?.id) {
                return this.element.data.media.url;
            }

            if (elemData?.url) {
                return this.assetFilter(elemData.url);
            }

            return this.assetFilter('administration/static/img/cms/preview_mountain_large.jpg');
        },

        assetFilter() {
            return Filter.getByName('asset');
        }
    },

    methods: {
        createdComponent() {
            this.initElementConfig('netzp-powerpack6-testimonial');
            this.initElementData('netzp-powerpack6-testimonial');
        },

        updateDemoValues() {
            if (this.element.config.media.source === 'mapped') {
                this.demoMedia = this.getDemoValue(this.element.config.media.value);
            }
            if (this.element.config.title.source === 'mapped') {
                this.demoValueTitle = this.getDemoValue(this.element.config.title.value);
            }
            if (this.element.config.contents.source === 'mapped') {
                this.demoValueContents = this.getDemoValue(this.element.config.contents.value);
            }
            if (this.element.config.name.source === 'mapped') {
                this.demoValueName = this.getDemoValue(this.element.config.name.value);
            }
            if (this.element.config.name2.source === 'mapped') {
                this.demoValueName2 = this.getDemoValue(this.element.config.name2.value);
            }
        }
    }
});
