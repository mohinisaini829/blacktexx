import template from './brand-detail.html.twig';
import './brand-detail.scss';

const { Component, Mixin } = Shopware;
const { mapPropertyErrors } = Component.getComponentHelper();

export default Component.register('brand-detail', {
    template,

    inject: ['repositoryFactory'],

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
                        title: 'Error',
                        message: 'Failed to load brand',
                    });
                });
        },

        saveBrand() {
            if (!this.brand.name) {
                this.createNotificationError({
                    title: 'Error',
                    message: 'Brand name is required',
                });
                return;
            }

            this.isSaveSuccessful = false;
            this.isLoading = true;

            console.log('Saving brand:', this.brand);

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
                        title: 'Success',
                        message: 'Brand saved successfully',
                    });
                })
                .catch((error) => {
                    this.isLoading = false;
                    console.error('Error saving brand:', error);
                    console.error('Error details:', error.response);
                    
                    let errorMessage = 'An error occurred while saving';
                    if (error.response?.data?.errors) {
                        const errors = error.response.data.errors;
                        errorMessage = errors.map(e => e.detail || e.title).join(', ');
                    } else if (error.response?.data?.message) {
                        errorMessage = error.response.data.message;
                    }
                    
                    this.createNotificationError({
                        title: 'Error',
                        message: errorMessage,
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
                    title: 'Error',
                    message: `Invalid file type. Allowed: ${allowedExtensions.join(', ')}`,
                });
                return;
            }

            // Validate file size (5MB max)
            const maxSize = 5 * 1024 * 1024;
            if (file.size > maxSize) {
                this.createNotificationError({
                    title: 'Error',
                    message: 'File size exceeds 5MB limit',
                });
                return;
            }

            this.isUploadingFile = true;

            // Create form data for actual file upload
            const formData = new FormData();
            formData.append('file', file);
            
            // Determine subfolder based on field name
            const subfolder = fieldName === 'sizeChartPath' ? 'sizeChartPaths' : 'catalogPdfPaths';
            formData.append('subfolder', subfolder);

            // Upload to custom API endpoint
            const headers = {
                'Authorization': `Bearer ${Shopware.Context.api.authToken.access}`
            };

            fetch('/api/_action/santafatex-brands/upload', {
                method: 'POST',
                body: formData,
                headers: headers
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Upload failed');
                }
                return response.json();
            })
            .then((data) => {
                if (data.success) {
                    // Store the uploaded path
                    this.brand[fieldName] = data.path;
                    
                    this.isUploadingFile = false;

                    this.createNotificationSuccess({
                        title: 'Success',
                        message: 'File uploaded successfully. Save the brand to complete.',
                    });
                } else {
                    throw new Error(data.message || 'Upload failed');
                }
            })
            .catch((error) => {
                console.error('Upload error:', error);
                this.isUploadingFile = false;
                
                this.createNotificationError({
                    title: 'Error',
                    message: 'File upload failed: ' + error.message,
                });
            });
        },

        onClickSave() {
            this.isLoading = true;

            this.brandRepository.save(this.brand, Shopware.Context.api)
                .then(() => {
                    this.isSaveSuccessful = true;
                    this.isLoading = false;

                    this.createNotificationSuccess({
                        title: 'Success',
                        message: 'Brand saved successfully',
                    });

                    if (this.isNewBrand) {
                        this.$router.push({ name: 'santafatex.brands.index' });
                    }
                })
                .catch((error) => {
                    this.isLoading = false;
                    this.createNotificationError({
                        title: 'Error',
                        message: 'Error saving brand',
                    });
                });
        },

        onCancel() {
            this.$router.push({ name: 'santafatex.brands.index' });
        },
    },
});