import template from './sw-cms-el-config-form.html.twig';

const { Component } = Shopware;

Component.override('sw-cms-el-config-form', {
    template,
    computed: {
        formTypeOptions() {
            const options = this.$super('formTypeOptions');
            options.push({
                label: this.$tc('myfav-inquiry.label'),
                value: 'myfavinquiry'
            });
            return options;
        }
    }
});
