import './component';
import './config';
import './preview';

Shopware.Service('cmsService').registerCmsElement({
    name: 'netzp-powerpack6-countdown',
    label: 'sw-cms.netzp-powerpack6.elements.countdown.label',
    component: 'sw-cms-el-netzp-powerpack6-countdown',
    configComponent: 'sw-cms-el-config-netzp-powerpack6-countdown',
    previewComponent: 'sw-cms-el-preview-netzp-powerpack6-countdown',

    defaultConfig: {
        layout: { source: 'static', value: 'boxes' },
        enddate: { source: 'static', value: '' },
        textSize: { source: 'static', value: 2 },
        textAlign: { source: 'static', value: 'center' },

        title: { source: 'static', value: 'Countdown' },
        countdown: { source: 'static', value: '{days}:{hours}:{minutes}:{seconds}' },
        elapsed: { source: 'static', value: 'Elapsed.' },
        elapsedHide: { source: 'static', value: false },
        buttonText: { source: 'static', value: 'Clicksum!' },
        buttonLink: { source: 'static', value: '' },
        buttonTargetBlank: { source: 'static', value: false },

        backgroundColor: { source: 'static', value: '#eeeeee' },
        textColor: { source: 'static', value: '#000000' },
        boxColor: { source: 'static', value: '#cccccc' },
        buttonColor: { source: 'static', value: '#cccccc' },
        buttonTextColor: { source: 'static', value: '#ff0000' },
    }
});
