const { Component, Mixin } = Shopware;
import template from './sw-cms-el-config-netzp-powerpack6-collapse.html.twig';

Component.register('sw-cms-el-config-netzp-powerpack6-collapse', {
    template,

    inject: [
        'repositoryFactory'
    ],

    mixins: [
        Mixin.getByName('cms-element')
    ],

    data() {
        return {
            active: 'tab1'
        };
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('netzp-powerpack6-collapse');
        }
    }
});
