import './component';
import './config';
import './preview';
const Criteria = Shopware.Data.Criteria;

Shopware.Service('cmsService').registerCmsElement({
    name: 'netzp-powerpack6-card',
    label: 'sw-cms.netzp-powerpack6.elements.card.label',
    component: 'sw-cms-el-netzp-powerpack6-card',
    configComponent: 'sw-cms-el-config-netzp-powerpack6-card',
    previewComponent: 'sw-cms-el-preview-netzp-powerpack6-card',

    defaultConfig: {
        height: {
            source: 'static',
            value: 10
        },

        type: {
            source: 'static',
            value: 'flip'
        },

        icon: { source: 'static', value: '' },

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

        text1: {
            source: 'static',
            value: 'Lorem!'
        },

        text2: {
            source: 'static',
            value: 'Impsum!'
        },

        imageCover1: { source: 'static', value: true },
        imageCover2: { source: 'static', value: true },

        imageOpacity1: { source: 'static', value: 100 },
        imageOpacity2: { source: 'static', value: 100 },

        backgroundColor1: { source: 'static', value: '#eeeeee' },
        backgroundColor2: { source: 'static', value: '#eeeeee' },

        color1: { source: 'static', value: '#000000' },
        color2: { source: 'static', value: '#000000' },

        blurText: {
            source: 'static',
            value: false
        },

        url: {
            source: 'static',
            value: ''
        },

        urlNewWindow: {
            source: 'static',
            value: false
        },

        urlText: {
            source: 'static',
            value: 'Clicksum!'
        },
    },

    collect: function collect(elem) {
        const criteriaList = {};

        var imageIds = [];
        if(elem.config['image1'].value) {
            imageIds.push(elem.config['image1'].value);
        }
        if(elem.config['image2'].value) {
            imageIds.push(elem.config['image2'].value);
        }

        const entityImage = elem.config['image1'].entity;
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

            if(entityKey == "media") {
                elem.data[configKey] = data[`entity-${entityKey}`].get(elem.config[configKey].value)
            }
        });
    }
});
