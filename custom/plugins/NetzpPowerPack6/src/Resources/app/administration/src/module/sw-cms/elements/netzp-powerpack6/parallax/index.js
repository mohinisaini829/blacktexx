import './component';
import './config';
import './preview';

Shopware.Service('cmsService').registerCmsElement({
    name: 'netzp-powerpack6-parallax',
    label: 'sw-cms.netzp-powerpack6.elements.parallax.label',
    component: 'sw-cms-el-netzp-powerpack6-parallax',
    configComponent: 'sw-cms-el-config-netzp-powerpack6-parallax',
    previewComponent: 'sw-cms-el-preview-netzp-powerpack6-parallax',

    defaultConfig: {
        media: {
            source: 'static',
            value: null,
            required: true,
            entity: {
                name: 'media'
            }
        },
        height: { source: 'static', value: 10 },
    }
});
