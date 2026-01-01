import './component';
import './preview';

Shopware.Service('cmsService').registerCmsElement({
    name: 'manufacturer-video-slider',
    label: 'sw-cms.elements.manufacturer-video-slider.label',
    component: 'sw-cms-el-component-manufacturer-video-slider',
    previewComponent: 'sw-cms-el-preview-manufacturer-video-slider'
});
