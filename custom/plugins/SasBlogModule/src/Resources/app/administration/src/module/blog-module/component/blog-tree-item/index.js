import template from './blog-tree-item.html.twig';

const { Component } = Shopware;

Component.extend('sas-blog-tree-item', 'sw-tree-item', {
    template,

    computed: {
        parentScope() {
            let parentNode = this.$parent;
            while (parentNode.$options.name !== 'sas-blog-tree') {
                parentNode = parentNode.$parent;
            }
            return parentNode;
        },
    },

    props: {
        activeCategoryId: {
            type: String,
            required: false,
            default: null,
        },
    },

    data() {
        return {
            editingCategory: null,
            isCanceling: false,
            isSaving: false,
        };
    },
    methods: {
        onEditCategory(category) {
            this.editingCategory = category;
            this.currentEditElement = category.id;
            setTimeout(() => this.editElementName(), 500);
            this.parentScope.onEditMode(category);
        },

        onFinishNameingElement(draft, event) {
            if (this.editingCategory) {
                this.isSaving = true;
                this.parentScope.onFinishEditNameingElement(
                    draft,
                    event,
                    this.editingCategory,
                );

                this.currentEditElement = null;
                this.editingCategory = null;
            } else {
                this.parentScope.onFinishNameingElement(draft, event);
            }
        },

        onBlurTreeItemInput(item) {
            if (this.isCanceling) {
                this.isCanceling = false;
                return;
            }

            if (this.isSaving) {
                this.isSaving = false;
                return;
            }

            this.abortCreateElement(item);
        },

        onCancelSubmit(item) {
            this.isCanceling = true;
            this.abortCreateElement(item);
        },

        abortCreateElement(item) {
            this.currentEditElement = null;
            this.editingCategory = null;
            this.$super('abortCreateElement', item);
        },
    },

    watch: {
        activeCategoryId() {
            this.active = this.activeCategoryId === this.item.id;
        },
    },
});
