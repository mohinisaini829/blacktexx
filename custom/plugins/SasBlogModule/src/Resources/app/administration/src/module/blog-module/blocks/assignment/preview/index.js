import template from './sw-cms-preview-blog-assignment.html.twig';
import './sw-cms-preview-blog-assignment.scss';

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
