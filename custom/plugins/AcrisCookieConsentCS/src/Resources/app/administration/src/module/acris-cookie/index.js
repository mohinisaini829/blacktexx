import './page/acris-cookie-list';
import './page/acris-cookie-create';
import './page/acris-cookie-detail';

import deDE from './snippet/de-DE';
import enGB from './snippet/en-GB';

const { Module } = Shopware;

Module.register('acris-cookie', {
    type: 'core',
    name: 'acris-cookie',
    title: 'acris-cookie.general.mainMenuItemGeneral',
    description: 'acris-cookie.general.description',
    color: '#9AA8B5',
    icon: 'regular-cog',
    favicon: 'icon-module-settings.png',
    entity: 'acris_cookie',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        index: {
            component: 'acris-cookie-list',
            path: 'index',
            meta: {
                parentPath: 'acris.cookie.index.index'
            }
        },
        detail: {
            component: 'acris-cookie-detail',
            path: 'detail/:id',
            meta: {
                parentPath: 'acris.cookie.index'
            }
        },
        create: {
            component: 'acris-cookie-create',
            path: 'create',
            meta: {
                parentPath: 'acris.cookie.index'
            }
        }
    }
});
