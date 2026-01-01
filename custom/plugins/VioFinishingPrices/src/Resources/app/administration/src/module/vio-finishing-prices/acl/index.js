Shopware.Service('privileges').addPrivilegeMappingEntry({
    category: 'permissions',
    parent: 'products',
    key: 'vio_finishing_prices',
    roles: {
        viewer: {
            privileges: [
                'vio_finishing_prices:read'
            ]
        },
        editor: {
            privileges: [
                'vio_finishing_prices:update'
            ],
            dependencies: [
                'vio_finishing_prices.viewer'
            ]
        },
        creator: {
            privileges: [
                'vio_finishing_prices:create'
            ],
            dependencies: [
                'vio_finishing_prices.viewer',
                'vio_finishing_prices.editor'
            ]
        },
        deleter: {
            privileges: [
                'vio_finishing_prices:delete'
            ],
            dependencies: [
                'vio_finishing_prices.viewer'
            ]
        }
    }
});
