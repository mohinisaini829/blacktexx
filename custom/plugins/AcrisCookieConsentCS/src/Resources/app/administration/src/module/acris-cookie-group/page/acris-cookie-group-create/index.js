import template from './acris-cookie-group-create.html.twig';

const { Component } = Shopware;
const utils = Shopware.Utils;

Component.extend('acris-cookie-group-create', 'acris-cookie-group-detail', {
    template,

    beforeRouteEnter(to, from, next) {
        if (to.name.includes('acris.cookie.group.create') && !to.params.id) {
            to.params.id = utils.createId();
            to.params.newItem = true;
        }

        next();
    },

    methods: {
        createdComponent() {
            if (!Shopware.State.getters['context/isSystemDefaultLanguage']) {
                Shopware.State.commit('context/resetLanguageToDefault');
            }

            this.$super('createdComponent');
        },

        getCookieGroup() {
            this.item = this.repository.create(Shopware.Context.api);
        },

        saveFinish() {
            this.isSaveSuccessful = false;
            this.$router.push({ name: 'acris.cookie.group.detail', params: { id: this.item.id } });
        },

        onSave() {
            const titleSaveError = this.$tc('acris-cookie-group.detail.notificationSaveErrorMessageTitle');
            const messageSaveError = this.$tc('acris-cookie-group.detail.notificationSaveErrorMessage', { title: this.item.title, description: this.item.description }, 0);
            const titleSaveSuccess = this.$tc('acris-cookie-group.detail.notificationSaveSuccessMessageTitle');
            const messageSaveSuccess = this.$tc('acris-cookie-group.detail.notificationSaveSuccessMessage', { title: this.item.title, description: this.item.description }, 0);

            this.isSaveSuccessful = false;
            this.isLoading = true;

            this.repository
                .save(this.item, Shopware.Context.api)
                .then(() => {
                    this.createNotificationSuccess({
                        title: titleSaveSuccess,
                        message: messageSaveSuccess
                    });

                    this.isLoading = false;
                    this.$router.push({ name: 'acris.cookie.group.detail', params: { id: this.item.id } });
                }).catch(() => {
                    this.createNotificationError({
                        title: titleSaveError,
                        message: messageSaveError
                    });
                    this.isLoading = false;
                });
        },
    }
});
