import template from './sw-cms-el-preview-blog-assignment.html.twig';
import './sw-cms-el-preview-blog-assignment.scss';

/**
 * @private
 * @package buyers-experience
 */
export default {
    template,

    computed: {
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        },
    },
};
