const { Component, Mixin, Filter } = Shopware;
import template from './sw-cms-el-netzp-powerpack6-cta2.html.twig';
import './sw-cms-el-netzp-powerpack6-cta2.scss';

Component.register('sw-cms-el-netzp-powerpack6-cta2', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    created() {
        this.createdComponent();
    },

    data() {
        return {
            active: 'element1',
            demoValueBackgroundImage: null,
            demoValues: []
        };
    },

    watch: {
        cmsPageState: {
            deep: true,
            handler() {
                this.updateDemoValues();
            },
        },

        'element.config.backgroundImage.source': {
            handler() {
                this.updateDemoValues();
            },
        }
    },

    computed: {
        getBackgroundImageSourceUrl() {
            const elemData = this.element.data.backgroundImage;
            const mediaSource = this.element.config.backgroundImage.source;

            if (mediaSource === 'mapped') {
                const demoMedia = this.getDemoValue(this.element.config.backgroundImage.value);

                if (demoMedia?.url) {
                    return demoMedia.url;
                }

                return this.assetFilter('administration/static/img/cms/preview_mountain_large.jpg');
            }

            if (elemData?.id) {
                return this.element.data.backgroundImage.url;
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
        initElements()
        {
            for(let i = 1; i <= this.element.config.numberOfElements.value; i++) {
                this.element.config.elements.value.push({
                    type: 'button',
                    contents: {
                        source: 'static',
                        value: 'Button ' + i
                    },
                    image: {
                        source: 'static',
                        value: null,
                        required: false,
                        entity: {
                            name: 'media'
                        }
                    },
                    mode: 'auto',
                    color: '#000000',
                    backgroundColor: '#ffffff',
                    borderColor: '',
                    borderWidth: '0',
                    fontSize: '150%',
                    height: 'auto',
                    width: 'auto',
                    padding: '0 .5rem',
                    alignSelf: 'auto',
                    url: '',
                    urlNewWindow: false
                })
            }
        },

        createdComponent()
        {
            this.initElementConfig('netzp-powerpack6-cta2');
            if(this.element.config.elements.value.length <= this.element.config.numberOfElements.value - this.element.config.elements.value.length) {
                this.initElements();
            }
        },

        getImageSourceUrl(n)
        {
            const elemData = this.element.data[n]?.image;
            const mediaSource = this.element.config.elements.value[n]?.image.source;

            if (mediaSource === 'mapped') {
                const demoMedia = this.getDemoValue(this.element.config.elements.value[n].image.value);

                if (demoMedia?.url) {
                    return demoMedia.url;
                }

                return this.assetFilter('administration/static/img/cms/preview_mountain_large.jpg');
            }

            if (elemData?.id) {
                return this.element.data[n].image.url;
            }

            if (elemData?.url) {
                return this.assetFilter(elemData.url);
            }

            return this.assetFilter('administration/static/img/cms/preview_mountain_large.jpg');
        },

        getElementBody(element, n)
        {
            let s = '';
            if(element === null || element === undefined) {
                return '';
            }

            if(element.type === 'text' || element.type === 'button') {
                if(element.contents.source === 'mapped') {
                    s = this.demoValues[n];
                }
                else {
                    s = element.contents.value;
                }
            }
            else if(element.type === 'image') {
                let imgStyle = '';
                if (element.width === '' || element.width === 'auto') {
                    imgStyle += 'max-width: 100%;'
                }
                else {
                    imgStyle += 'width: ' + element.width + ';';
                }

                if (element.height === '' || element.height === 'auto') {
                    imgStyle += 'max-height: 100%;'
                }
                else {
                    imgStyle += 'height: ' + element.height + ';';
                }

                s += '<img src="' + this.getImageSourceUrl(n) + '" style="' + imgStyle + '">';
            }

            return s;
        },

        getContainerStyle()
        {
            let s = '';
            if(this.element === null) {
                return '';
            }

            if(this.element.config.height.value !== '') {
                s += 'height: ' + this.element.config.height.value + ';';
            }
            if(this.element.config.gap.value !== '') {
                s += 'gap: ' + this.element.config.gap.value + ';';
                s += 'padding: ' + this.element.config.gap.value + ';';
            }

            s += 'flex-direction: ' + this.element.config.direction.value + ';';
            s += 'justify-content: ' + this.element.config.justifyContent.value + ';';
            s += 'align-items: ' + this.element.config.alignItems.value + ';';

            if(this.element.config.backgroundImage.value !== null) {
                s += 'background-image: url("' + this.getBackgroundImageSourceUrl + '");';
                s += 'background-size: ' + this.element.config.backgroundImageMode.value + ';';
                s += 'background-position: ' + this.element.config.backgroundImageAlign.value + ';';
                s += 'background-repeat: no-repeat;';
            }
            if(this.element.config.backgroundColor.value !== '') {
                s += 'background-color: ' + this.element.config.backgroundColor.value + ';';
            }

            return s;
        },

        getElementStyle(element)
        {
            let s = '';
            if(element === null || element === undefined) {
                return '';
            }

            s += 'align-self: ' + element.alignSelf + ';';
            s += 'display: inline-block;'; // prevent flex effects (e.g. "A <b>B</b> C" suppresses white space for flex reasons

            if (element.width !== '') {
                s += 'width: ' + element.width + ';';
            }
            if (element.height !== '') {
                s += 'height: ' + element.height + ';';
            }
            if(element.padding !== '') {
                s += 'padding: ' + element.padding + ';';
            }
            if(element.fontSize !== '') {
                s += 'font-size: ' + element.fontSize + ';';
            }

            if(this.isCustomButton(element) || ! this.isButton(element)) {
                if (element.color !== '') {
                    s += 'color: ' + element.color + ';';
                }
                if (element.backgroundColor !== '') {
                    s += 'background-color: ' + element.backgroundColor + ';';
                }
                if (element.borderWidth !== '0' && element.borderColor !== '') {
                    s += 'border: ' + element.borderWidth + 'px solid ' + element.borderColor + ';';
                }
            }

            return s;
        },

        getElementClass(element)
        {
            let s = '';

            if(element === null || element === undefined) {
                return '';
            }

            if(this.isButton(element)) {
                s += 'sw-button ';
                s += element.mode;
            }

            return s;
        },

        isButton(element)
        {
            return element.type === 'button';
        },

        isCustomButton(element)
        {
            return this.isButton(element) && element.mode === 'custom';
        },

        updateDemoValues()
        {
            var me = this;

            me.element.config.elements.value.forEach(function(element, n) {
                if (element.contents.source === 'mapped') {
                    me.$set(me.demoValues, n, me.getDemoValue(element.contents.value));
                }
                if (element.image.source === 'mapped') {
                    me.$set(me.demoValues, n, me.getDemoValue(element.image.value));
                }
            });

            if (this.element.config.backgroundImage.source === 'mapped') {
                this.demoValueBackgroundImage = this.getDemoValue(this.element.config.backgroundImage.value);
            }
        }
    }
});
