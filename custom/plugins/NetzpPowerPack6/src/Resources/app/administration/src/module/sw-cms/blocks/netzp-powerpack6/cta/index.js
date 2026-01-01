import './component';
import './preview';

Shopware.Service('cmsService').registerCmsBlock({
    name: 'netzp-powerpack6-cta',
    label: 'sw-cms.netzp-powerpack6.blocks.cta.label',
    category: 'netzp-powerpack-elements',
    component: 'sw-cms-block-netzp-powerpack6-cta',
    previewComponent: 'sw-cms-preview-netzp-powerpack6-cta',

    defaultConfig: {
        marginBottom: '20px',
        marginTop:    '20px',
        marginLeft:   '20px',
        marginRight:  '20px',
        sizingMode:   'boxed'
    },

    slots: {
        content: {
            type: 'netzp-powerpack6-cta'
        }
    }
});
