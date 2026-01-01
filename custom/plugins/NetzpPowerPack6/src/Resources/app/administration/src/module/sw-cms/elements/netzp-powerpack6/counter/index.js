import './component';
import './config';
import './preview';

Shopware.Service('cmsService').registerCmsElement({
    name: 'netzp-powerpack6-counter',
    label: 'sw-cms.netzp-powerpack6.elements.counter.label',
    component: 'sw-cms-el-netzp-powerpack6-counter',
    configComponent: 'sw-cms-el-config-netzp-powerpack6-counter',
    previewComponent: 'sw-cms-el-preview-netzp-powerpack6-counter',

    defaultConfig: {
        start: { source: 'static', value: 0 },
        end: { source: 'static', value: 1000 },
        speed: { source: 'static', value: 1000 },

        title: { source: 'static', value: 'Counter' },
        text: { source: 'static', value: '{counter}' },
        icon: { source: 'static', value: 'fa-check-circle' },

        iconColor: { source: 'static', value: '#000000' },
        titleColor: { source: 'static', value: '#000000' },
        counterColor: { source: 'static', value: '#000000' }
    }
});
