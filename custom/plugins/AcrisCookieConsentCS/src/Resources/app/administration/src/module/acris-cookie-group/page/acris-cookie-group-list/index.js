import template from './acris-cookie-group-list.html.twig';
import './acris-cookie-group-list.scss';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('acris-cookie-group-list', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('listing'),
        Mixin.getByName('notification'),
        Mixin.getByName('placeholder')
    ],

    data() {
        return {
            items: null,
            repository: null,
            isLoading: false,
            showDeleteModal: false,
            sortBy: 'createdAt',
            sortDirection: 'DESC',
            total: 0,
            allowDelete: true
        };
    },


    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        columns() {
            return [{
                property: 'title',
                label: 'acris-cookie-group.list.columnTitle',
                routerLink: 'acris.cookie.group.detail',
                primary: true,
                sortable: true,
                allowResize: true
            }, {
                property: 'description',
                label: 'acris-cookie-group.list.columnDescription',
                routerLink: 'acris.cookie.group.detail',
                sortable: true,
                allowResize: true
            }];
        },

        cookieGroupCriteria() {
            const criteria = new Criteria(this.page, this.limit);

            criteria.setTerm(this.term);

            criteria
                .addSorting(Criteria.sort(this.sortBy, this.sortDirection))
                .addSorting(Criteria.sort('createdAt', 'DESC'));

            return criteria;
        },

        entityRepository() {
            return this.repositoryFactory.create('acris_cookie_group');
        }
    },

    watch: {
        cookieGroupCriteria: {
            handler() {
                this.getList();
            },
            deep: true,
        },

        selection: {
            handler() {
                this.allowBulkDelete();
            },
            deep: true,
        }
    },

    created() {
        this.createComponent();
    },

    methods: {
        createComponent() {
            this.getList();
        },

        getList() {
            this.isLoading = true;

            this.entityRepository
                .search(this.cookieGroupCriteria)
                .then((result) => {
                    this.items = result;
                    this.total = result.total;
                    this.selection = {};
                }).finally(() => {
                    this.isLoading = false;
                });
        },

        allowBulkDelete() {
            this.allowDelete = !Object.values(this.selection).some((selection) => {
                return selection.isDefault;
            });
        },

        onDelete(id) {
            this.showDeleteModal = id;
        },

        onCloseDeleteModal() {
            this.showDeleteModal = false;
        },

        onConfirmDelete(id) {
            this.showDeleteModal = false;

            return this.entityRepository.delete(id).then(() => {
                this.getList();
            });
        },

        onChangeLanguage() {
            this.getList();
        },

        onChange(collection) {
            this.item.title = collection;
        },
    }
});
