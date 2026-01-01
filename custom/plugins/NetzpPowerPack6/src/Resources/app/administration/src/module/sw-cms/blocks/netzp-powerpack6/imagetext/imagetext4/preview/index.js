import template from './sw-cms-preview-netzp-powerpack6-imagetext4.html.twig';
import './sw-cms-preview-netzp-powerpack6-imagetext4.scss';


const { Component } = Shopware;

Component.register('sw-cms-preview-netzp-powerpack6-imagetext4', {
    template,

    computed: {
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        }
    }
});
