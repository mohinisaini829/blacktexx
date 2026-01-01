const { Component, Mixin } = Shopware;
import template from './sw-cms-el-netzp-powerpack6-html.html.twig';
import './sw-cms-el-netzp-powerpack6-html.scss';

Component.register('sw-cms-el-netzp-powerpack6-html', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    computed: {
        getHtml() {
            var s = "";
            if(this.element.config.html.value) {
                if(this.element.config.css.value) {
                    s += "<style>" + this.element.config.css.value + "</style>";
                }
                s += this.element.config.html.value;

                return s;
            }

            return "";
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('netzp-powerpack6-html');
        }
    }
});
