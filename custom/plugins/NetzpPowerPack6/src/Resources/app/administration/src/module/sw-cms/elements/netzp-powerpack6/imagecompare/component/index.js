const { Component, Mixin, Filter } = Shopware;
import template from './sw-cms-el-netzp-powerpack6-imagecompare.html.twig';
import './sw-cms-el-netzp-powerpack6-imagecompare.scss';

Component.register('sw-cms-el-netzp-powerpack6-imagecompare', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    data() {
        return {
            demoImage1: null,
            demoImage2: null,
        };
    },

    watch: {
        cmsPageState: {
            deep: true,
            handler() {
                this.updateDemoValues();
            },
        },

        'element.config.image1.source': {
            handler() {
                this.updateDemoValues();
            },
        },

        'element.config.image2.source': {
            handler() {
                this.updateDemoValues();
            },
        },
    },

    created()
    {
        this.createdComponent();
    },

    computed: {
        image1Url()
        {
            const elemData = this.element.data.image1;
            const mediaSource = this.element.config.image1.source;

            if (mediaSource === 'mapped') {
                const demoMedia = this.getDemoValue(this.element.config.image1.value);

                if (demoMedia?.url) {
                    return demoMedia.url;
                }

                return this.assetFilter('administration/static/img/cms/preview_mountain_large.jpg');
            }

            if (elemData?.id) {
                return this.element.data.image1.url;
            }

            if (elemData?.url) {
                return this.assetFilter(elemData.url);
            }

            return this.assetFilter('administration/static/img/cms/preview_mountain_large.jpg');
        },

        image2Url()
        {
            const elemData = this.element.data.image2;
            const mediaSource = this.element.config.image2.source;

            if (mediaSource === 'mapped') {
                const demoMedia = this.getDemoValue(this.element.config.image2.value);

                if (demoMedia?.url) {
                    return demoMedia.url;
                }

                return this.assetFilter('administration/static/img/cms/preview_mountain_large.jpg');
            }

            if (elemData?.id) {
                return this.element.data.image2.url;
            }

            if (elemData?.url) {
                return this.assetFilter(elemData.url);
            }

            return this.assetFilter('administration/static/img/cms/preview_mountain_large.jpg');
        },

        assetFilter()
        {
            return Filter.getByName('asset');
        },

        getStyleHeight()
        {
            let s = 'height: ' + this.element.config.height.value + 'rem;';

            return s;
        }
    },

    methods: {
        createdComponent()
        {
            this.initElementConfig('netzp-powerpack6-imagecompare');
            this.initElementData('netzp-powerpack6-imagecompare');
        },

        updateDemoValues()
        {
            if (this.element.config.image1.source === 'mapped') {
                this.demoImage1 = this.getDemoValue(this.element.config.image1.value);
            }
            if (this.element.config.image2.source === 'mapped') {
                this.demoImage2 = this.getDemoValue(this.element.config.image2.value);
            }
        }
    }
});
