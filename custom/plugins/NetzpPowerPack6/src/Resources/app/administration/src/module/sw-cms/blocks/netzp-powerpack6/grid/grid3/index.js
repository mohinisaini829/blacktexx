import './component';
import './preview';

let defaultValue = "";

Shopware.Service('cmsService').registerCmsBlock({
    name: 'netzp-powerpack6-grid3',
    label: 'sw-cms.netzp-powerpack6.blocks.grid3.label',
    category: 'netzp-powerpack-layouts',
    component: 'sw-cms-block-netzp-powerpack6-grid3',
    previewComponent: 'sw-cms-preview-netzp-powerpack6-grid3',

    defaultConfig: {
        marginBottom: '20px',
        marginTop:    '20px',
        marginLeft:   '20px',
        marginRight:  '20px',
        sizingMode:   'boxed'
    },

    slots: {
        column1: {
            type: 'text',
            default: {
                config: {
                    content: {
                        source: 'static',
                        value: defaultValue
                    }
                }
            }
        },
        column2: {
            type: 'text',
            default: {
                config: {
                    content: {
                        source: 'static',
                        value: defaultValue
                    }
                }
            }
        },
        column3: {
            type: 'text',
            default: {
                config: {
                    content: {
                        source: 'static',
                        value: defaultValue
                    }
                }
            }
        }
    }
});
