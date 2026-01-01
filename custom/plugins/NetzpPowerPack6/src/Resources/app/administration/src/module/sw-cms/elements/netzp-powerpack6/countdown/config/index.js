const { Component, Mixin } = Shopware;
import template from './sw-cms-el-config-netzp-powerpack6-countdown.html.twig';

Component.register('sw-cms-el-config-netzp-powerpack6-countdown', {
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
            this.initElementConfig('netzp-powerpack6-countdown');
        }
    }
});
