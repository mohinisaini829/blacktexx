const { Component, Mixin } = Shopware;
import template from './sw-cms-el-netzp-powerpack6-counter.html.twig';
import './sw-cms-el-netzp-powerpack6-counter.scss';

Component.register('sw-cms-el-netzp-powerpack6-counter', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    created() {
        this.createdComponent();
    },

    computed: {
        getIconStyle() {
            if(this.element.config.iconColor) {
                return 'color: ' + this.element.config.iconColor.value + '; ';
            }

            return '';
        },

        getTitleStyle() {
            if(this.element.config.titleColor) {
                return 'color: ' + this.element.config.titleColor.value + '; ';
            }

            return '';
        },

        getCounterStyle() {
            if(this.element.config.counterColor) {
                return 'color: ' + this.element.config.counterColor.value + '; ';
            }

            return '';
        },
    },

    methods: {
        createdComponent()
        {
            this.initElementConfig('netzp-powerpack6-counter');
        },

        getCounterTemplate()
        {
            let s = this.element.config.text.value;
            if(s === '') {
                s = '{counter}';
            }

            s = s.replace(/\{counter\}/g, this.element.config.end.value);
            s = s.replace(/\{start\}/g, this.element.config.start.value);
            s = s.replace(/\{end\}/g, this.element.config.end.value);

            return s;
        }
    }
});
