import './component';
import './config';
import './preview';

Shopware.Service('cmsService').registerCmsElement({
    name: 'netzp-powerpack6-alert',
    label: 'sw-cms.netzp-powerpack6.elements.alert.label',
    component: 'sw-cms-el-netzp-powerpack6-alert',
    configComponent: 'sw-cms-el-config-netzp-powerpack6-alert',
    previewComponent: 'sw-cms-el-preview-netzp-powerpack6-alert',

    defaultConfig: {
        alertType: {
            source: 'static',
            value: 0
        },
        title: { source: 'static', value: 'Lorem!' },
        contents: { source: 'static', value: 'Lorem ipsum dolor sit amet.' },
    }
});
