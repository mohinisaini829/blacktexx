import template from './sw-cms-sidebar.html.twig';
import BLOG from '../../../../constant/blog-module.constant';

export default {
    template,

    computed: {
        pageRepository() {
            return this.repositoryFactory.create('cms_page');
        },
        isBlogDetail() {
            return this.page.type === BLOG.PAGE_TYPES.BLOG_DETAIL;
        },
    },
};
