import './acl';
import './page/myfav-inquiry-list';
import './page/myfav-inquiry-detail';
import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

const { Module } = Shopware;

Module.register('myfav-inquiry', {
    type: 'plugin',
    name: 'MyfavInquiry',
    title: 'myfav-inquiry.general.mainMenuItemGeneral',
    description: 'myfav-inquiry.general.descriptionTextModule',
    color: '#152b48',
    icon: 'default-communication-envelope',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        list: {
            component: 'myfav-inquiry-list',
            path: 'list',
            meta: {
                privilege: 'myfav_inquiry.viewer'
            }
        },
        detail: {
            component: 'myfav-inquiry-detail',
            path: 'detail/:id',
            meta: {
                parentPath: 'myfav.inquiry.list',
                privilege: 'myfav_inquiry.editor'
            }
        },
    },

    navigation: [{
        label: 'myfav-inquiry.general.mainMenuItemGeneral',
        color: '#152b48',
        path: 'myfav.inquiry.list',
        icon: 'default-communication-envelope',
        parent: 'sw-order',
        privilege: 'myfav_inquiry.viewer'
    }],
});
