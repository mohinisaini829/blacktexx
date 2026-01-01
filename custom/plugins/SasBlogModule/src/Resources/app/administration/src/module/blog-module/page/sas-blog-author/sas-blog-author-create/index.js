const { Component } = Shopware;

Component.extend('sas-blog-author-create', 'sas-blog-author-detail', {
    methods: {
        createdComponent() {
            Shopware.Store.get('context').resetLanguageToDefault();

            this.blogAuthor = this.blogAuthorRepository.create(
                Shopware.Context.api,
            );
        },
    },
});
