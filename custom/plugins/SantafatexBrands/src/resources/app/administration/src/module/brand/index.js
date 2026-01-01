console.log('index');
import BrandIndexPage from './page/brand-index';
import BrandDetailPage from './page/brand-detail';


Shopware.Module.register('santafatex-brands', {
    type: 'plugin',
    name: 'Santafatex Brands',
    title: 'sw-santafatex.brands.general.mainMenuItemGeneral',
    description: 'Manage brands with file uploads',
    version: '1.0.0',
    targetVersion: '1.0.0',
    color: '#ff68b4',
    icon: 'default-bell-bulb',

    routes: {
        index: {
            component: BrandIndexPage,
            path: 'brands',
            name: 'santafatex.brands.index',
            meta: {
                parentPath: 'sw.dashboard.index',
                privilege: 'santafatex_brands.viewer',
            },
        },
        detail: {
            component: BrandDetailPage,
            path: 'brands/:id',
            name: 'santafatex.brands.detail',
            meta: {
                parentPath: 'santafatex.brands.index',
                privilege: 'santafatex_brands.editor',
            },
        },
        create: {
            component: BrandDetailPage,
            path: 'brands/create',
            name: 'santafatex.brands.create',
            meta: {
                parentPath: 'santafatex.brands.index',
                privilege: 'santafatex_brands.creator',
            },
        },
    },

    navigation: [
        {
            id: 'santafatex-brands',
            label: 'sw-santafatex.brands.general.mainMenuItemGeneral',
            color: '#ff68b4',
            path: 'santafatex.brands.index',
            icon: 'default-bell-bulb',
            position: 70,
            parent: 'sw-catalogue',
        },
    ],
});