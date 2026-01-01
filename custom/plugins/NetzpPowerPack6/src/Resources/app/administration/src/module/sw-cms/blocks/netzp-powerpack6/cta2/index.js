import './component';
import './preview';

Shopware.Service('cmsService').registerCmsBlock({
    name: 'netzp-powerpack6-cta2',
    label: 'sw-cms.netzp-powerpack6.blocks.cta2.label',
    category: 'netzp-powerpack-elements',
    component: 'sw-cms-block-netzp-powerpack6-cta2',
    previewComponent: 'sw-cms-preview-netzp-powerpack6-cta2',

    defaultConfig: {
        marginBottom: '20px',
        marginTop:    '20px',
        marginLeft:   '20px',
        marginRight:  '20px',
        sizingMode:   'boxed'
    },

    slots: {
        content: {
            type: 'netzp-powerpack6-cta2'
        }
    }
});
