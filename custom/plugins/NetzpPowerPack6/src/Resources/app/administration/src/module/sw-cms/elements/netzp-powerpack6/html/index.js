import './component';
import './config';
import './preview';

Shopware.Service('cmsService').registerCmsElement({
    name: 'netzp-powerpack6-html',
    label: 'sw-cms.netzp-powerpack6.elements.html.label',
    component: 'sw-cms-el-netzp-powerpack6-html',
    configComponent: 'sw-cms-el-config-netzp-powerpack6-html',
    previewComponent: 'sw-cms-el-preview-netzp-powerpack6-html',

    defaultConfig: {
        html: {
            source: 'static',
            value: ''
        },
        css: {
            source: 'static',
            value: ''
        }
    }
});
