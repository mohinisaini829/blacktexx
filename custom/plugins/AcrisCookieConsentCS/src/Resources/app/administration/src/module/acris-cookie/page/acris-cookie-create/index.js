import template from './acris-cookie-create.html.twig';

const { Component } = Shopware;
const utils = Shopware.Utils;

Component.extend('acris-cookie-create', 'acris-cookie-detail', {
    template,

    beforeRouteEnter(to, from, next) {
        if (to.name.includes('acris.cookie.create') && !to.params.id) {
            to.params.id = utils.createId();
            to.params.newItem = true;
        }

        next();
    },

    methods: {
        getCookie() {
            this.item = this.repository.create(Shopware.Context.api);
            this.item.scriptPosition = 'body_end';
        },

        createdComponent() {
            if (!Shopware.State.getters['context/isSystemDefaultLanguage']) {
                Shopware.State.commit('context/resetLanguageToDefault');
            }

            this.$super('createdComponent');
        },


        onClickSave() {
            this.isLoading = true;
            const titleSaveError = this.$tc('acris-cookie.detail.notificationSaveErrorMessageTitle');
            const messageSaveError = this.$tc('acris-cookie.detail.notificationSaveErrorMessage', { title: this.item.title, description: this.item.description }, 0);
            const titleSaveSuccess = this.$tc('acris-cookie.detail.notificationSaveSuccessMessageTitle');
            const messageSaveSuccess = this.$tc('acris-cookie.detail.notificationSaveSuccessMessage', { title: this.item.title, description: this.item.description }, 0);
            this.item.unknown = false;

            this.repository
                .save(this.item, Shopware.Context.api)
                .then(() => {
                    this.isLoading = false;
                    this.createNotificationSuccess({
                        title: titleSaveSuccess,
                        message: messageSaveSuccess
                    });
                    this.$router.push({ name: 'acris.cookie.detail', params: { id: this.item.id } });
                }).catch(() => {
                    this.isLoading = false;
                    this.createNotificationError({
                        title: titleSaveError,
                        message: messageSaveError
                    });
                });
        }
    }
});
