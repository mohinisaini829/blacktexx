import './sas-textarea-field.scss';
import template from './sas-textarea-field.html.twig';

const { Component } = Shopware;

const name = Shopware.Component.getComponentRegistry().has(
    'sw-textarea-field-deprecated',
)
    ? 'sw-textarea-field-deprecated'
    : 'sw-textarea-field';

// Deprecated: sw-textarea-field-deprecated, use sw-textarea-field instead
Component.extend('sas-textarea-field', name, {
    template,

    props: {
        maxLength: {
            type: Number,
            required: false,
            default: 255,
        },
        textCountBeforeWarning: {
            type: Number,
            required: false,
            default: 20,
        },
    },

    watch: {
        value(value) {
            if (!value) {
                return;
            }

            if (value.length > this.maxLength) {
                this.currentValue = value.substr(0, this.maxLength);
                this.$emit('input', this.currentValue);

                return;
            }

            this.currentValue = value;
        },
    },

    computed: {
        currentLength() {
            return this.currentValue ? this.currentValue.length : 0;
        },

        charLeft() {
            return this.maxLength - this.currentLength;
        },

        hasWarning() {
            return (
                this.currentLength > 0 &&
                this.charLeft <= this.textCountBeforeWarning
            );
        },
    },
});
