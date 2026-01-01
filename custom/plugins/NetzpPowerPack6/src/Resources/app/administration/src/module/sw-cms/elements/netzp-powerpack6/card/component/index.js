const { Component, Mixin, Filter } = Shopware;
import template from './sw-cms-el-netzp-powerpack6-card.html.twig';
import './sw-cms-el-netzp-powerpack6-card.scss';

Component.register('sw-cms-el-netzp-powerpack6-card', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    data() {
        return {
            demoImage1: null,
            demoImage2: null,
            demoValueText1: '',
            demoValueText2: ''
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

        'element.config.text1.source': {
            handler() {
                this.updateDemoValues();
            },
        },

        'element.config.text2.source': {
            handler() {
                this.updateDemoValues();
            },
        },
    },

    created() {
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

        assetFilter() {
            return Filter.getByName('asset');
        },

        getStyle()
        {
            return 'height: ' + this.element.config.height.value + 'rem;';
        },

        getClass()
        {
            return 'card-' + this.element.config.type.value;
        },

        getStyleIcon()
        {
            return 'color: '  + this.element.config.color1.value;
        },

        getStyleFront()
        {
            return 'background-color: ' + this.element.config.backgroundColor1.value + '; color: '  + this.element.config.color1.value;
        },

        getStyleBack()
        {
            return 'background-color: ' + this.element.config.backgroundColor2.value + '; color: '  + this.element.config.color2.value;
        },

        getImageClassFront()
        {
            let s = '';

            s += this.element.config.imageCover1.value ? 'cover' : 'contain';
            if(this.isBlur) {
                s += ' blur-effect';
            }

            return s;
        },

        getImageClassBack()
        {
            return this.element.config.imageCover2.value ? 'cover' : 'contain';
        },

        getImageStyleFront()
        {
            return 'opacity: ' + this.element.config.imageOpacity1.value + '%;';
        },

        getImageStyleBack()
        {
            return 'opacity: ' + this.element.config.imageOpacity2.value + '%;';
        },

        getTextClass()
        {
            let s = 'card-text';

            if(this.blurText) {
                s += ' blur-effect';
            }

            return s;
        },

        getLinkClass()
        {
            let s = 'card-link';

            if(this.blurText) {
                s += ' blur-effect';
            }

            return s;
        },

        hasUrl()
        {
            return this.element.config.url.value !== '';
        },

        hasUrlText()
        {
            return this.element.config.urlText.value !== '';
        },

        hasIcon()
        {
            return this.element.config.icon.value !== '';
        },

        isTwoSided()
        {
            return this.element.config.type.value === 'flip' ||
                   this.element.config.type.value === 'reveal';
        },

        isPopup()
        {
            return this.element.config.type.value === 'popup';
        },

        isBlur()
        {
            return this.element.config.type.value === 'blur';
        },

        blurText()
        {
            return this.isBlur && this.element.config.blurText.value;
        }
    },

    methods: {
        createdComponent()
        {
            this.initElementConfig('netzp-powerpack6-card');
            this.initElementData('netzp-powerpack6-card');
        },

        updateDemoValues()
        {
            if (this.element.config.image1.source === 'mapped') {
                this.demoImage1 = this.getDemoValue(this.element.config.image1.value);
            }
            if (this.element.config.image2.source === 'mapped') {
                this.demoImage2 = this.getDemoValue(this.element.config.image2.value);
            }
            if (this.element.config.text1.source === 'mapped') {
                this.demoValueText1 = this.getDemoValue(this.element.config.text1.value);
            }
            if (this.element.config.text2.source === 'mapped') {
                this.demoValueText2 = this.getDemoValue(this.element.config.text2.value);
            }
        }
    }
});
