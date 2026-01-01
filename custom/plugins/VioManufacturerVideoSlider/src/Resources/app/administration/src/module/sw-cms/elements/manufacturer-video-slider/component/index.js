import template from './sw-cms-el-component-manufacturer-video-slider.html.twig';
import './sw-cms-el-component-manufacturer-video-slider.scss';

const {Component, Mixin} = Shopware;

Component.register('sw-cms-el-component-manufacturer-video-slider', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('manufacturer-video-slider');
            this.initElementData('manufacturer-video-slider');
        },
    },
});
