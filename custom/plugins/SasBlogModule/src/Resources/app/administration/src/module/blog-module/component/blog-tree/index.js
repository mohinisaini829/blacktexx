const { Component } = Shopware;

Component.extend('sas-blog-tree', 'sw-tree', {
    inject: ['repositoryFactory'],

    computed: {
        blogCategoryRepository() {
            return this.repositoryFactory.create('sas_blog_category');
        },
    },

    methods: {
        onEditMode(item) {
            this._eventFromEdit = item;
        },

        async onFinishEditNameingElement(draft, event, editItem) {
            if (editItem) {
                editItem.data.name = draft;
                this.blogCategoryRepository.save(editItem.data).then(() => {
                    this.$emit('finish-edit-item', editItem);
                    this.saveItems();
                    if (this.currentEditMode !== null && this.contextItem) {
                        this.currentEditMode(
                            this.contextItem,
                            this.addElementPosition,
                        );
                    }
                });
            }
            this._eventFromEdit = event;
            this.newElementId = null;
        },
        onFinishNameingElement(draft, event) {
            if (this.createdItem) {
                this.createdItem.data.name = draft;
            }

            this.$super('onFinishNameingElement', draft, event);
        },
    },
});
