import './sas-tag-detail.scss';
import template from './sas-tag-detail.html.twig';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;
const { mapPropertyErrors } = Shopware.Component.getComponentHelper();

Component.register('sas-tag-detail', {
    template,

    inject: ['repositoryFactory'],

    mixins: [Mixin.getByName('notification')],

    shortcuts: {
        'SYSTEMKEY+S': 'onSave',
        ESCAPE: 'onCancel',
    },

    data() {
        return {
            isLoading: false,
            isSaveSuccessful: false,
            tag: null,
            processSuccess: false,
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(this.identifier),
        };
    },

    computed: {
        identifier() {
            return this.tag !== null ? this.tag.name : '';
        },

        tagRepository() {
            return this.repositoryFactory.create('sas_tag');
        },

        defaultCriteria() {
            const criteria = new Criteria();
            criteria.addAssociation('blogs');

            return criteria;
        },

        ...mapPropertyErrors('tag', ['name', 'blogs']),
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.isLoading = true;

            this.tagRepository
                .get(
                    this.$route.params.id,
                    Shopware.Context.api,
                    this.defaultCriteria,
                )
                .then((tag) => {
                    this.tag = tag;
                    this.isLoading = false;
                });
        },

        saveFinish() {
            this.isSaveSuccessful = false;
        },

        async onSave() {
            this.isLoading = true;
            this.isSaveSuccessful = false;

            return this.tagRepository
                .save(this.tag, Shopware.Context.api)
                .then(() => {
                    this.isLoading = false;
                    this.isSaveSuccessful = true;
                    this.createNotificationSuccess({
                        message: this.$tc(
                            'sas-tag.detail.messageSaveSuccess',
                            {
                                name: `${this.tag.name}`,
                            },
                            0,
                        ),
                    });
                    this.$router.push({
                        name: 'blog.module.tag.detail',
                        params: { id: this.tag.id },
                    });
                })
                .catch((exception) => {
                    this.createNotificationError({
                        message: this.$tc(
                            'global.notification.unspecifiedSaveErrorMessage',
                        ),
                    });
                    this.isLoading = false;
                    throw exception;
                });
        },

        onCancel() {
            this.$router.push({ name: 'sas.blog.tag.index' });
        },
    },
});
