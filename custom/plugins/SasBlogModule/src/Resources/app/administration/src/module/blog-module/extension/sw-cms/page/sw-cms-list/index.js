export default {
    methods: {
        async createdComponent() {
            if (this.cmsPageTypeService.getType('blog_detail') === undefined) {
                const newTypeData = {
                    name: 'blog_detail',
                    title: 'sw-cms.sorting.labelSortByBlogPages',
                    hideInList: false,
                };
                this.cmsPageTypeService.register(newTypeData);
            }

            this.$super('createdComponent');
        },
    },
};
