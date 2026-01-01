Shopware.Service('privileges').addPrivilegeMappingEntry({
    category: 'permissions',
    parent: 'content',
    key: 'sas-tag',
    roles: {
        viewer: {
            privileges: ['sas_tag:read', 'sas_tag_translation:read'],
            dependencies: [],
        },
        editor: {
            privileges: ['sas_tag:update', 'sas_tag_translation:update'],
            dependencies: [],
        },
        creator: {
            privileges: ['sas_tag:create', 'sas_tag_translation:create'],
            dependencies: [],
        },
        deleter: {
            privileges: ['sas_tag:delete', 'sas_tag_translation:delete'],
            dependencies: [],
        },
    },
});
