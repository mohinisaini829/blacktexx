import template from './netzp-cmsconfig.html.twig';
import './netzp-cmsconfig.scss';

const { Component } = Shopware;

Component.register('netzp-cmsconfig', {
    template,

    props:
    {
        model:
        {
            type: Object,
            required: true
        }
    },

    computed:
    {
        noBreakpointSelected: function()
        {
            return this.getBreakpointInfo().length === 0;
        },

        breakpointsSelected: function()
        {
            return this.getBreakpointInfo().length < 5;
        },

        // NE 28.04.25 - if there are multiple blocks (of same type?) then the custom fields where not initialized
        // b/c this component was already registered and not init'ed again.
        // getCustomFields() is called in template netzp-cmsconfig.htmltwig on top
        getCustomFields: function()
        {
            this.initCustomFields();

            return this.model.customFields;
        }
    },

    methods:
    {
        initCustomFields()
        {
            if ( ! this.model.customFields) {
                this.$set(this.model, 'customFields', {});
            }
            if (! this.model.customFields.netzp_pp) {
                this.$set(this.model.customFields, 'netzp_pp', {
                    xs: true, sm: true, md: true, lg: true, xl: true,
                    color1: '', color2: '', colorAngle: 0,
                    showFrom: null, showUntil: null,
                    ruleId: null
                });
            }
        },

        getBreakpointInfo: function()
        {
            var info = [];
            if(this.model.customFields && this.model.customFields['netzp_pp']) {
                const breakpoints = this.model.customFields['netzp_pp'];
                if(breakpoints['xs']) info.push('XS');
                if(breakpoints['sm']) info.push('SM');
                if(breakpoints['md']) info.push('MD');
                if(breakpoints['lg']) info.push('LG');
                if(breakpoints['xl']) info.push('XL');
            }

            return info;
        },

        swapColors()
        {
            let color1 = this.model.customFields.netzp_pp['color1'];
            let color2 = this.model.customFields.netzp_pp['color2'];

            this.model.customFields.netzp_pp['color1'] = color2;
            this.model.customFields.netzp_pp['color2'] = color1;
       },

        toggleAll(value)
        {
            let color1 =  this.model.customFields.netzp_pp['color1'];
            let color2 =  this.model.customFields.netzp_pp['color2'];
            let colorAngle =  this.model.customFields.netzp_pp['colorAngle'];
            let ruleId =  this.model.customFields.netzp_pp['ruleId'];
            let showFrom =  this.model.customFields.netzp_pp['showFrom'];
            let showUntil =  this.model.customFields.netzp_pp['showUntil'];

            if(this.model.customFields.netzp_pp['xs'] &&
                this.model.customFields.netzp_pp['sm'] &&
                this.model.customFields.netzp_pp['md'] &&
                this.model.customFields.netzp_pp['lg'] &&
                this.model.customFields.netzp_pp['xl']) {
                this.$set(this.model.customFields, 'netzp_pp', {
                    xs: false, sm: false, md: false, lg: true, xl: true,
                    color1: color1, color2: color2, colorAngle: colorAngle,
                    ruleId: ruleId, showFrom: showFrom, showUntil: showUntil
                });
            }
            else {
                this.$set(this.model.customFields, 'netzp_pp', {
                    xs: value, sm: value, md: value, lg: value, xl: value,
                    color1: color1, color2: color2, colorAngle: colorAngle,
                    ruleId: ruleId, showFrom: showFrom, showUntil: showUntil
                });
            }
        },

        saveRule(id)
        {
            this.model.customFields.netzp_pp['ruleId'] = id;
        },

        dismissRule()
        {
            this.model.customFields.netzp_pp['ruleId'] = null;
        }
    },

    mounted: function()
    {
        this.initCustomFields();
    }
});
