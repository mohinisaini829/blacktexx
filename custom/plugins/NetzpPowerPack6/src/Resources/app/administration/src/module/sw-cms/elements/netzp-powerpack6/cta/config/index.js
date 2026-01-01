const { Component, Mixin } = Shopware;
import template from './sw-cms-el-config-netzp-powerpack6-cta.html.twig';
import './sw-cms-el-config-netzp-powerpack6-cta.scss';

Component.register('sw-cms-el-config-netzp-powerpack6-cta', {
    template,
    inject: ['repositoryFactory'],
    mixins: [Mixin.getByName('cms-element')],

    data() {
        return {
            mediaModalImageIsOpen: false,
            mediaModalImageBackgroundIsOpen: false,
            active: 'tab1'
        };
    },

    computed: {
        mediaRepository() {
            return this.repositoryFactory.create('media');
        },

        uploadTagImage() {
            return `cms-element-media-config-${this.element.id}-image`;
        },
        uploadTagImageBackground() {
            return `cms-element-media-config-${this.element.id}-backgroundimage`;
        },

        previewSourceImage() {
            if (this.element.data && this.element.data.image && this.element.data.image.id) {
                return this.element.data.image;
            }

            return this.element.config.image.value;
        },

        previewSourceImageBackground() {
            if (this.element.data && this.element.data.backgroundImage && this.element.data.backgroundImage.id) {
                return this.element.data.backgroundImage;
            }

            return this.element.config.backgroundImage.value;
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent()
        {
            this.initElementConfig('netzp-powerpack6-cta');
        },

        onImageUpload({ targetId })
        {
            this.mediaRepository.get(targetId, Shopware.Context.api).then((mediaEntity) => {
                this.element.config.image.value = mediaEntity.id;
                this.updateElementDataImage(mediaEntity);
                this.$emit('element-update', this.element);
            });
        },

        onImageBackgroundUpload({ targetId })
        {
            this.mediaRepository.get(targetId, Shopware.Context.api).then((mediaEntity) => {
                this.element.config.backgroundImage.value = mediaEntity.id;
                this.updateElementDataImageBackground(mediaEntity);
                this.$emit('element-update', this.element);
            });
        },

        onImageRemove()
        {
            this.element.config.image.value = null;
            this.updateElementDataImage();
            this.$emit('element-update', this.element);
        },

        onImageBackgroundRemove()
        {
            this.element.config.backgroundImage.value = null;
            this.updateElementDataImageBackground();
            this.$emit('element-update', this.element);
        },

        onSelectionChangesImage(mediaEntity)
        {
            const image = mediaEntity[0];
            this.element.config.image.value = image.id;
            this.updateElementDataImage(image);
            this.$emit('element-update', this.element);
        },

        onSelectionChangesImageBackground(mediaEntity)
        {
            const image = mediaEntity[0];
            this.element.config.backgroundImage.value = image.id;
            this.updateElementDataImageBackground(image);
            this.$emit('element-update', this.element);
        },

        updateElementDataImage(image = null)
        {
            this.$set(this.element.data, 'imageId', image === null ? null : image.id);
            this.$set(this.element.data, 'image', image);
        },

        updateElementDataImageBackground(image = null)
        {
            this.$set(this.element.data, 'backgroundImageId', image === null ? null : image.id);
            this.$set(this.element.data, 'backgroundImage', image);
        },

        onOpenMediaModalImage()
        {
            this.mediaModalImageIsOpen = true;
        },

        onOpenMediaModalImageBackground()
        {
            this.mediaModalImageBackgroundIsOpen = true;
        },

        onCloseModalImage()
        {
            this.mediaModalImageIsOpen = false;
        },

        onCloseModalImageBackground()
        {
            this.mediaModalImageBackgroundIsOpen = false;
        }
    }
});
