import './component';
import './config';
import './preview';
const Criteria = Shopware.Data.Criteria;

Shopware.Service('cmsService').registerCmsElement({
    name: 'netzp-powerpack6-imagecompare',
    label: 'sw-cms.netzp-powerpack6.elements.imagecompare.label',
    component: 'sw-cms-el-netzp-powerpack6-imagecompare',
    configComponent: 'sw-cms-el-config-netzp-powerpack6-imagecompare',
    previewComponent: 'sw-cms-el-preview-netzp-powerpack6-imagecompare',

    defaultConfig: {
        height: {
            source: 'static',
            value: 20
        },

        image1: {
            source: 'static',
            value: null,
            entity: {
                name: 'media'
            }
        },

        image2: {
            source: 'static',
            value: null,
            entity: {
                name: 'media'
            }
        },

        controlColor: { source: 'static', value: '#ffffff' },
        smoothing: { source: 'static', value: false },
        addCircle: { source: 'static', value: false },
        verticalMode: { source: 'static', value: false },
        startingPoint: { source: 'static', value: 50 },
        labelBefore: { source: 'static', value: '' },
        labelAfter: { source: 'static', value: '' }
    },

    collect: function collect(elem) {
        const criteriaList = {};

        var imageIds = [];
        if(elem.config.image1.value && elem.config.image1.source !== 'mapped') {
            imageIds.push(elem.config.image1.value);
        }
        if(elem.config.image2.value && elem.config.image2.source !== 'mapped') {
            imageIds.push(elem.config.image2.value);
        }

        const entityImage = elem.config.image1.entity;
        const entityData = {
            value: imageIds,
            key: 'image1',
            searchCriteria: entityImage.criteria ? entityImage.criteria : new Criteria(),
            ...entityImage
        };

        entityData.searchCriteria.setIds(imageIds);
        criteriaList["entity-media"] = entityData;

        return criteriaList;
    },

    enrich: function enrich(elem, data) {
        if (Object.keys(data).length < 1) {
            return;
        }

        Object.keys(elem.config).forEach((configKey) => {
            const entity = elem.config[configKey].entity;

            if (!entity) {
                return;
            }

            const entityKey = entity.name;
            if (!data[`entity-${entityKey}`]) {
                return;
            }

            if(entityKey === "media") {
                elem.data[configKey] = data[`entity-${entityKey}`].get(elem.config[configKey].value)
            }
        });
    }
});
