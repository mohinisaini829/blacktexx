const { Component } = Shopware;
import template from './sw-cms-el-preview-netzp-powerpack6-card.html.twig';
import './sw-cms-el-preview-netzp-powerpack6-card.scss';

Component.register('sw-cms-el-preview-netzp-powerpack6-card', {
    template,

    computed: {
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        }
    }
});
