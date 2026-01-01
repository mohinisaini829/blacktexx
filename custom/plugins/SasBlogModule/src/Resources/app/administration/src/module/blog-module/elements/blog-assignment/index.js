/**
 * @private
 * @package buyers-experience
 */
Shopware.Component.register(
    'sw-cms-el-preview-blog-assignment',
    () => import('./preview'),
);
/**
 * @private
 * @package buyers-experience
 */
Shopware.Component.register(
    'sw-cms-el-config-blog-assignment',
    () => import('./config'),
);
/**
 * @private
 * @package buyers-experience
 */
Shopware.Component.register(
    'sw-cms-el-blog-assignment',
    () => import('./component'),
);

const Criteria = Shopware.Data.Criteria;
const criteria = new Criteria(1, 25);
criteria.addAssociation('assignedBlogs');

/**
 * @private
 * @package buyers-experience
 */
Shopware.Service('cmsService').registerCmsElement({
    name: 'blog-assignment',
    label: 'sas-blog.elements.assignment.label',
    component: 'sw-cms-el-blog-assignment',
    configComponent: 'sw-cms-el-config-blog-assignment',
    previewComponent: 'sw-cms-el-preview-blog-assignment',
    defaultConfig: {
        product: {
            source: 'static',
            value: null,
            required: false,
            entity: {
                name: 'product',
                criteria: criteria,
            },
        },
        displayMode: {
            source: 'static',
            value: 'standard',
        },
        boxLayout: {
            source: 'static',
            value: 'standard',
        },
        elMinWidth: {
            source: 'static',
            value: '300px',
        },
        showRandom: {
            source: 'static',
            value: false,
        },
    },
    collect: Shopware.Service('cmsService').getCollectFunction(),
});
