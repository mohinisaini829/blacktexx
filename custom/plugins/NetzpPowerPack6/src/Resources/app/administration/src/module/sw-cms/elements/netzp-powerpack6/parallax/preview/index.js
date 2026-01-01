const { Component } = Shopware;
import template from './sw-cms-el-preview-netzp-powerpack6-parallax.html.twig';
import './sw-cms-el-preview-netzp-powerpack6-parallax.scss';

Component.register('sw-cms-el-preview-netzp-powerpack6-parallax', {
    template,

    computed: {
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        }
    }
});
