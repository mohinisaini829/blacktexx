const { Component } = Shopware;
import template from './sw-cms-el-preview-netzp-powerpack6-testimonial.html.twig';
import './sw-cms-el-preview-netzp-powerpack6-testimonial.scss';

Component.register('sw-cms-el-preview-netzp-powerpack6-testimonial', {
    template,

    computed: {
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        }
    }
});
