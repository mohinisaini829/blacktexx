import template from './sw-cms-section.html.twig';

const { Component } = Shopware;

Component.override('sw-cms-section', {
    template,

    computed: {
        sectionStyles() {
            let parentStyles = this.$super('sectionStyles');

            if(this.hasCustomFields()) {
                let customFields = this.section.customFields['netzp_pp'],
                    color1 = customFields['color1'],
                    color2 = customFields['color2'],
                    colorAngle = customFields['colorAngle'];

                if(color1 != '' && color2 != '') {
                    parentStyles['background-image'] = 'linear-gradient(' + colorAngle + 'deg, ' + color1 + ', ' + color2 + ')';
                    delete parentStyles['background-color'];
                }
            }

            return parentStyles;
        }
    },

    methods: {
        hasCustomFields: function () {
            return this.section && this.section.customFields && this.section.customFields['netzp_pp'];
        },
    }
});
