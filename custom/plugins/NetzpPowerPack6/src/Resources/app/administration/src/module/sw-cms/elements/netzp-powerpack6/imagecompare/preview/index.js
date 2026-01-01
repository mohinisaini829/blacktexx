const { Component } = Shopware;
import template from './sw-cms-el-preview-netzp-powerpack6-imagecompare.html.twig';
import './sw-cms-el-preview-netzp-powerpack6-imagecompare.scss';

Component.register('sw-cms-el-preview-netzp-powerpack6-imagecompare', {
    template,

    computed: {
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        }
    }
});
