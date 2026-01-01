import './component';
import './preview';

Shopware.Service('cmsService').registerCmsElement({
    name: 'produkt-zertifizierungen',
    label: 'sw-cms.elements.vio_product_zerts.label',
    component: 'sw-cms-el-component-produkt-zertifizierungen',
    previewComponent: 'sw-cms-el-preview-produkt-zertifizierungen'
});