const { Component, Mixin } = Shopware;
import template from './sw-cms-el-netzp-powerpack6-alert.html.twig';
import './sw-cms-el-netzp-powerpack6-alert.scss';

Component.register('sw-cms-el-netzp-powerpack6-alert', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    data() {
        return {
            demoValueTitle: '',
            demoValueContents: '',
        };
    },

    watch: {
        cmsPageState: {
            deep: true,
            handler() {
                this.updateDemoValues();
            },
        },

        'element.config.title.source': {
            handler() {
                this.updateDemoValues();
            },
        },

        'element.config.contents.source': {
            handler() {
                this.updateDemoValues();
            },
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('netzp-powerpack6-alert');
        },

        updateDemoValues() {
            if (this.element.config.title.source === 'mapped') {
                this.demoValueTitle = this.getDemoValue(this.element.config.title.value);
            }
            if (this.element.config.contents.source === 'mapped') {
                this.demoValueContents = this.getDemoValue(this.element.config.contents.value);
            }
        },

        getAppearance(type) {
            if(type == 0) return "info";
            if(type == 1) return "warning";
            if(type == 2) return "error";
            if(type == 3) return "success";

            return "info";
        }
    }
});
