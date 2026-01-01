const { Component } = Shopware;

Component.extend('vio-finishing-prices-create', 'vio-finishing-prices-detail', {
    methods: {
        getFinishingPrice() {
            this.finishingPrice = this.repository.create(Shopware.Context.api);

            // Set default values
            this.finishingPrice.active = true; // <-- default true
        },

        onClickSave() {
            this.isLoading = true;

            this.repository
                .save(this.finishingPrice, Shopware.Context.api)
                .then(() => {
                    this.isLoading = false;
                    this.$router.push({ 
                        name: 'vio.finishing.prices.detail', 
                        params: { id: this.finishingPrice.id } 
                    });
                })
                .catch((exception) => {
                    this.isLoading = false;
                    this.createNotificationError({
                        title: this.$t('vio-finishing-prices.detail.errorTitle'),
                        message: exception
                    });
                });
        }
    }
});
