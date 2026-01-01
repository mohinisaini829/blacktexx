import template from './brand-detail.html.twig';
import './brand-detail.scss';

const { Component, Mixin } = Shopware;
const { mapPropertyErrors } = Component.getComponentHelper();

export default Component.register('brand-detail', {
    template,

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('placeholder'),
    ],

    data() {
        return {
            brand: null,
            isLoading: false,
            isSaveSuccessful: false,
            isNewBrand: true,
            isUploadingFile: false,
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(this.identifier),
        };
    },

    computed: {
        ...mapPropertyErrors('brand', ['name', 'description', 'videoSliderHtml']),

        brandRepository() {
            return this.repositoryFactory.create('santafatex_brand');
        },

        identifier() {
            return this.brand && this.brand.name ? this.brand.name : '';
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            if (this.$route.params.id) {
                this.isNewBrand = false;
                this.loadBrand();
            } else {
                this.brand = this.brandRepository.create(Shopware.Context.api);
                this.brand.active = true;
                this.brand.displayOrder = 0;
            }
        },

        loadBrand() {
            this.isLoading = true;
            this.brandRepository.get(this.$route.params.id, Shopware.Context.api)
                .then((brand) => {
                    this.brand = brand;
                    this.isLoading = false;
                })
                .catch((error) => {
                    console.error('Error loading brand:', error);
                    this.isLoading = false;
                    this.createNotificationError({
                        title: this.$t('sw-santafatex.brands.notification.saveError'),
                        message: this.$t('sw-santafatex.brands.notification.loadErrorMessage'),
                    });
                });
        },

        saveBrand() {
            if (!this.brand.name) {
                this.createNotificationError({
                    title: this.$t('sw-santafatex.brands.notification.saveError'),
                    message: 'Brand name is required',
                });
                return;
            }

            this.isSaveSuccessful = false;
            this.isLoading = true;

            this.brandRepository.save(this.brand, Shopware.Context.api)
                .then(() => {
                    this.isLoading = false;
                    this.isSaveSuccessful = true;

                    if (this.isNewBrand) {
                        this.$router.push({
                            name: 'santafatex.brands.detail',
                            params: { id: this.brand.id },
                        });
                        this.isNewBrand = false;
                    }

                    this.createNotificationSuccess({
                        title: this.$t('sw-santafatex.brands.notification.saveSuccess'),
                        message: this.$t('sw-santafatex.brands.notification.saveSuccessMessage'),
                    });
                })
                .catch((error) => {
                    this.isLoading = false;
                    console.error('Error saving brand:', error);
                    this.createNotificationError({
                        title: this.$t('sw-santafatex.brands.notification.saveError'),
                        message: error.response?.data?.message || 'An error occurred while saving',
                    });
                });
        },

        onSizeChartFileUpload(event) {
            const file = event.target.files[0];
            if (file) {
                this.uploadFile(file, 'sizeChartPath');
            }
        },

        onCatalogFileUpload(event) {
            const file = event.target.files[0];
            if (file) {
                this.uploadFile(file, 'catalogPdfPath');
            }
        },

        uploadFile(file, fieldName) {
            // Validate file
            const allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif'];
            const fileExtension = file.name.split('.').pop().toLowerCase();

            if (!allowedExtensions.includes(fileExtension)) {
                this.createNotificationError({
                    title: this.$t('sw-santafatex.brands.notification.uploadError'),
                    message: `Invalid file type. Allowed: ${allowedExtensions.join(', ')}`,
                });
                return;
            }

            // Validate file size (5MB max)
            const maxSize = 5 * 1024 * 1024;
            if (file.size > maxSize) {
                this.createNotificationError({
                    title: this.$t('sw-santafatex.brands.notification.uploadError'),
                    message: 'File size exceeds 5MB limit',
                });
                return;
            }

            this.isUploadingFile = true;
            const formData = new FormData();
            formData.append('file', file);
            formData.append('fieldName', fieldName);

            // For now, we'll store the file name in the field
            // In a real implementation, you would upload to a server endpoint
            const timestamp = Date.now();
            const filename = `${fieldName}-${timestamp}.${fileExtension}`;
            this.brand[fieldName] = `/uploads/brands/${fieldName}s/${filename}`;

            this.isUploadingFile = false;

            this.createNotificationSuccess({
                title: this.$t('sw-santafatex.brands.notification.uploadSuccess'),
                message: 'File uploaded successfully. Save the brand to persist changes.',
            });
        },

        onCancel() {
            this.$router.push({ name: 'santafatex.brands.index' });
        },
    },
});