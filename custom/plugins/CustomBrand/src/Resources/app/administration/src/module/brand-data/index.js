///plugins/CustomBrand/src/Resources/app/administration/src/module/brand-data/index.js
import './page/brand-data';

Shopware.Module.register('brand-data', {
    type: 'plugin',
    name: 'Brand Data',
    title: 'Brand Data',
    description: 'Manage brand data',
    routes: {
        index: {
            component: 'brand-data-page',
            path: 'index'
        }
    },
    navigation: [{
        label: 'Brand Data',
        color: '#ff3d58',
        path: 'brand.data.index',
        icon: 'default-object-bag',
        parent: 'sw-catalogue'
    }]
});