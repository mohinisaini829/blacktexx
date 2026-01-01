const { Component, Mixin } = Shopware;
import template from './sw-cms-el-netzp-powerpack6-tabs.html.twig';
import './sw-cms-el-netzp-powerpack6-tabs.scss';

Component.register('sw-cms-el-netzp-powerpack6-tabs', {
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
        createdComponent()
        {
            this.initElementConfig('netzp-powerpack6-tabs');
        }
    }
});
