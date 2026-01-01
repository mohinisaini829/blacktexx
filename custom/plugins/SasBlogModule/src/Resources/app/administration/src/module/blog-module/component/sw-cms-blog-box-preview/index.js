import template from './sw-cms-blog-box-preview.html.twig';
import './sw-cms-blog-box-preview.scss';

/**
 * @private
 * @package buyers-experience
 */
export default {
    template,

    props: {
        hasText: {
            type: Boolean,
            default: false,
            required: false,
        },
    },

    computed: {
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        },
    },
};
