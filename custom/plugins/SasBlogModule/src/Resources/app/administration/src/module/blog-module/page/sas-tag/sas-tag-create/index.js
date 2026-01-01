const { Component } = Shopware;

Component.extend('sas-tag-create', 'sas-tag-detail', {
    methods: {
        createdComponent() {
            Shopware.Store.get('context').resetLanguageToDefault();

            this.tag = this.tagRepository.create(Shopware.Context.api);
        },
    },
});
