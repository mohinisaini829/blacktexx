const { Component, Mixin, Filter } = Shopware;
import template from './sw-cms-el-netzp-powerpack6-parallax.html.twig';
import './sw-cms-el-netzp-powerpack6-parallax.scss';

Component.register('sw-cms-el-netzp-powerpack6-parallax', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

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
        },

        getStyle() {
            return 'height: ' + this.element.config.height.value + 'rem;';
        }
    },

    methods: {
        createdComponent() {
            this.initElementConfig('netzp-powerpack6-parallax');
            this.initElementData('netzp-powerpack6-parallax');
        }
    }
});
