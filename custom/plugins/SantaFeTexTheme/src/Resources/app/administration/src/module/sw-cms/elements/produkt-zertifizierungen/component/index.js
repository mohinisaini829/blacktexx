import template from './sw-cms-el-component-produkt-zertifizierungen.html.twig';
import './sw-cms-el-component-produkt-zertifizierungen.scss';

const {Component, Mixin} = Shopware;

Component.register('sw-cms-el-component-produkt-zertifizierungen', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementData('produkt-zertifizierungen');
        },
    },
});