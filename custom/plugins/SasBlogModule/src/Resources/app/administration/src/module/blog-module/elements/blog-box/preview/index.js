import template from './sw-cms-el-preview-blog-box.html.twig';
import './sw-cms-el-preview-blog-box.scss';

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
