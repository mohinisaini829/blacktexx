import template from './acris-cookie-detail.html.twig';

const { Component } = Shopware;
const { StateDeprecated } = Shopware;
const { Mixin } = Shopware;
const { Criteria } = Shopware.Data;

const GoogleCookieConsentModeValues =
    ['ad_storage', 'ad_user_data', 'ad_personalization', 'analytics_storage',
        'functionality_storage', 'personalization_storage', 'security_storage'];

Component.register('acris-cookie-detail', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('listing'),
        Mixin.getByName('notification'),
        Mixin.getByName('placeholder')
    ],

    data() {
        return {
            cookieGroups: [],
            item: null,
            isLoading: false,
            processSuccess: false,
            repository: null
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        languageStore() {
            return StateDeprecated.getStore('language');
        },
        cookieGroupsRepository() {
            return this.repositoryFactory.create('acris_cookie_group');
        },
        cookieGroupOptions() {
            const options = [];
            this.initializeFurtherProductComponents();
            this.cookieGroups.forEach((cookieGroup) => {
                options.push({
                    label: cookieGroup.translated.title,
                    value: cookieGroup.id
                })
            })
            return options;
        },
        scriptPositionOptions() {
            const options = [];
            options.push({
                label: this.$tc('acris-cookie.detail.fieldScripPositionOptionHead'),
                value: 'head'
            })
            options.push({
                label: this.$tc('acris-cookie.detail.fieldScripPositionOptionBodyStart'),
                value: 'body_start'
            })
            options.push({
                label: this.$tc('acris-cookie.detail.fieldScripPositionOptionBodyEnd'),
                value: 'body_end'
            })
            return options;
        },
        googleCookieConsentModeOptions() {
            const options = [];
            GoogleCookieConsentModeValues.forEach((option, idx) => {
                options.push({
                    label: this.$tc(`acris-cookie.detail.googleCookieConsentModeOption${idx + 1}`),
                    value: option
                })
            })
            return options;
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        getCookie() {
            this.repository
                .get(this.$route.params.id, Shopware.Context.api)
                .then((entity) => {
                    this.item = entity;
                    if (this.item.unknown === true) {
                        if (!Shopware.State.getters['context/isSystemDefaultLanguage']) {
                            Shopware.State.commit('context/resetLanguageToDefault');
                        }
                    }
                });
        },

        onClickSave() {
            this.isLoading = true;
            const titleSaveError = this.$tc('acris-cookie.detail.notificationSaveErrorMessageTitle');
            const messageSaveError = this.$tc('acris-cookie.detail.notificationSaveErrorMessage', { title: this.item.title, description: this.item.description }, 0);
            const titleSaveSuccess = this.$tc('acris-cookie.detail.notificationSaveSuccessMessageTitle');
            const messageSaveSuccess = this.$tc('acris-cookie.detail.notificationSaveSuccessMessage', { title: this.item.title, description: this.item.description }, 0);

            if (this.item.unknown) {
                delete this.item.unknown;
            }

            this.repository
                .save(this.item, Shopware.Context.api)
                .then(() => {
                    this.getCookie();
                    this.isLoading = false;
                    this.processSuccess = true;
                    this.createNotificationSuccess({
                        title: titleSaveSuccess,
                        message: messageSaveSuccess
                    });
                }).catch(() => {
                    this.isLoading = false;
                    this.createNotificationError({
                        title: titleSaveError,
                        message: messageSaveError
                    });
                });
        },

        saveFinish() {
            this.processSuccess = false;
        },

        createdComponent() {
            this.repository = this.repositoryFactory.create('acris_cookie');
            this.getCookie();
            this.initializeFurtherProductComponents();
        },

        initializeFurtherProductComponents() {
            const criteria = new Criteria(1, 100);

            this.cookieGroupsRepository.search(criteria, Shopware.Context.api).then((searchResult) => {
                this.cookieGroups = searchResult;
            });
            this.isLoading = false;
        },

        onChangeLanguage() {
            this.getCookie();
        },

        onChange(collection) {
            this.item.cookieId = collection;
            this.item.active = collection;
            this.item.cookieGroupId = collection;
            this.item.title = collection;
            this.item.provider = collection;
            this.item.description = collection;
            this.item.salesChannels = collection;
            this.item.script = collection;
            this.item.scriptPosition = collection;
            this.item.googleCookieConsentMode = collection;
        },

        onChangeSalesChannels(collection) {
            this.item.salesChannels = collection;
        },
    }
});
