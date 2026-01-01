const { Component } = Shopware;
import template from './sw-cms-preview-netzp-powerpack6-map.html.twig';
import './sw-cms-preview-netzp-powerpack6-map.scss';

Component.register('sw-cms-preview-netzp-powerpack6-map', {
    template,

    computed: {
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        }
    }
});
