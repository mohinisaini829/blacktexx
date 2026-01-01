const { Component, Mixin } = Shopware;
import template from './sw-cms-el-netzp-powerpack6-infobar.html.twig';
import './sw-cms-el-netzp-powerpack6-infobar.scss';

Component.register('sw-cms-el-netzp-powerpack6-infobar', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    created() {
        this.createdComponent();
    },

    data() {
        return {
            active: 'tab1'
        };
    },

    computed: {
        getIconStyle() {
            var s = '';
            s += 'font-size: ' + this.element.config.iconSize.value + 'rem; ';
            s += 'color: ' + this.element.config.iconColor.value + ';';
            if(this.element.config.layout.value == 'vertical') {
                s += 'background-color: ' + this.element.config.circleColor.value + ';';
                s += 'width: calc(' + this.element.config.iconSize.value + 'rem * 2); ';
                s += 'height: calc(' + this.element.config.iconSize.value + 'rem * 2); ';
                s += 'line-height: calc(' + this.element.config.iconSize.value + 'rem * 2); ';
            }

            return s;
        },

        getTextStyle() {
            var s = '';
            s += 'font-size: ' + this.element.config.textSize.value + 'rem; ';
            s += 'color: ' + this.element.config.textColor.value;

            return s;
        }
    },

    methods: {
        createdComponent() {
            this.initElementConfig('netzp-powerpack6-infobar');
        }
    }
});
