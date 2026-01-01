import template from './sw-cms-preview-netzp-powerpack6-imagetext2.html.twig';
import './sw-cms-preview-netzp-powerpack6-imagetext2.scss';

const { Component } = Shopware;

Component.register('sw-cms-preview-netzp-powerpack6-imagetext2', {
    template,

    computed: {
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        }
    }
});
