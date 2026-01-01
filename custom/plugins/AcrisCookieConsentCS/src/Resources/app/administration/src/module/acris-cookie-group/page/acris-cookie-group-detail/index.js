import template from './acris-cookie-group-detail.html.twig';

const { Component } = Shopware;
const { StateDeprecated } = Shopware;
const { Mixin } = Shopware;

Component.register('acris-cookie-group-detail', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('listing'),
        Mixin.getByName('notification'),
        Mixin.getByName('placeholder')
    ],

    data() {
        return {
            item: null,
            isLoading: false,
            isSaveSuccessful: false,
            repository: null
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    updated() {
        this.componentUpdated();
    },

    created() {
        this.createdComponent();
    },

    computed: {
        languageStore() {
            return StateDeprecated.getStore('language');
        }
    },

    methods: {
        getCookieGroup() {
            this.isLoading = true;
            this.repository
                .get(this.$route.params.id, Shopware.Context.api)
                .then((entity) => {
                    this.item = entity;
                });
        },

        createdComponent() {
            this.repository = this.repositoryFactory.create('acris_cookie_group');
            this.getCookieGroup();
            this.initializeFurtherProductComponents();
        },

        initializeFurtherProductComponents() {
            this.isLoading = false;
        },

        saveFinish() {
            this.isSaveSuccessful = false;
        },

        onSave() {
            const titleSaveError = this.$tc('acris-cookie-group.detail.notificationSaveErrorMessageTitle');
            const messageSaveError = this.$tc('acris-cookie-group.detail.notificationSaveErrorMessage', { title: this.item.title, description: this.item.description }, 0);
            const titleSaveSuccess = this.$tc('acris-cookie-group.detail.notificationSaveSuccessMessageTitle');
            const messageSaveSuccess = this.$tc('acris-cookie-group.detail.notificationSaveSuccessMessage', { title: this.item.title, description: this.item.description }, 0);

            this.isSaveSuccessful = false;
            this.isLoading = true;

            this.repository
                .save(this.item)
                .then(() => {
                    this.getCookieGroup();
                    this.createNotificationSuccess({
                        title: titleSaveSuccess,
                        message: messageSaveSuccess
                    });

                    this.isLoading = false;
                    this.isSaveSuccessful = true;
                }).catch((exception) => {
                    this.createNotificationError({
                        title: titleSaveError,
                        message: messageSaveError
                    });
                    this.isLoading = false;
                    throw exception;
                });
        },

        componentUpdated() {
            if (this.item) {
                if (this.titleSelected !== true) {
                    this.userInputValue = this.item.title;
                }
                this.titleSelected = false;

                if (this.descriptionSelected !== true) {
                    this.userInputValue = this.item.description;
                }
                this.descriptionSelected = false;
            }
        },

        onChangeLanguage() {
            this.getCookieGroup();
            this.initializeFurtherProductComponents();
        },

        onChange(collection) {
            this.item.title = collection;
            this.item.description = collection;
        },
    }
});
