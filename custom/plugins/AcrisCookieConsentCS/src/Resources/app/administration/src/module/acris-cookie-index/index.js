import './acris-settings-item.scss'
import './page/acris-cookie-index';

import deDE from './snippet/de-DE';
import enGB from './snippet/en-GB';

const { Module } = Shopware;

Module.register('acris-cookie-index', {
    type: 'plugin',
    name: 'acris-cookie-index',
    title: 'acris-cookie-index.general.mainMenuItemGeneral',
    description: 'acris-cookie-index.general.descriptionTextModule',
    version: '1.0.0',
    targetVersion: '1.0.0',
    color: '#a6c836',
    icon: 'regular-smile-beam',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        index: {
            component: 'acris-cookie-index',
            path: 'index/index',
            icon: 'regular-smile-beam',
            meta: {
                parentPath: 'sw.settings.index.plugins'
            }
        }
    },

    settingsItem: [
        {
            name: 'acris-cookie-index-index',
            to: 'acris.cookie.index.index',
            label: 'acris-cookie-index.general.mainMenuItemGeneral',
            group: 'plugins',
            icon: 'regular-smile-beam'
        }
    ]
});
