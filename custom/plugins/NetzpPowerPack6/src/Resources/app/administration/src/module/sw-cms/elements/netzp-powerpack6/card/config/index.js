const { Component, Mixin } = Shopware;
import template from './sw-cms-el-config-netzp-powerpack6-card.html.twig';
import './sw-cms-el-config-netzp-powerpack6-card.scss';

Component.register('sw-cms-el-config-netzp-powerpack6-card', {
    template,
    inject: ['repositoryFactory'],
    mixins: [Mixin.getByName('cms-element')],

    emits: ['update:value'],

    data() {
        return {
            mediaModalImage1IsOpen: false,
            mediaModalImage2IsOpen: false,
            active: 'front'
        };
    },

    computed: {
        mediaRepository() {
            return this.repositoryFactory.create('media');
        },

        uploadTagImage1() {
            return `cms-element-media-config-${this.element.id}-image1`;
        },

        uploadTagImage2() {
            return `cms-element-media-config-${this.element.id}-image2`;
        },

        previewSourceImage1() {
            if (this.element.data && this.element.data.image1 && this.element.data.image1.id) {
                return this.element.data.image1;
            }

            return this.element.config.image1.value;
        },

        previewSourceImage2() {
            if (this.element.data && this.element.data.image2 && this.element.data.image2.id) {
                return this.element.data.image2;
            }

            return this.element.config.image2.value;
        },

        isTwoSided()
        {
            return this.element.config.type.value === 'flip' ||
                this.element.config.type.value === 'reveal';
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('netzp-powerpack6-card');
        },

        onImage1Upload({ targetId }) {
            this.mediaRepository.get(targetId, Shopware.Context.api).then((mediaEntity) => {
                this.element.config.image1.value = mediaEntity.id;
                this.updateElementDataImage1(mediaEntity);
                this.$emit('element-update', this.element);
            });
        },
        onImage2Upload({ targetId }) {
            this.mediaRepository.get(targetId, Shopware.Context.api).then((mediaEntity) => {
                this.element.config.image2.value = mediaEntity.id;
                this.updateElementDataImage2(mediaEntity);
                this.$emit('element-update', this.element);
            });
        },

        onImage1Remove() {
            this.element.config.image1.value = null;
            this.updateElementDataImage1();
            this.$emit('element-update', this.element);
        },

        onImage2Remove() {
            this.element.config.image2.value = null;
            this.updateElementDataImage2();
            this.$emit('element-update', this.element);
        },

        onSelectionChangesImage1(mediaEntity) {
            const image1 = mediaEntity[0];
            this.element.config.image1.value = image1.id;
            this.updateElementDataImage1(image1);
            this.$emit('element-update', this.element);
        },

        onSelectionChangesImage2(mediaEntity) {
            const image2 = mediaEntity[0];
            this.element.config.image2.value = image2.id;
            this.updateElementDataImage2(image2);
            this.$emit('element-update', this.element);
        },

        updateElementDataImage1(image1 = null) {
            this.$set(this.element.data, 'image1Id', image1 === null ? null : image1.id);
            this.$set(this.element.data, 'image1', image1);
        },

        updateElementDataImage2(image2 = null) {
            this.$set(this.element.data, 'image2Id', image2 === null ? null : image2.id);
            this.$set(this.element.data, 'image2', image2);
        },

        onOpenMediaModalImage1() {
            this.mediaModalImage1IsOpen = true;
        },

        onOpenMediaModalImage2() {
            this.mediaModalImage2IsOpen = true;
        },

        onCloseModalImage1() {
            this.mediaModalImage1IsOpen = false;
        },

        onCloseModalImage2() {
            this.mediaModalImage2IsOpen = false;
        }
    }
});
