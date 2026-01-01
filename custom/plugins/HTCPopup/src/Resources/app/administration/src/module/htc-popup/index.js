import './page/htc-popup-list';
import './page/htc-popup-create';
import './page/htc-popup-detail';

import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';
import nlNL from './snippet/nl-NL.json';


const { Module } = Shopware;

Module.register('htc-popup', {
    type: 'plugin',
    name: 'htc-popup',
    color: '#ff3d58',
    icon: 'regular-shopping-bag',
    title: 'htc-popup-snp.general.navigationTitle',
    description: 'htc-popup-snp.general.navigationDescription',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB,
        'nl-NL': nlNL
    },

    routes: {
        list: {
            component: 'htc-popup-list',
            path: 'list'
        },
        detail: {
            component: 'htc-popup-detail',
            path: 'detail/:id',
            meta: {
                parentPath: 'htc.popup.list'
            }
        },
        create: {
            component: 'htc-popup-create',
            path: 'create',
            meta: {
                parentPath: 'htc.popup.list'
            }
        }
    },

    navigation: [
        {
            label: 'htc-popup-snp.general.navigationTitle',
            color: '#ff3d58',
            path: 'htc.popup.list',
            icon: 'default-action-sliders',
            position: 100,
            entity: 'popup',
            parent: 'sw-content'
        }
    ],
});