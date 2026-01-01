import template from './sw-cms-el-config-blog-box.html.twig';
import './sw-cms-el-config-blog-box.scss';

const { Criteria } = Shopware.Data;
const { Mixin } = Shopware;

/**
 * @private
 * @package buyers-experience
 */
export default {
    template,

    inject: ['repositoryFactory'],

    mixins: [Mixin.getByName('cms-element')],

    computed: {
        blogRepository() {
            return this.repositoryFactory.create('sas_blog_entries');
        },

        blogSelectContext() {
            const context = { ...Shopware.Context.api };
            context.inheritance = true;

            return context;
        },

        blogCriteria() {
            const criteria = new Criteria(1, 25);

            return criteria;
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('blog-box');
        },

        onBlogChange(blogId) {
            if (!blogId) {
                this.element.config.blog.value = null;
                this.element.data.blogId = null;
                this.element.data.blog = null;
            } else {
                const criteria = new Criteria(1, 25);
                criteria.addAssociation('media');

                this.blogRepository
                    .get(blogId, this.blogSelectContext, criteria)
                    .then((blog) => {
                        this.element.config.blog.value = blogId;
                        this.element.data.blogId = blogId;
                        this.element.data.blog = blog;
                    });
            }

            this.$emit('element-update', this.element);
        },
    },
};
