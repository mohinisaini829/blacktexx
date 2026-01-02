import template from './brand-index.html.twig';
import './brand-index.scss';

const { Component, Mixin } = Shopware;
const { Criteria, EqualsFilter } = Shopware.Data;

Component.register('brand-index', {
    template,

    mixins: [
        Mixin.getByName('listing'),
        Mixin.getByName('notification'),
        Mixin.getByName('placeholder'),
    ],

    data() {
        return {
            brands: [],
            isLoading: false,
            sortBy: 'displayOrder',
            sortDirection: 'ASC',
            total: 0,
            limit: 25,
            page: 1,
            term: null,
            columns: [
                {
                    property: 'name',
                    dataIndex: 'name',
                    label: this.$tc('sw-santafatex.brands.index.columnName'),
                    routerLink: 'santafatex.brands.detail',
                    primary: true,
                    sortable: true,
                    allowSummarize: true,
                },
                {
                    property: 'description',
                    dataIndex: 'description',
                    label: this.$tc('sw-santafatex.brands.index.columnDescription'),
                    sortable: true,
                },
                {
                    property: 'active',
                    dataIndex: 'active',
                    label: this.$tc('sw-santafatex.brands.index.columnActive'),
                    sortable: true,
                    align: 'center',
                },
                {
                    property: 'displayOrder',
                    dataIndex: 'displayOrder',
                    label: this.$tc('sw-santafatex.brands.index.columnDisplayOrder'),
                    sortable: true,
                    align: 'right',
                },
                {
                    property: 'createdAt',
                    dataIndex: 'createdAt',
                    label: this.$tc('sw-santafatex.brands.index.columnCreatedAt'),
                    sortable: true,
                },
            ],
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    computed: {
        brandRepository() {
            return this.repositoryFactory.create('santafatex_brand');
        },

        listingColumns() {
            return this.columns;
        },
    },

    mounted() {
        this.getList();
    },

    methods: {
        getList() {
            this.isLoading = true;
            const criteria = new Criteria(this.page, this.limit);

            if (this.term) {
                criteria.addFilter(new EqualsFilter('name', this.term));
            }

            criteria.addSorting(Criteria.sort(this.sortBy, this.sortDirection));

            return this.brandRepository.search(criteria, Shopware.Context.api)
                .then((response) => {
                    this.brands = response;
                    this.total = response.total;
                    this.isLoading = false;
                    return response;
                })
                .catch(() => {
                    this.isLoading = false;
                });
        },

        onDelete(id) {
            this.showDeleteModal(id);
        },

        showDeleteModal(id) {
            this.$refs.confirmDeleteModal.confirmDelete(id);
        },

        onConfirmDelete(id) {
            this.brandRepository.delete(id, Shopware.Context.api)
                .then(() => {
                    this.getList();
                    this.createNotificationSuccess({
                        title: this.$tc('sw-santafatex.brands.notification.deleteSuccess'),
                        message: this.$tc('sw-santafatex.brands.notification.deleteSuccessMessage'),
                    });
                })
                .catch((exception) => {
                    this.createNotificationError({
                        title: this.$tc('sw-santafatex.brands.notification.deleteError'),
                        message: exception.response?.data?.message,
                    });
                });
        },

        onEdit(brand) {
            this.$router.push({
                name: 'santafatex.brands.detail',
                params: { id: brand.id },
            });
        },

        onCreate() {
            this.$router.push({
                name: 'santafatex.brands.create',
            });
        },

        onSearch(value) {
            this.term = value;
            this.page = 1;
            this.getList();
        },

        onPageChange(options) {
            this.page = options.page;
            this.limit = options.limit;
            this.getList();
        },

        onSort(options) {
            this.sortBy = options.sortBy;
            this.sortDirection = options.sortDirection;
            this.getList();
        },
    },
});
