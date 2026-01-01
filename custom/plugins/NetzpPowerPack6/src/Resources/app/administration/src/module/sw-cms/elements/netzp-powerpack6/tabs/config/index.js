const { Component, Mixin } = Shopware;
import template from './sw-cms-el-config-netzp-powerpack6-tabs.html.twig';

Component.register('sw-cms-el-config-netzp-powerpack6-tabs', {
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
        createdComponent()
        {
            this.initElementConfig('netzp-powerpack6-tabs');
        },

        checkTabTitle(n)
        {
            if(this.element.config['tabtitle' + n].value.trim() === '') {
                this.$set(this.element.config['tabtitle' + n], 'value', 'Tab ' + n);
            }
        }
    }
});
