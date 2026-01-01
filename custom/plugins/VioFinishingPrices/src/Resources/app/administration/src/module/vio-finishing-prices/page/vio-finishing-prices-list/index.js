import template from './vio-finishing-prices-list.html.twig';

const {Component, Mixin} = Shopware;
const {Criteria} = Shopware.Data;

Component.register('vio-finishing-prices-list', {
    template,

    inject: [
        'repositoryFactory',
        'acl'
    ],

    mixins: [
        Mixin.getByName('listing')
    ],

    data() {
        return {
            repository: null,
            finishingPrices: null,
            total: 0,
            isLoading: false
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
                property: 'active',
                dataIndex: 'active',
                label: this.$t('vio-finishing-prices.list.columnActive'),
                inlineEdit: 'boolean',
                allowResize: true,
            },{
                property: 'name',
                dataIndex: 'name',
                label: this.$t('vio-finishing-prices.list.columnName'),
                inlineEdit: 'string',
                allowResize: true,
            },];
        }
    },

    methods: {
        onChangeLanguage(languageId) {
            this.getList(languageId);
        },

        getList() {
            this.isLoading = true;
            let criteria = new Criteria();
            criteria
                .addSorting(Criteria.sort('position', 'ASC'))
                .addSorting(Criteria.sort('name', 'ASC'));

            if(this.repository !== null) {
                this.repository
                    .search(criteria, Shopware.Context.api)
                    .then((result) => {
                        this.total = result.total;
                        this.selection = {};
                        this.finishingPrices = result;
                        this.isLoading = false;
                    });
            }
        },

        updateTotal({total}) {
            this.total = total;
        },
    },

    created() {
        this.repository = this.repositoryFactory.create('finishing_price_table');
        this.getList();
    }
});
