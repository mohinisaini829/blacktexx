import template from './sw-cms-el-config-myfav-special-offers.html.twig';

const {Component} = Shopware;
const { Criteria, EntityCollection } = Shopware.Data;

Component.extend('sw-cms-el-config-myfav-special-offers', 'sw-cms-el-config-product-slider', {
    template,

    methods: {
        createdComponent() {
            this.initElementConfig('myfav-special-offers');

            this.productCollection = new EntityCollection('/product', 'product', Shopware.Context.api);

            if (this.element.config.products.value.length <= 0) {
                return;
            }

            if (this.element.config.products.source === 'product_stream') {
                this.loadProductStream();
            } else {
                // We have to fetch the assigned entities again
                // ToDo: Fix with NEXT-4830
                const criteria = new Criteria(1, 100);
                criteria.addAssociation('cover');
                criteria.addAssociation('options.group');
                criteria.setIds(this.element.config.products.value);

                this.productRepository
                    .search(criteria, Object.assign({}, Shopware.Context.api, { inheritance: true }))
                    .then((result) => {
                        this.productCollection = result;
                    });
            }
        }
    },
});

