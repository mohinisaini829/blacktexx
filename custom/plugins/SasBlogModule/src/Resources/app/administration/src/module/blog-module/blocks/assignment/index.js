/**
 * @private
 * @package buyers-experience
 */
Shopware.Component.register(
    'sw-cms-preview-blog-assignment',
    () => import('./preview'),
);
/**
 * @private
 * @package buyers-experience
 */
Shopware.Component.register(
    'sw-cms-block-blog-assignment',
    () => import('./component'),
);
/**
 * @private
 * @package buyers-experience
 */
Shopware.Service('cmsService').registerCmsBlock({
    name: 'blog-assignment',
    label: 'sas-blog.blocks.blog.blog-assignment.label',
    category: 'sas-blog',
    component: 'sas-cms-block-blog-assignment',
    previewComponent: 'sw-cms-preview-blog-assignment',
    defaultConfig: {
        marginBottom: '20px',
        marginTop: '20px',
        marginLeft: '20px',
        marginRight: '20px',
        sizingMode: 'boxed',
    },
    slots: {
        content: 'blog-assignment',
    },
});
