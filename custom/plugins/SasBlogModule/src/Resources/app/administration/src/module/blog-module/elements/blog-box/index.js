/**
 * @private
 * @package buyers-experience
 */
Shopware.Component.register(
    'sw-cms-el-preview-blog-box',
    () => import('./preview'),
);
/**
 * @private
 * @package buyers-experience
 */
Shopware.Component.register(
    'sw-cms-el-config-blog-box',
    () => import('./config'),
);
/**
 * @private
 * @package buyers-experience
 */
Shopware.Component.register('sw-cms-el-blog-box', () => import('./component'));

const Criteria = Shopware.Data.Criteria;
const criteria = new Criteria(1, 25);
criteria.addAssociation('media');

/**
 * @private
 * @package buyers-experience
 */
Shopware.Service('cmsService').registerCmsElement({
    name: 'blog-box',
    label: 'sas-blog.elements.blogBox.label',
    component: 'sw-cms-el-blog-box',
    previewComponent: 'sw-cms-el-preview-blog-box',
    configComponent: 'sw-cms-el-config-blog-box',
    defaultConfig: {
        blog: {
            source: 'static',
            value: null,
            required: true,
            entity: {
                name: 'blog',
                criteria: criteria,
            },
        },
        boxLayout: {
            source: 'static',
            value: 'standard',
        },
        displayMode: {
            source: 'static',
            value: 'standard',
        },
        verticalAlign: {
            source: 'static',
            value: null,
        },
    },
    defaultData: {
        boxLayout: 'standard',
        blog: null,
    },
    collect: Shopware.Service('cmsService').getCollectFunction(),
});
