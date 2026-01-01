const { Component, Mixin, Filter } = Shopware;
import template from './sw-cms-el-netzp-powerpack6-cta.html.twig';
import './sw-cms-el-netzp-powerpack6-cta.scss';

Component.register('sw-cms-el-netzp-powerpack6-cta', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    data() {
        return {
            demoImage: null,
            demoValueTitle: '',
            demoValueText: '',
            demoValueButton: ''
        };
    },

    watch: {
        cmsPageState: {
            deep: true,
            handler() {
                this.updateDemoValues();
            },
        },

        'element.config.image.source': {
            handler() {
                this.updateDemoValues();
            },
        },

        'element.config.backgroundImage.source': {
            handler() {
                this.updateDemoValues();
            },
        },

        'element.config.title.source': {
            handler() {
                this.updateDemoValues();
            },
        },

        'element.config.text.source': {
            handler() {
                this.updateDemoValues();
            },
        },

        'element.config.button.source': {
            handler() {
                this.updateDemoValues();
            },
        }
    },

    created() {
        this.createdComponent();
    },

    computed: {
        imageUrl() {
            const elemData = this.element.data.image;
            const mediaSource = this.element.config.image.source;

            if (mediaSource === 'mapped') {
                const demoMedia = this.getDemoValue(this.element.config.image.value);

                if (demoMedia?.url) {
                    return demoMedia.url;
                }

                return this.assetFilter('administration/static/img/cms/preview_mountain_large.jpg');
            }

            if (elemData?.id) {
                return this.element.data.image.url;
            }

            if (elemData?.url) {
                return this.assetFilter(elemData.url);
            }

            return this.assetFilter('administration/static/img/cms/preview_mountain_large.jpg');
        },

        backgroundImageUrl() {
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
        },

        autoLayout() {
            return this.element.config.autoLayout.value;
        },

        hasImage() {
            return this.element.config.image.value != null;
        },

        imagePos() {
            return this.element.config.imagePosX.value;
        },

        mainStyle() {
            var mainStyle = "";

            mainStyle += "height: " + this.element.config.height.value + "rem;";
            if (this.element.config.borderColor.value) {
                mainStyle += "border: 1px solid " + this.element.config.borderColor.value + ";";
            }
            if (this.element.config.borderRadius.value) {
                mainStyle += "border-radius: " + this.element.config.borderRadius.value + "px;";
            }
            if (this.element.config.backgroundColor.value) {
                mainStyle += "background-color: " + this.element.config.backgroundColor.value + ";";
            }

            if (this.element.config.backgroundImage.value) {
                mainStyle += "background: " + this.element.config.backgroundColor.value + " ";
                mainStyle += "url('" + this.backgroundImageUrl + "') ";
                mainStyle += "no-repeat ";
                mainStyle += this.element.config.backgroundImageAlign.value + "; ";
                mainStyle += "background-size: " + this.element.config.backgroundImageMode.value + "; ";
            }

            return mainStyle;
        },

        mainClass() {
            var mainClass = "";

            if (this.autoLayout) {
                mainClass = "layout-auto";
            } else {
                mainClass = "layout-manual";
            }

            return mainClass;
        },

        imageInnerStyle() {
            var imageInnerStyle = "";

            if (this.element.config.imageBlock.value) {
                if (this.imagePos == "left" || this.imagePos == "right") {
                    imageInnerStyle += "width: " + this.element.config.imageSize.value + "rem; ";
                    imageInnerStyle += "height: " + this.element.config.height.value + "rem; ";
                } else {
                    imageInnerStyle += "width: 100%; ";
                    imageInnerStyle += "height: " + this.element.config.imageSize.value + "rem; ";
                }
            } else {
                imageInnerStyle += "width: " + this.element.config.imageSize.value + "rem; ";
                imageInnerStyle += "height: " + this.element.config.imageSize.value + "rem; ";
            }

            return imageInnerStyle;
        },

        imageClass() {
            var imageClass = "", imageAlign = ""

            if (this.element.config.imageBlock.value) {
                imageClass += "image-block ";
            }

            if (this.element.config.imagePosX.value == "center" && this.element.config.imagePosY.value == "center") {
                imageAlign += "pos-xy-center ";
            } else {
                imageAlign += "pos-x-" + this.element.config.imagePosX.value + " ";
                imageAlign += "pos-y-" + this.element.config.imagePosY.value + " ";
            }

            if (! this.autoLayout) {
                imageClass += imageAlign;
            }

            return imageClass;
        },

        imageInnerClass() {
            var imageInnerClass = "";

            if (!this.element.config.imageBlock.value) {
                imageInnerClass += (this.element.config.imageRounded.value) ? "image-rounded" : "";
            }

            return imageInnerClass;
        },

        titleStyle() {
            var titleStyle = "";

            titleStyle += "font-size: " + this.element.config.titleSize.value + "rem; ";
            titleStyle += "color: " + this.element.config.titleColor.value + "; ";
            if(this.element.config.titleBackgroundColor.value) {
                titleStyle += "background-color: " + this.element.config.titleBackgroundColor.value + "; ";
            }

            if(this.autoLayout) {
                if(this.hasImage && (this.imagePos == "left" || this.imagePos == "right")) {
                    titleStyle += "width: calc(99% - " + this.element.config.imageSize.value + "rem); ";
                }
                else {
                    titleStyle += "width: 100%; ";
                }
            }


            return titleStyle;
        },

        titleClass() {
            var titleClass = "", titleAlign = "";

            if(this.element.config.titleShadow.value) {
                titleClass += "text-shadow ";
            }

            if(this.element.config.titlePosX.value == "center" && this.element.config.titlePosY.value == "center") {
                titleAlign += "pos-xy-center ";
            }
            else {
                titleAlign += "pos-x-" + this.element.config.titlePosX.value + " ";
                titleAlign += "pos-y-" + this.element.config.titlePosY.value + " ";
            }
            if( ! this.autoLayout) {
                titleClass += titleAlign;
            }

            return titleClass;
        },

        textStyle() {
            var textStyle = "";

            textStyle += "font-size: " + this.element.config.textSize.value + "rem; ";
            textStyle += "color: " + this.element.config.textColor.value + "; ";
            textStyle += "background-color: " + this.element.config.textBackgroundColor.value + "; ";

            if(this.autoLayout) {
                if(this.hasImage && (this.imagePos == "left" || this.imagePos == "right")) {
                    textStyle += "width: calc(99% - " + this.element.config.imageSize.value + "rem); ";
                }
                else {
                    textStyle += "width: 100%; ";
                }
            }

            return textStyle;
        },

        textClass() {
            var textClass = "", textAlign = "";

            if(this.element.config.textShadow.value) {
                textClass += "text-shadow ";
            }

            if(this.element.config.textPosX.value == "center" && this.element.config.textPosY.value == "center") {
                textAlign += "pos-xy-center ";
            }
            else {
                textAlign += "pos-x-" + this.element.config.textPosX.value + " ";
                textAlign += "pos-y-" + this.element.config.textPosY.value + " ";
            }
            if(this.element.config.textAlign.value == "center") {
                textAlign += "text-center ";
            }
            else if (this.element.config.textAlign.value == "right") {
                textAlign += "text-right ";
            }

            if( ! this.autoLayout) {
                textClass += textAlign;
            }
            return textClass;
        },

        buttonStyle() {
            var buttonStyle = "";

            buttonStyle += "background-color: " + this.element.config.buttonBackgroundColor.value + "; ";
            buttonStyle += "color: " + this.element.config.buttonColor.value + "; ";
            buttonStyle += "border: 1px solid " + this.element.config.buttonBorderColor.value + "; ";
            buttonStyle += "line-height: " + this.element.config.buttonSize.value + "rem; ";
            buttonStyle += "font-size: " + this.element.config.buttonSize.value + "rem; ";

            return buttonStyle;
        },

        buttonClass() {
            var buttonClass = "", buttonAlign = "";

            if (this.element.config.buttonBlock.value) {
                buttonClass += "btn-block ";
            }
            if (this.element.config.buttonRounded.value) {
                buttonClass += "btn-rounded ";
            }
            if (this.element.config.buttonShadow.value) {
                buttonClass += "btn-shadow ";
            }

            if(this.autoLayout) {
                buttonAlign += "pos-x-right pos-y-bottom ";
            }
            else {
                if(this.element.config.buttonPosX.value == "center" && this.element.config.buttonPosY.value == "center") {
                    buttonAlign += "pos-xy-center ";
                }
                else {
                    buttonAlign += "pos-x-" + this.element.config.buttonPosX.value + " ";
                    buttonAlign += "pos-y-" + this.element.config.buttonPosY.value + " ";
                }
            }
            buttonClass += buttonAlign;

            return buttonClass;
        }
    },

    methods: {
        createdComponent()
        {
            this.initElementConfig('netzp-powerpack6-cta');
            this.initElementData('netzp-powerpack6-cta');
        },

        updateDemoValues()
        {
            if (this.element.config.image.source === 'mapped') {
                this.demoImage = this.getDemoValue(this.element.config.image.value);
            }
            if (this.element.config.backgroundImage.source === 'mapped') {
                this.demoImage = this.getDemoValue(this.element.config.backgroundImage.value);
            }
            if (this.element.config.title.source === 'mapped') {
                this.demoValueTitle = this.getDemoValue(this.element.config.title.value);
            }
            if (this.element.config.text.source === 'mapped') {
                this.demoValueText = this.getDemoValue(this.element.config.text.value);
            }
            if (this.element.config.button.source === 'mapped') {
                this.demoValueButton = this.getDemoValue(this.element.config.button.value);
            }
        }
    }
});
