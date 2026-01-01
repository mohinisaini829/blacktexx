import './component';
import './preview';

Shopware.Service('cmsService').registerCmsBlock({
    name: 'netzp-powerpack6-imagecompare',
    label: 'sw-cms.netzp-powerpack6.blocks.imagecompare.label',
    category: 'netzp-powerpack-elements',
    component: 'sw-cms-block-netzp-powerpack6-imagecompare',
    previewComponent: 'sw-cms-preview-netzp-powerpack6-imagecompare',

    defaultConfig: {
        marginBottom: '20px',
        marginTop:    '20px',
        marginLeft:   '20px',
        marginRight:  '20px',
        sizingMode:   'boxed'
    },

    slots: {
        content: {
            type: 'netzp-powerpack6-imagecompare'
        }
    }
});
