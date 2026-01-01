import template from './sw-cms-el-component-finishing-price-table.html.twig';
import './sw-cms-el-component-finishing-price-table.scss';

const {Component, Mixin} = Shopware;

Component.register('sw-cms-el-component-finishing-price-table', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('finishing-price-table');
            this.initElementData('finishing-price-table');
        },
    },
});
