import template from './vio-finishing-prices-detail.html.twig';

const { Component, Mixin } = Shopware;

Component.register('vio-finishing-prices-detail', {
    template,

    inject: [
        'repositoryFactory',
        'acl'
    ],

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('placeholder')
    ],

    shortcuts: {
        'SYSTEMKEY+S': 'onSave',
        ESCAPE: 'onCancel',
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    data() {
        return {
            finishingPrice: null,
            isLoading: false,
            processSuccess: false,
            repository: null
        };
    },

    created() {
        Shopware.State.commit('context/resetLanguageToDefault');
        this.repository = this.repositoryFactory.create('finishing_price_table');
        this.getFinishingPrice();
    },

    methods: {
        getFinishingPrice() {
            this.isLoading = true;
            this.repository
                .get(this.$route.params.id, Shopware.Context.api)
                .then((entity) => {
                    this.finishingPrice = entity;
                    this.isLoading = false;
                });
        },

        abortOnLanguageChange() {
            return this.repository.hasChanges(this.finishingPrice);
        },

        saveOnLanguageChange() {
            return this.onClickSave();
        },

        onChangeLanguage() {
            this.getFinishingPrice();
        },

        onClickSave() {
            this.isLoading = true;

            this.repository
                .save(this.finishingPrice, Shopware.Context.api)
                .then(() => {
                    this.getFinishingPrice();
                    this.isLoading = false;
                    this.processSuccess = true;
                }).catch((exception) => {
                this.isLoading = false;
                this.createNotificationError({
                    title: this.$t('vio-finishing-prices.detail.errorTitle'),
                    message: exception.message
                });
            });
        },

        saveFinish() {
            this.processSuccess = false;
        },

        onCancel() {
            this.$router.push({ name: 'vio.finishing.prices.list' });
        },
    }
});
