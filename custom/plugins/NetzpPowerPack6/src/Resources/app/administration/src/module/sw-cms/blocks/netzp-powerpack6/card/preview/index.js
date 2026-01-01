const { Component } = Shopware;
import template from './sw-cms-preview-netzp-powerpack6-card.html.twig';
import './sw-cms-preview-netzp-powerpack6-card.scss';

Component.register('sw-cms-preview-netzp-powerpack6-card', {
    template,

    computed: {
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        }
    }
});
