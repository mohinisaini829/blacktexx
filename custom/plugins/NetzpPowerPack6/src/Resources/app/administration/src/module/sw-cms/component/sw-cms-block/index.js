import template from './sw-cms-block.html.twig';
import './sw-cms-block.scss';

const { Component } = Shopware;

Component.override('sw-cms-block', {
    template,

    computed:
    {
        breakpointInfoIsVisible: function()
        {
            var info = this.getBreakpointInfo();

            return info.length > 0 && info.length < 5;
        },

        breakpointInfoNoSelection: function()
        {
            var info = this.getBreakpointInfo();

            if( ! this.hasCustomFields()) return false;
            return info.length === 0;
        },

        hasVisibility: function()
        {
            if(this.hasCustomFields()) {
                let customFields = this.block.customFields['netzp_pp'],
                    ruleId = customFields['ruleId'],
                    showFrom = customFields['showFrom'],
                    showUntil = customFields['showUntil'];

                if(ruleId === undefined) ruleId = null;
                if(showFrom === undefined) showFrom = null;
                if(showUntil === undefined) showUntil = null;

                return ruleId !== null || showFrom !== null || showUntil !== null;
            }

            return false;
        },

        visibilityInfo: function()
        {
            if(this.hasCustomFields()) {
                let customFields = this.block.customFields['netzp_pp'],
                    showFrom = customFields['showFrom'],
                    showUntil = customFields['showUntil'],
                    ruleId = customFields['ruleId'],
                    s = '';

                if(ruleId === undefined) ruleId = null;
                if(showFrom === undefined) showFrom = null;
                if(showUntil === undefined) showUntil = null;

                if(ruleId !== null) {
                    s += this.$tc('sw-cms.netzp-powerpack6.rule.info');
                }

                if(showFrom === null && showUntil === null) {
                    return s;
                }

                if(s !== '') s += ' | ';

                if(showFrom !== null) {
                    let dFrom = new Date(showFrom);
                    s += dFrom.toLocaleDateString();
                }
                else {
                    s += '∞'
                }

                if(showFrom === showUntil) {
                    return s;
                }

                s += ' - ';
                if(showUntil !== null) {
                    let dUntil = new Date(showUntil);
                    s += dUntil.toLocaleDateString();
                }
                else {
                    s += '∞';
                }

                return s;
            }

            return '';
        },

        blockStyles()
        {
            let parentStyles = this.$super('blockStyles');

            if(this.hasCustomFields()) {
                let customFields = this.block.customFields['netzp_pp'],
                    color1 = customFields['color1'],
                    color2 = customFields['color2'],
                    colorAngle = customFields['colorAngle'];

                if(color1 != '' && color2 != '') {
                    parentStyles['background-image'] = 'linear-gradient(' + colorAngle + 'deg, ' + color1 + ', ' + color2 + ')';
                    delete parentStyles['background-color'];
                }
            }

            return parentStyles;
        },
    },

    methods:
    {
        hasCustomFields: function()
        {
            return this.block && this.block.customFields && this.block.customFields['netzp_pp'];
        },

        getBreakpointInfo: function()
        {
            var info = [];
            if(this.hasCustomFields()) {
                const breakpoints = this.block.customFields['netzp_pp'];;
                if(breakpoints['xs']) info.push('XS');
                if(breakpoints['sm']) info.push('SM');
                if(breakpoints['md']) info.push('MD');
                if(breakpoints['lg']) info.push('LG');
                if(breakpoints['xl']) info.push('XL');
            }

            return info;
        }
    }
});
