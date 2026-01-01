Shopware.Service('privileges').addPrivilegeMappingEntry({
    category: 'permissions',
    parent: 'customers',
    key: 'myfav_inquiry',
    roles: {
        viewer: {
            privileges: [
                'myfav_inquiry:read'
            ],
            dependencies: [
                'product.viewer'
            ]
        },
        editor: {
            privileges: [
                'myfav_inquiry:update'
            ],
            dependencies: [
                'myfav_inquiry.viewer'
            ]
        },
        creator: {
            privileges: [
                'myfav_inquiry:create'
            ],
            dependencies: [
                'myfav_inquiry.viewer',
                'myfav_inquiry.editor'
            ]
        },
        deleter: {
            privileges: [
                'myfav_inquiry:delete'
            ],
            dependencies: [
                'myfav_inquiry.viewer'
            ]
        }
    }
});
