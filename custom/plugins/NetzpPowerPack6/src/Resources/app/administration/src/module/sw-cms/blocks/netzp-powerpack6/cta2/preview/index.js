const { Component } = Shopware;
import template from './sw-cms-preview-netzp-powerpack6-cta2.html.twig';
import './sw-cms-preview-netzp-powerpack6-cta2.scss';

Component.register('sw-cms-preview-netzp-powerpack6-cta2', {
    template,

    computed: {
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        }
    }
});
