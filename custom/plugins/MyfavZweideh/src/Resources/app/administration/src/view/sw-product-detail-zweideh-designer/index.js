import template from './sw-product-detail-zweideh-designer.html.twig';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;
const { mapState, mapGetters } = Component.getComponentHelper();

Shopware.Component.register('sw-product-detail-zweideh-designer', {
    template,

    inject: [
        'repositoryFactory'
    ],

    metaInfo() {
        return {
            title: 'Designer'
        };
    },

    data() {
        return {
            designerSetup: null,
            designerSetups: null,
            designerProduct: null,
            designerProducts: [
                { id: "1", name: "abc" },
                { id: "2", name: "xyz"}
            ],
            designerProductsCriteria: null,
            isLoading: false,
            processSuccess: false,
            designerSetupRepository: null,
            designerProductRepository: null,
            productLoaded: false
        };
    },

    computed: {
        ...mapState('swProductDetail', [
            'product',
            'parentProduct',
        ]),

        ...mapGetters('swProductDetail', [
            'isLoading',
        ]),
    },

    watch: {
        product() {
            console.log('product changed');

            // Dieser Code wird nur einmal ausgeführt, wenn die Seite geladen wurde.
            // Er stellt sicher, dass er ausgeführt wird, wenn die Produktdaten zur Verfügung stehen,
            // und erlaubt damit ein sauberes Initialisieren der beiden Dropdown-Boxen,
            // die in ihren Werten voneinander abhängig sind,
            // und gleichzeitig von Werten im "Produkt" abhängig sind.
            if(!this.productLoaded) {
                this.productLoaded = true;
                this.setDesignerProductDropdown(
                    this.product.customFields.myfav_zweideh_designer_setup_id,
                    this.product.customFields.myfav_zweideh_designer_product_id
                );
            }
        },
    },

    created() {
        console.log('created');
        this.createdComponent();

        if(typeof this.product.customFields !== 'undefined') {
            this.setDesignerProductDropdown(
                this.product.customFields.myfav_zweideh_designer_setup_id,
                this.product.customFields.myfav_zweideh_designer_product_id
            );
        }
    },

    methods: {
        createdComponent() {
            console.log('createdComponent()');
            
            this.designerSetupRepository = this.repositoryFactory.create('myfav_designer_setup');
            this.designerProductRepository = this.repositoryFactory.create('myfav_designer_product');
        },

        onSelectDesignerSetup(designerSetup) {
            console.log('onSelectDesignerSetup()');

            this.designerSetup = designerSetup;
            this.product.customFields.myfav_zweideh_designer_setup_id = designerSetup;

            this.setDesignerProductDropdown(this.designerSetup);
        },

        setDesignerProductDropdown(designerSetupId, designerProductId = null) {
            console.log('setDesignerProductDropdown()');
            
            // Reset designerProducts first
            if(designerProductId !== null) {
                console.log('designerProductId: ', designerProductId);
                this.designerProduct = designerProductId;
            } else {
                this.designerProduct = null;
            }

            this.designerProducts = [];

            // Load designerProducts by designerSetup
            let criteria = new Criteria();
            criteria.setLimit(1000);
            criteria.addFilter(
                Criteria.equals('myfavDesignerSetupId', designerSetupId)
            );

            console.log('set criteria.');

            this.designerProductRepository
                .search(criteria, Shopware.Context.api)
                .then(data => {
                    this.designerProducts.push({ id: '0', name: 'Bitte wählen' });
                    
                    // Update designerProducts with new data
                    for(let i = 0, length = data.length; i < length; i++) {
                        let entry = {
                            id: data[i].id,
                            name: data[i].name
                        };

                        this.designerProducts.push(entry);
                    }
                });

            console.log('done, setting it');
        },

        //onSelectDesignerProduct(designerProduct) {
        onSelectDesignerProduct(event) {
            console.log('onSelectDesignerProduct()');
            console.log(event.target);
            this.designerProduct = event.target.value;
            this.product.customFields.myfav_zweideh_designer_product_id = this.designerProduct;
        },

        getDesignerSetupId() {
            if('customFields' in this.product) {
                console.log('getDesignerSetupId');
                return this.product.customFields.myfav_zweideh_designer_setup_id;
            }

            return null;
        },

        getDesignerProductId() {
            if('customFields' in this.product) {
                console.log('getDesignerProductId');

                console.log(this.product.customFields.myfav_zweideh_designer_product_id);

                console.log(this.designerProduct);

                this.designerProduct = this.product.customFields.myfav_zweideh_designer_product_id;

                return this.product.customFields.myfav_zweideh_designer_product_id;
            }

            return null;
        },
    }
});