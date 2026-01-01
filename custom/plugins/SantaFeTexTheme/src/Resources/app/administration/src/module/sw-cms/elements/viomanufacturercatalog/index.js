import './component';
import './preview';

Shopware.Service('cmsService').registerCmsElement({
    name: 'viomanufacturercatalog',
    label: 'sw-cms.elements.viomanufacturercatalog.label',
    component: 'sw-cms-el-component-viomanufacturercatalog',
    previewComponent: 'sw-cms-el-preview-viomanufacturercatalog'
});