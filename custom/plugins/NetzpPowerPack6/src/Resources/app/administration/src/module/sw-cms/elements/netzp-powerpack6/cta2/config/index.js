const { Component, Mixin } = Shopware;
import template from './sw-cms-el-config-netzp-powerpack6-cta2.html.twig';
import './sw-cms-el-config-netzp-powerpack6-cta2.scss';

Component.register('sw-cms-el-config-netzp-powerpack6-cta2', {
    template,

    inject: [
        'repositoryFactory'
    ],

    mixins: [
        Mixin.getByName('cms-element')
    ],

    data() {
        return {
            mediaModalImageIsOpen: false,
            mediaModalImageBackgroundIsOpen: false,
            showTabRemoveConfirm: false,
            activeTabIndex: 0
        };
    },

    watch: {
        'element.config.numberOfElements.value': {
            deep: true,
            handler(newVal) {
                if(this.element.config.elements.value.length < parseInt(newVal)) {
                    this.initElements();
                }
            }
        }
    },

    computed: {
        mediaRepository()
        {
            return this.repositoryFactory.create('media');
        },

        uploadTagImageBackground() {
            return `cms-element-cta2-config-${this.element.id}-backgroundimage`;
        },

        previewSourceImageBackground() {
            if (this.element.data && this.element.data.backgroundImage && this.element.data.backgroundImage.id) {
                return this.element.data.backgroundImage;
            }

            return this.element.config.backgroundImage.value;
        }
    },

    created()
    {
        this.createdComponent();
    },

    methods: {
        getElement(n)
        {
            return this.element.config.elements.value[n];
        },

        getElementType(n)
        {
            return this.getElement(n).type;
        },

        getTabName(n)
        {
            let type = this.getElementType(n);

            return this.$t('sw-cms.netzp-powerpack6.elements.cta2.config.element.type.' + type) + ' ' + (n+1);
        },

        showButtonType(n)
        {
            if(this.getElementType(n) === 'button')
            {
                return true;
            }

            return false;
        },

        showColorSettings(n)
        {

            if(this.getElementType(n) === 'button' && this.getElement(n).mode !== 'custom')
            {
                return false;
            }

            return true;
        },

        uploadTagImage(n)
        {
            return `cms-element-cta2-${this.element.id}-element${n}-image`;
        },

        previewSourceImage(n)
        {
            if (this.element.data && this.element.data[n] &&
                this.element.data[n].image && this.element.data[n].image.id) {
                return this.element.data[n].image;
            }

            return this.element.config.elements.value[n].image.value;
        },

        onImageUpload({ targetId }, n)
        {
            this.mediaRepository.get(targetId, Shopware.Context.api).then((mediaEntity) => {
                this.element.config.elements.value[n].image.value = mediaEntity.id;
                this.updateElementDataImage(n, mediaEntity);
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

        onImageRemove(n)
        {
            this.element.config.elements.value[n].image.value = null;
            this.updateElementDataImage(n);
            this.$emit('element-update', this.element);
        },

        onImageBackgroundRemove()
        {
            this.element.config.backgroundImage.value = null;
            this.updateElementDataImageBackground();
            this.$emit('element-update', this.element);
        },

        onSelectionChangedImage( mediaEntity, n )
        {
            const image = mediaEntity[0];
            this.element.config.elements.value[n].image.value = image.id;
            this.updateElementDataImage(n, image);
            this.$emit('element-update', this.element);
        },

        onSelectionChangesImageBackground(mediaEntity)
        {
            const image = mediaEntity[0];
            this.element.config.backgroundImage.value = image.id;
            this.updateElementDataImageBackground(image);
            this.$emit('element-update', this.element);
        },

        updateElementDataImage(n, image = null)
        {
            let data = [];
            if(this.element.data) {
                data = this.element.data;
            }

            data[n] = {
                'imageId': image === null ? null : image.id,
                'image': image
            };

            this.$set(this.element, 'data', data);
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
        },

        setActiveTab(item)
        {
            this.activeTabIndex = item.$attrs.index;
        },

        onRemoveTab()
        {
            this.showTabRemoveConfirm = true;
        },

        removeTabConfirmed()
        {
            this.showTabRemoveConfirm = false;

            if(this.activeTabIndex > 0 && this.element.config.numberOfElements.value > 1)
            {
                this.element.config.numberOfElements.value = this.element.config.numberOfElements.value - 1;
                this.element.config.elements.value.splice(this.activeTabIndex - 1, 1);

                this.$nextTick(() => {
                    const nextIndex = Math.max(1, Math.min(this.element.config.numberOfElements.value, this.activeTabIndex));
                    const nextChild = this.$refs.netzpCta2Tabs.$children.find(item => item.$attrs.index == nextIndex);

                    this.$refs.netzpCta2Tabs.setActiveItem(nextChild);
                    this.$refs.netzpCta2Tabs.scrollToItem(nextChild);
                });
            }
        },

        onAddTab()
        {
            if(this.element.config.numberOfElements.value < 10) {
                this.element.config.numberOfElements.value = this.element.config.numberOfElements.value + 1;
                this.$nextTick(() => {
                    const newIndex = this.element.config.numberOfElements.value;
                    const newChild = this.$refs.netzpCta2Tabs.$children.find(item => item.$attrs.index == newIndex);

                    this.$refs.netzpCta2Tabs.setActiveItem(newChild);
                    this.$refs.netzpCta2Tabs.scrollToItem(newChild);
                });
            }
        },

        initElements()
        {
            for(let i = this.element.config.elements.value.length + 1; i <= this.element.config.numberOfElements.value; i++) {
                this.element.config.elements.value.push({
                    type: 'text',
                    contents: {
                        source: 'static',
                        value: 'Element ' + i
                    },
                    image: {
                        source: 'static',
                        value: null,
                        required: false,
                        entity: {
                            name: 'media'
                        }
                    },
                    mode: '',
                    color: '#000000',
                    backgroundColor: '#ffffff',
                    borderColor: '',
                    borderWidth: 0,
                    fontSize: '100%',
                    height: 'auto',
                    width: 'auto',
                    padding: '0 .5rem',
                    alignSelf: 'auto',
                    url: '',
                    urlNewWindow: false
                })
            }
        },

        createdComponent()
        {
            this.initElementConfig('netzp-powerpack6-cta2');
        }
    }
});
