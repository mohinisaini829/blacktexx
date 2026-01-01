import template from './sas-blog-create.html.twig';

const { Component } = Shopware;

Component.extend('sas-blog-create', 'sas-blog-detail', {
    template,

    methods: {
        async createdComponent() {
            Shopware.Store.get('adminMenu').collapseSidebar();

            const isSystemDefaultLanguage =
                Shopware.Store.get('context').isSystemDefaultLanguage;
            this.cmsPageState.setIsSystemDefaultLanguage(isSystemDefaultLanguage);

            if (!isSystemDefaultLanguage) {
                Shopware.Store.get('context').resetLanguageToDefault();
            }

            if (
                Shopware.Context.api.languageId !==
                Shopware.Context.api.systemLanguageId
            ) {
                Shopware.Store.get('context').setApiLanguageId(Shopware.Context.api.languageId);
            }

            this.resetCmsPageState();

            this.createPage();
            this.createBlog(this.page.id);
            this.isLoading = false;

            this.setPageContext();
        },

        createBlog(pageId) {
            this.blog = this.blogRepository.create();
            this.blog.cmsPageId = pageId;
            this.blogId = this.blog.id;
        },

        loadBlog() {
            this.isLoading = true;
            this.createBlog(this.page.id);
            this.isLoading = false;
        },
    },
});
