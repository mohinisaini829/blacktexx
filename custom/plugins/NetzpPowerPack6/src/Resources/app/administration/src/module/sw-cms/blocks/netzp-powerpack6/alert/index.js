import './component';
import './preview';

Shopware.Service('cmsService').registerCmsBlock({
    name: 'netzp-powerpack6-alert',
    label: 'sw-cms.netzp-powerpack6.blocks.alert.label',
    category: 'netzp-powerpack-elements',
    component: 'sw-cms-block-netzp-powerpack6-alert',
    previewComponent: 'sw-cms-preview-netzp-powerpack6-alert',

    defaultConfig: {
        marginBottom: '0px',
        marginTop:    '0px',
        marginLeft:   '0px',
        marginRight:  '0px',
        sizingMode:   'boxed'
    },

    slots: {
        content: {
            type: 'netzp-powerpack6-alert'
        }
    }
});
