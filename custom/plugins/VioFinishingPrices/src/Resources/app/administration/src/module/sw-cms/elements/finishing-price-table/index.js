import './component';
import './preview';

Shopware.Service('cmsService').registerCmsElement({
    name: 'finishing-price-table',
    label: 'sw-cms.elements.finishing-price-table.label',
    component: 'sw-cms-el-component-finishing-price-table',
    previewComponent: 'sw-cms-el-preview-finishing-price-table',
});
