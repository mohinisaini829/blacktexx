import template from './sas-tag-list.html.twig';
import './sas-tag-list.scss';

const { Component, Mixin } = Shopware;

const Criteria = Shopware.Data.Criteria;

Component.register('sas-tag-list', {
    template,

    inject: ['repositoryFactory'],

    mixins: [Mixin.getByName('notification'), Mixin.getByName('listing')],

    data() {
        return {
            tags: null,
            total: 0,
            isLoading: true,
            currentLanguageId: Shopware.Context.api.languageId,
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    created() {
        this.getList();
    },

    computed: {
        tagRepository() {
            return this.repositoryFactory.create('sas_tag');
        },

        columns() {
            return [
                {
                    property: 'name',
                    label: 'sas-tag.list.table.name',
                    align: 'left',
                    inlineEdit: 'string',
                    allowResize: false,
                    primary: true,
                    dataIndex: 'name',
                },
            ];
        },
    },

    methods: {
        changeLanguage(newLanguageId) {
            this.currentLanguageId = newLanguageId;
            this.getList();
        },

        getList() {
            this.isLoading = true;
            const criteria = new Criteria(this.page, this.limit);

            return this.tagRepository
                .search(criteria, Shopware.Context.api)
                .then((result) => {
                    this.total = result.total;
                    this.tags = result;
                    this.isLoading = false;
                });
        },
    },
});
