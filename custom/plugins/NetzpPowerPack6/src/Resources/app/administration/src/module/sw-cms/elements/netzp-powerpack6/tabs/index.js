import './component';
import './config';
import './preview';

Shopware.Service('cmsService').registerCmsElement({
    name: 'netzp-powerpack6-tabs',
    label: 'sw-cms.netzp-powerpack6.elements.tabs.label',
    component: 'sw-cms-el-netzp-powerpack6-tabs',
    configComponent: 'sw-cms-el-config-netzp-powerpack6-tabs',
    previewComponent: 'sw-cms-el-preview-netzp-powerpack6-tabs',

    defaultConfig: {
        numberOfTabs: {
            source: 'static',
            value: 3
        },

        title:  { source: 'static', value: '' },

        tabtitle1:  { source: 'static', value: 'Tab 1' },
        tabtitle2:  { source: 'static', value: 'Tab 2' },
        tabtitle3:  { source: 'static', value: 'Tab 3' },
        tabtitle4:  { source: 'static', value: 'Tab 4' },
        tabtitle5:  { source: 'static', value: 'Tab 5' },
        tabtitle6:  { source: 'static', value: 'Tab 6' },
        tabtitle7:  { source: 'static', value: 'Tab 7' },
        tabtitle8:  { source: 'static', value: 'Tab 8' },
        tabtitle9:  { source: 'static', value: 'Tab 9' },
        tabtitle10: { source: 'static', value: 'Tab 10' },
        tabtitle11: { source: 'static', value: 'Tab 11' },
        tabtitle12: { source: 'static', value: 'Tab 12' },
        tabtitle13: { source: 'static', value: 'Tab 13' },
        tabtitle14: { source: 'static', value: 'Tab 14' },
        tabtitle15: { source: 'static', value: 'Tab 15' },
        tabtitle16: { source: 'static', value: 'Tab 16' },
        tabtitle17: { source: 'static', value: 'Tab 17' },
        tabtitle18: { source: 'static', value: 'Tab 18' },
        tabtitle19: { source: 'static', value: 'Tab 19' },
        tabtitle20: { source: 'static', value: 'Tab 20' },

        tabcontents1:  { source: 'static', value: '' },
        tabcontents2:  { source: 'static', value: '' },
        tabcontents3:  { source: 'static', value: '' },
        tabcontents4:  { source: 'static', value: '' },
        tabcontents5:  { source: 'static', value: '' },
        tabcontents6:  { source: 'static', value: '' },
        tabcontents7:  { source: 'static', value: '' },
        tabcontents8:  { source: 'static', value: '' },
        tabcontents9:  { source: 'static', value: '' },
        tabcontents10: { source: 'static', value: '' },
        tabcontents11: { source: 'static', value: '' },
        tabcontents12: { source: 'static', value: '' },
        tabcontents13: { source: 'static', value: '' },
        tabcontents14: { source: 'static', value: '' },
        tabcontents15: { source: 'static', value: '' },
        tabcontents16: { source: 'static', value: '' },
        tabcontents17: { source: 'static', value: '' },
        tabcontents18: { source: 'static', value: '' },
        tabcontents19: { source: 'static', value: '' },
        tabcontents20: { source: 'static', value: '' }
    }
});
