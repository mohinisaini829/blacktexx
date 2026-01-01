const { Component } = Shopware;
import template from './sw-cms-preview-netzp-powerpack6-imagecompare.html.twig';
import './sw-cms-preview-netzp-powerpack6-imagecompare.scss';

Component.register('sw-cms-preview-netzp-powerpack6-imagecompare', {
    template,

    computed: {
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        }
    }
});
