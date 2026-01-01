import template from './sas-product-detail-blog-assignment.html.twig';

const { Component } = Shopware;

Component.register('sas-product-detail-blog-assignment', {
    template,

    computed: {
        product() {
            return Shopware.Store.get('swProductDetail').product;
        },

        parentProduct() {
            return Shopware.Store.get('swProductDetail').parentProduct;
        },

        isStoreLoading() {
            return Shopware.Store.get('swProductDetail').isLoading;
        },
    },

    watch: {
        isStoreLoading: {
            handler() {
                if (this.isStoreLoading === false) {
                    this.product.customFields = this.product.customFields || {};
                }
            },
            immediate: true,
        },
    },
});
