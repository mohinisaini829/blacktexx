import template from './acris-cookie-list.html.twig';
import './acris-cookie-list.scss';

const { Component } = Shopware;
const { Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('acris-cookie-list', {
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
            groups: null,
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
                property: 'cookieId',
                inlineEdit: 'string',
                label: 'acris-cookie.list.columnCookieId',
                routerLink: 'acris.cookie.detail',
                primary: true,
                sortable: true,
                allowResize: true
            }, {
                property: 'title',
                inlineEdit: 'string',
                label: 'acris-cookie.list.columnTitle',
                routerLink: 'acris.cookie.detail',
                sortable: true,
                allowResize: true
            }, {
                property: 'description',
                inlineEdit: 'string',
                label: 'acris-cookie.list.columnDescription',
                routerLink: 'acris.cookie.detail',
                sortable: true,
                allowResize: true
            }, {
                property: 'cookieGroup.title',
                label: 'acris-cookie.list.columnCookieGroup',
                sortable: true,
                allowResize: true
            }, {
                property: 'provider',
                label: 'acris-cookie.list.providers',
                sortable: true,
                allowResize: true
            }, {
                property: 'isDefault',
                inlineEdit: 'boolean',
                label: 'acris-cookie.list.columnDefault',
                align: 'center',
                allowResize: true
            }, {
                property: 'active',
                inlineEdit: 'boolean',
                label: 'acris-cookie.list.columnActive',
                align: 'center',
                allowResize: true
            }, {
                property: 'createdAt',
                dataIndex: 'createdAt',
                allowResize: true,
                label: 'acris-cookie.list.columnCreatedAt',
            }];
        },

        entityRepository() {
            return this.repositoryFactory.create('acris_cookie');
        },

        defaultCriteria() {
            const defaultCriteria = new Criteria(this.page, this.limit);

            defaultCriteria.setTerm(this.term);

            defaultCriteria.addAssociation('cookieGroup');

            defaultCriteria
                .addSorting(Criteria.sort(this.sortBy, this.sortDirection))
                .addSorting(Criteria.sort('createdAt', 'DESC'));

            return defaultCriteria;
        }
    },

    watch: {
        defaultCriteria: {
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

    methods: {
        getList() {
            this.isLoading = true;

            this.entityRepository.search(this.defaultCriteria)
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
            this.item.cookieId = collection;
            this.item.title = collection;
            this.item.description = collection;
            this.item.provider = collection;
            this.item.isDefault = collection;
            this.item.active = collection;
        },
    }
});
