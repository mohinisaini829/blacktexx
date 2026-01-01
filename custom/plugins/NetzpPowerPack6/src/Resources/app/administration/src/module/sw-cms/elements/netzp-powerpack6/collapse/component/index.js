const { Component, Mixin } = Shopware;
import template from './sw-cms-el-netzp-powerpack6-collapse.html.twig';
import './sw-cms-el-netzp-powerpack6-collapse.scss';

Component.register('sw-cms-el-netzp-powerpack6-collapse', {
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

    methods: {
        createdComponent() {
            this.initElementConfig('netzp-powerpack6-collapse');
        }
    }
});
