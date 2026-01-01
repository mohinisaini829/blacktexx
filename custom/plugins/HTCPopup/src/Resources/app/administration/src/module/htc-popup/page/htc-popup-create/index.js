import template from './htc-popup-create.html.twig';

const { Component, Mixin } = Shopware;
const { EntityCollection, Criteria } = Shopware.Data;

Component.register('htc-popup-create', {
    template,

    inject: [
        'repositoryFactory'
    ],

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('placeholder')
    ],

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    data() {
        return {
            popup: null,
            repository: null,
            isLoading: false,
            processSuccess: false,
            customerGroups: null,
            pageVisibleOptions: [
                { value: 0, label: this.$tc('htc-popup-snp.popup.detail.visibleOptionLabels.homePageOptionLabel') },
                { value: 1, label: this.$tc('htc-popup-snp.popup.detail.visibleOptionLabels.productPageOptionLabel') },
                { value: 2, label: this.$tc('htc-popup-snp.popup.detail.visibleOptionLabels.categoryPageOptionLabel') },
                { value: 3, label: this.$tc('htc-popup-snp.popup.detail.visibleOptionLabels.otherPageOptionLabel') }
            ],
            frequencyOptions: [
                { value: 0, label: this.$tc('htc-popup-snp.popup.detail.frequencyOptionLabels.alwaysLabel') },
                { value: 1, label: this.$tc('htc-popup-snp.popup.detail.frequencyOptionLabels.onlyOneLabel') },
            ],
            alignContentOptions: [
                { value: 1, label: this.$tc('htc-popup-snp.popup.detail.alignContentOptions.leftTopLabel') },
                { value: 2, label: this.$tc('htc-popup-snp.popup.detail.alignContentOptions.leftCenterLabel') },
                { value: 3, label: this.$tc('htc-popup-snp.popup.detail.alignContentOptions.leftBottomLabel') },
                { value: 4, label: this.$tc('htc-popup-snp.popup.detail.alignContentOptions.centerTopLabel') },
                { value: 5, label: this.$tc('htc-popup-snp.popup.detail.alignContentOptions.centerCenterLabel') },
                { value: 6, label: this.$tc('htc-popup-snp.popup.detail.alignContentOptions.centerBottomLabel') },
                { value: 7, label: this.$tc('htc-popup-snp.popup.detail.alignContentOptions.rightTopLabel') },
                { value: 8, label: this.$tc('htc-popup-snp.popup.detail.alignContentOptions.rightCenterLabel') },
                { value: 9, label: this.$tc('htc-popup-snp.popup.detail.alignContentOptions.rightBottomLabel') },

            ]
        };
    },

    created() {
        this.repository = this.repositoryFactory.create('htc_popup');
        this.customerGroups = new EntityCollection(
            this.customerGroupRepository.route,
            this.customerGroupRepository.entityName,
            Shopware.Context.api
        );

        Promise.resolve(this.getPopup()).then(this.checkLanguage);
    },

    computed: {

        customerGroupRepository() {
            return this.repositoryFactory.create('customer_group');
        },

        customerGroupIds: {

            get() {
                if (!this.popup || !this.popup.customerGroupIds) {
                    return [];
                }
                return this.popup.customerGroupIds.split(",");
            },

            set(customerGroupIds) {
                this.popup.customerGroupIds = customerGroupIds.join(',');
            }
        },

        visiblePages: {
            
            get() {
                if (!this.popup || !this.popup.visibleOn) {
                    return [];
                }
                return this.popup.visibleOn.split(",").map(option => parseInt(option));
            },

            set(value) {
                if (value == null && value.length == 0) {
                    this.popup.visibleOn = null;
                }
                this.popup.visibleOn = value.join(',');
            }
        },
    },

    methods: {

        getPopup() {
            this.popup = this.repository.create(Shopware.Context.api);
        },

        checkLanguage() {
            if (this.popup.isNew()) {
                const isSystemDefaultLang = Shopware.State.getters['context/isSystemDefaultLanguage'];

                if (!isSystemDefaultLang) {
                    Shopware.State.commit('context/resetLanguageToDefault');
                    console.log('after commit', Shopware.State.getters['context/isSystemDefaultLanguage']);
                }

                // this.$nextTick(() => {
                //     // This is a hack to update the language switch,
                //     // since it is not reactive to the global language state
                //     this.$refs.langSwitch.createdComponent();
                // });
            }
        },

        onChangeLanguage() {
            this.getPopup();
        },

        abortOnLanguageChange({ oldLanguageId, newLanguageId }) {
            if (oldLanguageId === newLanguageId) return false;
            return this.repository.hasChanges(this.popup);
        },

        saveOnLanguageChange() {
            return this.onClickSave();
        },

        setCustomerGroupIds(customerGroups) {
            this.customerGroupIds = customerGroups.getIds();
            this.customerGroups = customerGroups;
        },

        onClickSave() {
            this.isLoading = true;
            this.repository
                .save(this.popup, Shopware.Context.api)
                .then(() => {
                    this.$router.push({
                       name: 'htc.popup.detail',
                       params: { id: this.popup.id },
                    });
                    this.createNotificationSuccess({
                        message: this.$tc('htc-popup-snp.general.successSaveMessage')
                    });
                })
                .catch((e) => {
                    this.createNotificationError({
                        title: this.$tc('htc-popup-snp.general.errorTitle'),
                        message: this.$tc('htc-popup-snp.general.errorMessage')
                    });
                })
                .finally(() => {
                    this.isLoading = false;
                });
        },

        saveFinish() {
            this.processSuccess = false;
        }
    }

});