import './component';
import './config';
import './preview';
const Criteria = Shopware.Data.Criteria;

Shopware.Service('cmsService').registerCmsElement({
    name: 'netzp-powerpack6-cta2',
    label: 'sw-cms.netzp-powerpack6.elements.cta2.label',
    component: 'sw-cms-el-netzp-powerpack6-cta2',
    configComponent: 'sw-cms-el-config-netzp-powerpack6-cta2',
    previewComponent: 'sw-cms-el-preview-netzp-powerpack6-cta2',

    defaultConfig: {
        numberOfElements: {
            source: 'static',
            value: 1
        },

        height: {
            source: 'static',
            value: 'auto'
        },

        gap: {
            source: 'static',
            value: '.5rem'
        },

        direction: {
            source: 'static',
            value: 'column' // row|column
        },

        justifyContent: {
            source: 'static',
            value: 'center' // flex-start|flex-end|center|space-between|space-around
        },

        alignItems: {
            source: 'static',
            value: 'center' // stretch|flex-start|flex-end|center
        },

        backgroundImage: {
            source: 'static',
            value: null,
            entity: {
                name: 'media'
            }
        },

        backgroundImageMode: {
            source: 'static',
            value: 'cover'
        },

        backgroundImageAlign: {
            source: 'static',
            value: 'center center'
        },

        backgroundColor: {
            source: 'static',
            value: ''
        },

        url: {
            source: 'static',
            value: ''
        },

        urlNewWindow: {
            source: 'static',
            value: false
        },

        elements:  {
            source: 'static',
            value: []
        }
    },

    collect: function collect(elem)
    {
        const criteriaList = {};

        var imageIds = [];
        elem.config.elements.value.forEach(function(element, n) {
            if(element.image.value && element.image.source !== 'mapped') {
                imageIds.push(element.image.value);
            }
        })

        if(elem.config.backgroundImage.value && elem.config.backgroundImage.source !== 'mapped') {
            imageIds.push(elem.config.backgroundImage.value);
        }

        const entityData = {
            value: imageIds,
            name: 'media',
            key: 'images',
            searchCriteria: new Criteria()
        };

        entityData.searchCriteria.setIds(imageIds);
        criteriaList["entity-media"] = entityData;

        return criteriaList;
    },

    enrich: function enrich(elem, data)
    {
        if (Object.keys(data).length < 1) {
            return;
        }

        let tmp = [];
        elem.config.elements.value.forEach(function(element, n) {
            if(element.type === 'image') {
                tmp[n] = { image: data[`entity-media`].get(element.image.value) };
            }
        });

        if(elem.config.backgroundImage.value && elem.config.backgroundImage.source !== 'mapped') {
            tmp['backgroundImage'] = data[`entity-media`].get(elem.config['backgroundImage'].value);
        }

        elem.data = tmp;
    }
});

// see https://www.cssportal.com/css-flexbox-generator/
/* elements config
   {
       type: 'text',   // text|button|image
       contents: '',   // text for text/button, mediaId for image
       mode: '',       // text: - / button: primary|secondary / image: rounded|fullwidth
       color: '#000000',
       backgroundColor: '#ffffff',
       borderColor: '',
       borderWidth: '0px',
       height: '',
       width: '',
       align-self: 'auto', // auto|flex-start|flex-end|center|stretch
       url: '',
       urlNewWindow: false,
   }
*/
