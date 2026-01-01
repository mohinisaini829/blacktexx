import './component';
import './config';
import './preview';
const Criteria = Shopware.Data.Criteria;

Shopware.Service('cmsService').registerCmsElement({
    name: 'netzp-powerpack6-cta',
    label: 'sw-cms.netzp-powerpack6.elements.cta.label',
    component: 'sw-cms-el-netzp-powerpack6-cta',
    configComponent: 'sw-cms-el-config-netzp-powerpack6-cta',
    previewComponent: 'sw-cms-el-preview-netzp-powerpack6-cta',

    defaultConfig: {
        height: { source: 'static', value: 8 },
        borderColor: { source: 'static', value: '' },
        borderRadius: { source: 'static', value: 0 },
        autoLayout: { source: 'static', value: true },

        backgroundImage: {
            source: 'static',
            value: null,
            entity: {
                name: 'media'
            }
        },
        backgroundColor: { source: 'static', value: '#eeeeee' },
        backgroundImageMode: { source: 'static', value: "cover" },
        backgroundImageAlign: { source: 'static', value: "center center" },

        image: {
            source: 'static',
            value: null,
            entity: {
                name: 'media'
            }
        },
        imagePosX: { source: 'static', value: 'left' },
        imagePosY: { source: 'static', value: 'center' },
        imageSize: { source: 'static', value: 5 },
        imageRounded: { source: 'static', value: true },
        imageBlock: { source: 'static', value: false },

        title: { source: 'static', value: 'Lorem impsum!' },
        titlePosX: { source: 'static', value: 'center' },
        titlePosY: { source: 'static', value: 'top' },
        titleSize: { source: 'static', value: 1.2 },
        titleColor: { source: 'static', value: '#000000' },
        titleBackgroundColor: { source: 'static', value: '' },
        titleShadow: { source: 'static', value: false },

        text: { source: 'static', value: 'Lorem ipsum<br>lorem impsum<br>lorem impsum.' },
        textPosX: { source: 'static', value: 'center' },
        textPosY: { source: 'static', value: 'center' },
        textSize: { source: 'static', value: 0.9 },
        textWidth: { source: 'static', value: 100 },
        textColor: { source: 'static', value: '#000000' },
        textBackgroundColor: { source: 'static', value: '' },
        textAlign: { source: 'static', value: 'center' },
        textShadow: { source: 'static', value: false },

        button: { source: 'static', value: 'Clicksum!' },
        buttonPosX: { source: 'static', value: 'center' },
        buttonPosY: { source: 'static', value: 'bottom' },
        buttonSize: { source: 'static', value: 1.0 },
        buttonBackgroundColor: { source: 'static', value: '' },
        buttonColor: { source: 'static', value: '' },
        buttonBorderColor: { source: 'static', value: '' },
        buttonUrl: { source: 'static', value: '' },
        buttonTargetBlank: { source: 'static', value: false },
        buttonBlock: { source: 'static', value: false },
        buttonRounded: { source: 'static', value: false },
        buttonShadow: { source: 'static', value: false },
    },

    collect: function collect(elem) {
        const criteriaList = {};

        var imageIds = [];
        if(elem.config.image.value && elem.config.image.source !== 'mapped') {
            imageIds.push(elem.config.image.value);
        }
        if(elem.config.backgroundImage.value && elem.config.backgroundImage.source !== 'mapped') {
            imageIds.push(elem.config.backgroundImage.value);
        }

        const entityImage = elem.config.image.entity;
        const entityData = {
            value: imageIds,
            key: 'image',
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
