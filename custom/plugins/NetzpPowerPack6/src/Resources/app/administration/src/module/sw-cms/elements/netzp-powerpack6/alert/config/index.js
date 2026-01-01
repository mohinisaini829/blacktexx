const { Component, Mixin } = Shopware;
import template from './sw-cms-el-config-netzp-powerpack6-alert.html.twig';

Component.register('sw-cms-el-config-netzp-powerpack6-alert', {
    template,

    inject: [
        'repositoryFactory'
    ],

    mixins: [
        Mixin.getByName('cms-element')
    ],

    computed: {
        optionsAlertType() {
            return [
                {
                    value: 0,
                    name: this.$tc('sw-cms.netzp-powerpack6.elements.alert.config.alertType.info')
                },
                {
                    value: 1,
                    name: this.$tc('sw-cms.netzp-powerpack6.elements.alert.config.alertType.warning')
                },
                {
                    value: 2,
                    name: this.$tc('sw-cms.netzp-powerpack6.elements.alert.config.alertType.error')
                },
                {
                    value: 3,
                    name: this.$tc('sw-cms.netzp-powerpack6.elements.alert.config.alertType.success')
                },
            ];
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('netzp-powerpack6-alert');
        }
    }
});
