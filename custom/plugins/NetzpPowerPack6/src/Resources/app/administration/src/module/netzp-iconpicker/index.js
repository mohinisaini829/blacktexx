import template from './netzp-iconpicker.html.twig';
import './netzp-iconpicker.scss';

const { Component, Mixin } = Shopware;

Component.register('netzp-iconpicker', {
    template,

    emits: ['update:value'],

    inject: ['feature'],

    mixins: [
        Mixin.getByName('sw-form-field')
    ],

    props: {
        value: {
            type: String,
            required: false,
            default: '',
        }
    },

    data() {
        return {
            currentValue: this.value,
            doRender: true,
            visible: false,
            searchValue: '',
            styles: {}
        };
    },

    computed: {
        hasPrefix() {
            return this.$scopedSlots.hasOwnProperty('prefix');
        },

        hasSuffix() {
            return this.$scopedSlots.hasOwnProperty('suffix');
        },

        additionalListeners() {
            const additionalListeners = { ...this.$listeners };

            delete additionalListeners.input;
            delete additionalListeners.change;

            return additionalListeners;
        },

        iconList() {
            return this.styles;
        }
    },

    watch: {
        value(value) {
            this.currentValue = value;
        },
    },

    created() {
        this.styles = window['___FONT_AWESOME___'].styles.fas;
    },

    methods: {
        search()
        {
            if(this.searchValue.length < 2)
            {
                this.styles = {}
                return;
            }

            let tmp = {};
            for (let [key, value] of Object.entries(window['___FONT_AWESOME___'].styles.fas))
            {
                if(key.indexOf(this.searchValue) > -1)
                {
                    tmp[key] = value;
                }
            }
            this.styles = tmp;
        },

        onChange(event)
        {
            this.$emit('update:value', event.target.value || '');
            this.forceRerender();
        },

        onInput(event)
        {
            this.$emit('update:value', event.target.value);
        },

        forceRerender()
        {
            this.doRender = false;
            this.$nextTick(() => {
                this.doRender = true;
            });
        },

        showPicker()
        {
            this.visible = ! this.visible;
            if(this.visible) {
                this.$nextTick(() => {
                    this.$refs.search.focus();
                })
            }
        },

        iconSelected(iconName)
        {
            this.currentValue = 'fa-' + iconName;

            this.$emit('update:value', this.currentValue);
            this.forceRerender();

            this.visible = false;
        }
    }
});
