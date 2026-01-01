import './component';
import './config';
import './preview';

Shopware.Service('cmsService').registerCmsElement({
    name: 'netzp-powerpack6-testimonial',
    label: 'sw-cms.netzp-powerpack6.elements.testimonial.label',
    component: 'sw-cms-el-netzp-powerpack6-testimonial',
    configComponent: 'sw-cms-el-config-netzp-powerpack6-testimonial',
    previewComponent: 'sw-cms-el-preview-netzp-powerpack6-testimonial',

    defaultConfig: {
        media: {
            source: 'static',
            value: null,
            required: true,
            entity: {
                name: 'media'
            }
        },

        title: {
            source: 'static',
            value: 'Lorem ipsum'
        },

        contents: {
            source: 'static',
            value: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.'
        },

        name: {
            source: 'static',
            value: 'Lorem ipsum'
        },

        name2: {
            source: 'static',
            value: 'Lorem ipsum dolor sit amet.'
        }
    }
});
