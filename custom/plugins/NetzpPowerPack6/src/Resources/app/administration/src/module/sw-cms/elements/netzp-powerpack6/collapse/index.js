import './component';
import './config';
import './preview';

Shopware.Service('cmsService').registerCmsElement({
    name: 'netzp-powerpack6-collapse',
    label: 'sw-cms.netzp-powerpack6.elements.collapse.label',
    component: 'sw-cms-el-netzp-powerpack6-collapse',
    configComponent: 'sw-cms-el-config-netzp-powerpack6-collapse',
    previewComponent: 'sw-cms-el-preview-netzp-powerpack6-collapse',

    defaultConfig: {
        numberOfItems: {
            source: 'static',
            value: 3
        },
        firstItemOpened: {
            source: 'static',
            value: true
        },

        title:  { source: 'static', value: '' },

        itemtitle1:  { source: 'static', value: 'Item 1' },
        itemtitle2:  { source: 'static', value: 'Item 2' },
        itemtitle3:  { source: 'static', value: 'Item 3' },
        itemtitle4:  { source: 'static', value: 'Item 4' },
        itemtitle5:  { source: 'static', value: 'Item 5' },
        itemtitle6:  { source: 'static', value: 'Item 6' },
        itemtitle7:  { source: 'static', value: 'Item 7' },
        itemtitle8:  { source: 'static', value: 'Item 8' },
        itemtitle9:  { source: 'static', value: 'Item 9' },
        itemtitle10: { source: 'static', value: 'Item 10' },
        itemtitle11: { source: 'static', value: 'Item 11' },
        itemtitle12: { source: 'static', value: 'Item 12' },
        itemtitle13: { source: 'static', value: 'Item 13' },
        itemtitle14: { source: 'static', value: 'Item 14' },
        itemtitle15: { source: 'static', value: 'Item 15' },
        itemtitle16: { source: 'static', value: 'Item 16' },
        itemtitle17: { source: 'static', value: 'Item 17' },
        itemtitle18: { source: 'static', value: 'Item 18' },
        itemtitle19: { source: 'static', value: 'Item 19' },
        itemtitle20: { source: 'static', value: 'Item 20' },

        itemcontents1:  { source: 'static', value: '' },
        itemcontents2:  { source: 'static', value: '' },
        itemcontents3:  { source: 'static', value: '' },
        itemcontents4:  { source: 'static', value: '' },
        itemcontents5:  { source: 'static', value: '' },
        itemcontents6:  { source: 'static', value: '' },
        itemcontents7:  { source: 'static', value: '' },
        itemcontents8:  { source: 'static', value: '' },
        itemcontents9:  { source: 'static', value: '' },
        itemcontents10: { source: 'static', value: '' },
        itemcontents11: { source: 'static', value: '' },
        itemcontents12: { source: 'static', value: '' },
        itemcontents13: { source: 'static', value: '' },
        itemcontents14: { source: 'static', value: '' },
        itemcontents15: { source: 'static', value: '' },
        itemcontents16: { source: 'static', value: '' },
        itemcontents17: { source: 'static', value: '' },
        itemcontents18: { source: 'static', value: '' },
        itemcontents19: { source: 'static', value: '' },
        itemcontents20: { source: 'static', value: '' }
    }
});
