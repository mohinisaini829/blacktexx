import './component';
import './preview';

const { Service } = Shopware;

Service('cmsService').registerCmsBlock({
    name: 'solid-ase-content-slider',
    label: 'sw-cms.blocks.solid-ase.content-slider.label',
    category: 'solid-advanced-slider-elements',
    component: 'sw-cms-block-solid-ase-content-slider',
    previewComponent: 'sw-cms-block-preview-solid-ase-content-slider',
    defaultConfig: {
        marginBottom: '',
        marginTop: '',
        marginLeft: '',
        marginRight: '',
        sizingMode: 'boxed',
    },
    slots: {
        slider: {
            type: 'solid-ase-content-slider',
        },
    },
});
