const { Component, Mixin } = Shopware;
import template from './sw-cms-el-netzp-powerpack6-countdown.html.twig';
import './sw-cms-el-netzp-powerpack6-countdown.scss';

Component.register('sw-cms-el-netzp-powerpack6-countdown', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    created() {
        this.createdComponent();
    },

    computed: {
        getStyle() {
            return 'padding: 1rem; ' +
                   'background-color: ' + this.element.config.backgroundColor.value + '; ' +
                   'color: ' + this.element.config.textColor.value + '; ' +
                   'font-size: ' + this.element.config.textSize.value + 'rem; ' +
                   'text-align: ' + this.element.config.textAlign.value;
        },

        getCountdownStyle() {
            return 'text-align: ' + this.element.config.textAlign.value;
        },

        getBoxStyle() {
            return 'background-color: ' + this.element.config.boxColor.value;
        },

        getButtonStyle() {
            return 'background-color: ' + this.element.config.buttonColor.value + '; ' +
                   'color: ' + this.element.config.buttonTextColor.value + '; ';
        },

        getButtonClass() {
            var c = '';
            if(this.element.config.textAlign.value == 'left') {
                c += 'btn-absolute';
            }
            return c;
        },

        getTitleStyle() {
            return 'color: ' + this.element.config.textColor.value + '; ';
        },

        getEndDateClass() {
            var c = '';
            if(this.element.config.textAlign.value == 'right') {
                c += 'left';
            }
            else {
                c += 'right';
            }
            return c;
        },

        getEndDateFormatted() {
            if(this.element.config.enddate.value === '') {
                return '---';
            }

            const dt =  Date.parse(this.element.config.enddate.value);
            var options = {
                year: 'numeric', month: 'numeric', day: 'numeric',
                hour: 'numeric', minute: 'numeric', second: 'numeric',
                hour12: false
            };
            return (new Intl.DateTimeFormat(Shopware.State.get('session').currentLocale, options).format(dt));
        }
    },

    methods: {
        createdComponent() {
            this.initElementConfig('netzp-powerpack6-countdown');
        }
    }
});
