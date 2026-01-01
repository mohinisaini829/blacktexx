import './acl';
import './page/vio-finishing-prices-list';
import './page/vio-finishing-prices-detail';
import './page/vio-finishing-prices-create';

const {Module} = Shopware;

Module.register('vio-finishing-prices', {
    type: 'plugin',
    title: 'vio-finishing-prices.general.mainMenuItemGeneral',
    color: '#57D9A3',
    icon: 'default-text-table',

    routes: {
        list: {
            component: 'vio-finishing-prices-list',
            path: 'list',
            meta: {
                privilege: 'vio_finishing_prices.viewer'
            }
        },
        detail: {
            component: 'vio-finishing-prices-detail',
            path: 'detail/:id',
            meta: {
                parentPath: 'vio.finishing.prices.list',
                privilege: 'vio_finishing_prices.editor'
            }
        },
        create: {
            component: 'vio-finishing-prices-create',
            path: 'create',
            meta: {
                parentPath: 'vio.finishing.prices.list',
                privilege: 'vio_finishing_prices.creator'
            }
        }
    },

    settingsItem: {
        to: 'vio.finishing.prices.list',
        label: 'vio-finishing-prices.general.mainMenuItemGeneral',
        color: '#57D9A3',
        group: 'plugins',
        icon: 'default-text-table'
    }
})
