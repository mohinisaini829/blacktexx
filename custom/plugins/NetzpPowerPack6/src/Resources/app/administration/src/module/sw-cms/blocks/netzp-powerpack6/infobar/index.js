import './component';
import './preview';

Shopware.Service('cmsService').registerCmsBlock({
    name: 'netzp-powerpack6-infobar',
    label: 'sw-cms.netzp-powerpack6.blocks.infobar.label',
    category: 'netzp-powerpack-elements',
    component: 'sw-cms-block-netzp-powerpack6-infobar',
    previewComponent: 'sw-cms-preview-netzp-powerpack6-infobar',

    defaultConfig: {
        marginBottom: '0px',
        marginTop:    '0px',
        marginLeft:   '0px',
        marginRight:  '0px',
        sizingMode:   'boxed'
    },

    slots: {
        content: {
            type: 'netzp-powerpack6-infobar'
        }
    }
});
