const { EntityDefinition } = Shopware;

EntityDefinition.add('santafatex_brand', {
    entity: 'santafatex_brand',
    properties: {
        id: {
            type: 'uuid',
            required: true
        },
        name: {
            type: 'string',
            required: true
        },
        description: {
            type: 'text'
        },
        sizeChartPath: {
            type: 'string'
        },
        videoSliderHtml: {
            type: 'text'
        },
        catalogPdfPath: {
            type: 'string'
        },
        active: {
            type: 'boolean',
            required: true
        },
        displayOrder: {
            type: 'int'
        },
        createdAt: {
            type: 'date'
        },
        updatedAt: {
            type: 'date'
        }
    }
});
