import template from './brand-detail.html.twig';
import './brand-detail.scss';

const { Component, Mixin } = Shopware;
const { mapPropertyErrors } = Component.getComponentHelper();

export default Component.register('brand-detail', {
    template,

    inject: ['repositoryFactory', 'syncService'],

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
                this.brand = {
                    id: null,
                    name: '',
                    description: null,
                    manufacturerId: null,
                    sizeChartPath: null,
                    videoSliderHtml: null,
                    catalogPdfPath: null,
                    active: true,
                    displayOrder: 0
                };
            }
        },

        loadBrand() {
            this.isLoading = true;
            const criteria = new Shopware.Data.Criteria();
            criteria.addAssociation('manufacturer');
            
            this.brandRepository.get(this.$route.params.id, Shopware.Context.api, criteria)
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
            if (!this.brand.manufacturerId) {
                this.createNotificationError({
                    title: 'Error',
                    message: 'Please select a manufacturer',
                });
                return;
            }

            this.isSaveSuccessful = false;
            this.isLoading = true;

            console.log('=== BRAND BEFORE SAVE ===');
            console.log(this.brand);

            const headers = {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': `Bearer ${Shopware.Context.api.authToken.access}`
            };

            // Get manufacturer name for brand name
            const manufacturerRepo = this.repositoryFactory.create('product_manufacturer');
            
            return manufacturerRepo.get(this.brand.manufacturerId, Shopware.Context.api)
                .then((manufacturer) => {
                    const payload = {
                        id: this.brand.id || this.createId(),
                        name: manufacturer.name,
                        description: this.brand.description || null,
                        manufacturerId: this.brand.manufacturerId,
                        sizeChartPath: this.brand.sizeChartPath || null,
                        videoSliderHtml: this.brand.videoSliderHtml || null,
                        catalogPdfPath: this.brand.catalogPdfPath || null,
                        active: this.brand.active !== false,
                        displayOrder: parseInt(this.brand.displayOrder) || 0
                    };

                    return this.saveBrandData(payload);
                })
                .catch((error) => {
                    this.isLoading = false;
                    console.error('Error fetching manufacturer:', error);
                    this.createNotificationError({
                        title: 'Error',
                        message: 'Failed to fetch manufacturer details',
                    });
                });
        },

        saveBrandData(payload) {

            // Use sync API
            const syncPayload = {
                'write-santafatex_brand': {
                    entity: 'santafatex_brand',
                    action: 'upsert',
                    payload: [payload]
                }
            };

            console.log('=== SYNC PAYLOAD ===');
            console.log(JSON.stringify(syncPayload, null, 2));

            fetch('/api/_action/sync', {
                method: 'POST',
                headers: headers,
                body: JSON.stringify(syncPayload)
            })
                .then(response => {
                    console.log('Response status:', response.status);
                    
                    if (!response.ok) {
                        return response.text().then(text => {
                            console.error('Error response:', text);
                            let errorData;
                            try {
                                errorData = JSON.parse(text);
                            } catch(e) {
                                errorData = { message: text };
                            }
                            return Promise.reject(errorData);
                        });
                    }
                    
                    return response.json();
                })
                .then((data) => {
                    console.log('=== SYNC RESPONSE ===');
                    console.log(data);
                    
                    this.isLoading = false;
                    this.isSaveSuccessful = true;

                    if (this.isNewBrand) {
                        this.brand.id = payload.id;
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
                    console.error('=== SAVE ERROR ===');
                    console.error('Error:', error);
                    
                    let errorMessage = 'An error occurred while saving';
                    if (error.errors) {
                        errorMessage = error.errors.map(e => e.detail || e.title).join(', ');
                    } else if (error.message) {
                        errorMessage = error.message;
                    }
                    
                    this.createNotificationError({
                        title: 'Error',
                        message: errorMessage,
                    });
                });
        },

        createId() {
            return 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'.replace(/[x]/g, () => {
                return (Math.random() * 16 | 0).toString(16);
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
            this.saveBrand();
        },

        onCancel() {
            this.$router.push({ name: 'santafatex.brands.index' });
        },
    },
});