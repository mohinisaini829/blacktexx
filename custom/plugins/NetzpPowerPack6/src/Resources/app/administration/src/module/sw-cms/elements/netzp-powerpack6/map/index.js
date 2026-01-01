import './component';
import './config';
import './preview';

Shopware.Service('cmsService').registerCmsElement({
    name: 'netzp-powerpack6-map',
    label: 'sw-cms.netzp-powerpack6.elements.map.label',
    component: 'sw-cms-el-netzp-powerpack6-map',
    configComponent: 'sw-cms-el-config-netzp-powerpack6-map',
    previewComponent: 'sw-cms-el-preview-netzp-powerpack6-map',

    defaultConfig: {
        height: { source: 'static', value: 13 },
        lat: { source: 'static', value:  52.0881315 },
        long: { source: 'static', value: 7.2454613 },
        mapType: {
            source: 'static',
            value: 'roadmap'
        },
        zoomLevel: { source: 'static', value: 16 },
        contents: { source: 'static', value: 'Lorem ipsum dolor sit amet.' },
        optIn: { source: 'static', value: false },
    }
});
