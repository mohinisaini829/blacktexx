import template from './sas-cms-product-assignment-modal.html.twig';
import './sas-cms-product-assignment-modal.scss';

const { Context, Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('sas-cms-product-assignment-modal', {
    template,

    inject: ['repositoryFactory'],

    props: {
        show: {
            type: Boolean,
            required: true,
            default: false,
        },
        assignedProducts: {
            type: Array,
            required: true,
        },
    },

    data() {
        return {
            isLoadingData: false,
            variantNames: {},
        };
    },

    computed: {
        isLoadingGrid() {
            return this.isLoadingData;
        },

        assignmentRepository() {
            return this.repositoryFactory.create(
                this.assignedProducts.entity,
                this.assignedProducts.source,
            );
        },

        productRepository() {
            return this.repositoryFactory.create('product');
        },

        searchCriteria() {
            const criteria = new Criteria(1, 25);

            criteria.addFilter(
                Criteria.multi('or', [
                    Criteria.equals('childCount', 0),
                    Criteria.not('and', [Criteria.equals('parentId', null)]),
                ]),
            );

            criteria.addAssociation('options.group');

            return criteria;
        },

        searchContext() {
            return {
                ...Context.api,
                inheritance: true,
            };
        },

        total() {
            if (
                !this.assignedProducts ||
                !Array.isArray(this.assignedProducts)
            ) {
                return 0;
            }

            return this.assignedProducts.length;
        },

        assignedProductColumns() {
            return [
                {
                    property: 'product.translated.name',
                    label: this.$tc('sw-product.list.columnName'),
                    primary: true,
                    allowResize: true,
                    sortable: false,
                },
                {
                    property: 'productNumber',
                    label: this.$tc('sw-product.list.columnProductNumber'),
                    allowResize: true,
                    sortable: false,
                },
            ];
        },

        variantProductIds() {
            const variantProductIds = [];

            this.assignedProducts.forEach((item) => {
                if (!item.parentId || item.translated.name || item.name) {
                    return;
                }

                variantProductIds.push(item.id);
            });

            return variantProductIds;
        },

        variantCriteria() {
            const criteria = new Criteria(1, 25);
            criteria.setIds(this.variantProductIds);

            return criteria;
        },

        assetFilter() {
            return Shopware.Filter.getByName('asset');
        },
    },

    created() {
        if (this.variantProductIds.length === 0) {
            return;
        }

        this.productRepository
            .search(this.variantCriteria, { ...Context.api, inheritance: true })
            .then((variants) => {
                const variantNames = {};
                variants.forEach((variant) => {
                    variantNames[variant.id] = variant.translated.name;
                });
                this.variantNames = variantNames;
            });
    },

    methods: {
        onToggleProduct(productId) {
            if (productId === null) {
                return;
            }

            this.isLoadingData = true;
            const matchedAssignedProduct = this.assignedProducts.find(
                (assignedProduct) => {
                    return assignedProduct.id === productId;
                },
            );

            if (matchedAssignedProduct) {
                this.removeItem(matchedAssignedProduct);
                this.isLoadingData = false;
            } else {
                const criteria = new Criteria(1, 25);
                criteria.addAssociation('options.group');

                this.productRepository
                    .get(
                        productId,
                        { ...Context.api, inheritance: true },
                        criteria,
                    )
                    .then((product) => {
                        this.assignedProducts.add(product);
                        this.isLoadingData = false;
                    });
            }
        },

        removeItem(item) {
            this.assignedProducts.remove(item.id);
        },

        isSelected(item) {
            return this.assignedProducts.some((p) => p.id === item.id);
        },

        onSave() {
            this.$emit('modal-close');
        },
    },
});
