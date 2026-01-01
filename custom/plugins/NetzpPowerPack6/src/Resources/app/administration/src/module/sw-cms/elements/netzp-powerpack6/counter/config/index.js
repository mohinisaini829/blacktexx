const { Component, Mixin } = Shopware;
import template from './sw-cms-el-config-netzp-powerpack6-counter.html.twig';

Component.register('sw-cms-el-config-netzp-powerpack6-counter', {
    template,

    inject: [
        'repositoryFactory'
    ],

    mixins: [
        Mixin.getByName('cms-element')
    ],

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('netzp-powerpack6-counter');
        }
    }
});
