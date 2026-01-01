import './page/acris-cookie-group-list';
import './page/acris-cookie-group-create';
import './page/acris-cookie-group-detail';

import deDE from './snippet/de-DE';
import enGB from './snippet/en-GB';

const { Module } = Shopware;

Module.register('acris-cookie-group', {
    type: 'core',
    name: 'acris-cookie-group',
    title: 'acris-cookie.general.mainMenuItemGeneral',
    description: 'acris-cookie.general.description',
    color: '#9AA8B5',
    icon: 'regular-users',
    favicon: 'icon-module-settings.png',
    entity: 'acris_cookie_group',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        index: {
            component: 'acris-cookie-group-list',
            path: 'index',
            meta: {
                parentPath: 'acris.cookie.index.index'
            }
        },
        detail: {
            component: 'acris-cookie-group-detail',
            path: 'detail/:id',
            meta: {
                parentPath: 'acris.cookie.group.index'
            }
        },
        create: {
            component: 'acris-cookie-group-create',
            path: 'create',
            meta: {
                parentPath: 'acris.cookie.group.index'
            }
        }
    }
});
