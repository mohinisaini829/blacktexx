import template from './htc-popup-list.html.twig';
import './htc-popup-list.scss';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('htc-popup-list', {
    template,

    inject: [
        'repositoryFactory',
        'context'
    ],

    data() {
        return {
            repository: null,
            popups: null
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },


    computed: {
        columns() {
            return [
                {
                    property: 'title',
                    dataIndex: 'title',
                    label: this.$t('htc-popup-snp.list.columnLabel'),
                    routerLink: 'htc.popup.detail',
                    inlineEdit: 'string',
                    allowResize: true,
                    align: 'center',
                    primary: true
                },
                {
                    property: 'priority',
                    dataIndex: 'priority',
                    label: this.$tc('htc-popup-snp.list.columnPriority'),
                    inlineEdit: 'number',
                    align: 'center',
                    allowResize: true,
                }, 
                {
                    property: 'active',
                    dataIndex: 'active',
                    label: this.$tc('htc-popup-snp.list.columnStatus'),
                    allowResize: true,
                    align: 'center',
                    inlineEdit: false,
                }, 
            ];
        }
    },

    created() {
        this.repository = this.repositoryFactory.create('htc_popup');

        this.repository
            .search(new Criteria(), Shopware.Context.api)
            .then((result) => {
                this.popups = result;
            });
    }
});
