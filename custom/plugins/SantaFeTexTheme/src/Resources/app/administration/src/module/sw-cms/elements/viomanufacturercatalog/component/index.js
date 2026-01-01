import template from './sw-cms-el-component-viomanufacturercatalog.html.twig';

const {Component, Mixin} = Shopware;

Component.register('sw-cms-el-component-viomanufacturercatalog', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('viomanufacturercatalog');
        },
    },
});