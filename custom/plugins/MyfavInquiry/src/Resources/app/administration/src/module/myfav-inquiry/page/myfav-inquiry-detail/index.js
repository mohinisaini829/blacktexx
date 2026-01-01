import template from './myfav-inquiry-detail.html.twig';
import './myfav-inquiry-detail-media';
import './myfav-inquiry-detail-offer';
import InquiryOfferDocumentApiService from "../../service/api/inquiry-offer-document.api.service";

import './myfav-inquiry-detail.scss';

const {Component, Mixin, Context, Application, Service} = Shopware;
const {Criteria} = Shopware.Data;

Component.register('myfav-inquiry-detail', {
    template,

    inject: [
        'repositoryFactory',
        'acl',
        'loginService',
        'syncService'
    ],

    mixins: [
        Mixin.getByName('notification')
    ],

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    data() {
        return {
            inquiry: null,
            isLoading: false,
            processSuccess: false,
            repository: null,
            variantNames: {},
            activeModal: null,
            inquiryOfferDocumentApiService: null,
            reloadOffers: false,
            extendedData: {},
            extendedDataOriginalProducts: {},
            designerData: null,
            designerLinks: {},
            loadingAdditionalData: true,
            customerId: null
        };
    },

    created() {
        this.repository = this.repositoryFactory.create('myfav_inquiry');
        const httpClient = Application.getContainer('init')['httpClient'];
        const loginService = Service('loginService');
        this.inquiryOfferDocumentApiService = new InquiryOfferDocumentApiService(
            httpClient,
            loginService
        );
        this.getInquiry();
    },

    mounted() {
        this.$nextTick(() => {
        });
    },

    computed: {
        variantProductIds() {
            const variantProductIds = [];

            this.inquiry.lineItems.forEach((item) => {
                if (!item.product || !item.product.parentId || item.product.translated.name || item.product.name) {
                    return;
                }

                variantProductIds.push(item.product.id);
            });

            return variantProductIds;
        },

        variantCriteria() {
            const criteria = new Criteria();
            criteria.setIds(this.variantProductIds);

            return criteria;
        },

        productRepository() {
            return this.repositoryFactory.create('product');
        },

        lineItemRepository() {
            return this.repositoryFactory.create('myfav_inquiry_line_item');
        },

        lineItemsColums() {
            return [
                { property: 'product', label: this.$tc('myfav-inquiry.detail.itemNameColumn') },
                { property: 'brand', label: this.$tc('myfav-inquiry.detail.itemBrandColumn') },
                { property: 'color', label: this.$tc('myfav-inquiry.detail.itemColorColumn') },
                { property: 'size', label: this.$tc('myfav-inquiry.detail.itemSizeColumn') },
                { property: 'quantity', label: this.$tc('myfav-inquiry.detail.itemQuantityColumn') },
                { property: 'price', label: this.$tc('myfav-inquiry.detail.itemPriceColumn') },
                { property: 'image', label: this.$tc('myfav-inquiry.detail.itemImageColumn') }
            ];
        }
    },

    methods: {
        /**
         * Vue.js Template-Logging Unterstützung. (Kann im vue Template mit {{ log(variable) }} verwendet werden.)
         */
        log(message) {
            console.log(message);
        },

        /**
         * Wenn es sich um einen designeten Artikel handelt, versucht diese Methode,
         * den Namen des Original-Produktes zu ermitteln.
         */
        getExtendedProductsName(inquiryItemId) {
            let designerLineItem = this.getDesignerDataForLineItem(inquiryItemId);
            console.log(designerLineItem);
            if(designerLineItem === null) {
                return;
            }

            if(designerLineItem.hasOwnProperty('originalProductName')) {
                return designerLineItem.originalProductName;
            }

            return "";
        },
        getExtendedProductsNumber(inquiryItemId) {
            let designerLineItem = this.getDesignerDataForLineItem(inquiryItemId);
            console.log(designerLineItem);
            if(designerLineItem === null) {
                return;
            }

            if(designerLineItem.hasOwnProperty('model')) {
                return designerLineItem.model;
            }

            return "";
        },
        getExtendedProductsBrand(inquiryItemId) {
            let designerLineItem = this.getDesignerDataForLineItem(inquiryItemId);
            console.log(designerLineItem);
            if(designerLineItem === null) {
                return;
            }

            if(designerLineItem.hasOwnProperty('brand')) {
                return designerLineItem.brand;
            }

            return "";
        },
        /**
         * New Method to Download Image on Click
         */
        downloadImage(imageUrl,altText) {
            var a = document.createElement('a');
            a.href = imageUrl;
            a.download = altText + '.jpg'; // Using the alt text as the filename
            a.click();
        },
        /**
         * Wenn es sich um einen designeten Artikel handelt, versucht diese Methode,
         * den Namen des Original-Produktes zu ermitteln.
         */
        getExtendedModifiedProductImage(inquiryItemId) {
            const designerLineItem = this.getDesignerDataForLineItem(inquiryItemId);

            if (!designerLineItem || !designerLineItem.modifiedProductImage) {
                return '';
            }

            const baseImageUrl = 'https://shopware678.ezxdemo.com/';

            return baseImageUrl + designerLineItem.modifiedProductImage;
        },
        


        /**
         * Designer Daten für ein LineItem aus den Objekt-Daten ermitteln.
         */
        getDesignerDataForLineItem(inquiryLineItemId) {
            if(this.designerData === null) {
                return null;
            }

            if(!this.designerData.hasOwnProperty('lineItemsExtendedData')) {
                return null;
            }

            if(!this.designerData.lineItemsExtendedData.hasOwnProperty(inquiryLineItemId)) {
                return null;
            }

            return this.designerData.lineItemsExtendedData[inquiryLineItemId];
        },

        /**
         * Wenn es sich um einen designeten Artikel handelt, versucht diese Methode,
         * die ID des Original-Produktes zu ermitteln.
         */
        getExtendedProductsId(inquiryItemId) {
            let designerLineItem = this.getDesignerDataForLineItem(inquiryItemId);

            if(designerLineItem === null) {
                return;
            }

            if(designerLineItem.hasOwnProperty('originalProductId')) {
                return designerLineItem.originalProductId;
            }

            return "";
        },

        /**
         * Wenn es sich um einen designeten Artikel handelt, versucht diese Methode,
         * die gewünschte Größe zu ermitteln.
         */
        getExtendedProductsSizeName(inquiryItemId) {
            let designerLineItem = this.getDesignerDataForLineItem(inquiryItemId);
            console.log(designerLineItem);
            if(designerLineItem === null) {
                return;
            }

            if(designerLineItem.hasOwnProperty('sizeName')) {
                return designerLineItem.sizeName;
            }

            return "";
        },

        getExtendedProductsColor(inquiryItemId) {
            let designerLineItem = this.getDesignerDataForLineItem(inquiryItemId);
            console.log(designerLineItem);
            if(designerLineItem === null) {
                return;
            }

            if(designerLineItem.hasOwnProperty('color')) {
                return designerLineItem.color;
            }

            return "";
        },

        /**
         * Anfrage laden.
         */
        getInquiry() {
            let criteria = new Criteria();
            criteria
                .addAssociation('medias')
                .addAssociation('lineItems.product.options.group')
                .addAssociation('salutation')
            ;
            this.repository
                .get(this.$route.params.id, Context.api, criteria)
                .then((entity) => {
                    this.inquiry = entity;
                    this.loadVariantNames();
                    this.loadAdditionalData();

                    this.customerId = this.inquiry.customerId;
                });
        },

        /**
         * Load variant names, cause api don't load name by parent automatically.
         */
        loadVariantNames() {
            this.productRepository.search(this.variantCriteria, { ...Context.api, inheritance: true }).then((variants) => {
                const variantNames = {};
                variants.forEach((variant) => {
                    variantNames[variant.id] = variant.translated.name;
                });
                this.variantNames = variantNames;
            });
        },

        /**
         * Load additional data (for configured products).
         */
        loadAdditionalData() {
            /*
            ACHTUNG! Diese Daten werden asynchron geladen. Im Template wird deswegen ein vue template verwendet,
            um diesen Abschitt sinnvoll darstellen zu können.
            Wir laden hier alle erweiterten Produkte gleichzeitig, damit dass auch wirklich funktioniert.
             */

            // Header setzen.
            const headers = {
                Authorization: `Bearer ${this.loginService.getToken()}`
            };

            // HttpClient aus dem SyncService abholen.
            const httpClient = this.syncService.httpClient;

            // Post-Parameter erstellen.
            const postParams = new FormData();
            postParams.append('inquiryId', this.inquiry.id);
            console.log('hiiii');
            console.log(headers);
            // Anfrage an den Server stellen.
            httpClient.post(
                'myfav/designer/getItems',
                postParams,
                { headers:headers }
            ).then((response) => {
                console.log('response (by mv):', response);

                this.designerData = response.data;
                this.loadingAdditionalData = false;
            });
        },

        /**
         * Handler that is executed, when a user clicks on the save button.
         */
        onClickSave() {
            this.isLoading = true;

            this.inquiry.customerId = this.customerId;

            this.repository
                .save(this.inquiry, Context.api)
                .then(() => {
                    this.getInquiry();
                    this.isLoading = false;
                    this.processSuccess = true;
                }).catch((exception) => {
                this.isLoading = false;
                this.createNotificationError({
                    title: this.$t('myfav-inquiry.detail.saveErrorTitle'),
                    message: exception.message
                });
            });
        },

        /**
         * Wird aufgerufen, wenn der Speichervorgang beendet wird.
         */
        saveFinish() {
            this.processSuccess = false;
        },

        /**
         * Modal: Item entfernen.
         */
        deleteLineItem(item) {
            this.isLoading = true;
            this.lineItemRepository.delete(item.id, Context.api).then(() => {
                this.getInquiry();
                this.isLoading = false;
            });
        },

        /**
         * Modal: Item hinzuufügen
         */
        openAddLineItemModal() {
            this.activeModal = 'addLineItem';
        },

        /**
         * Popup schließen.
         */
        onCloseModal() {
            this.activeModal = false;
        },

        /**
         * Angebots-Dokument erstellen.
         */
        createOfferDocument() {
            this.isLoading = true;
            this.inquiryOfferDocumentApiService.createDocument(this.inquiry.id).then((response) => {
                this.reloadOffers = true;
                this.isLoading = false;
            });
        },

        /**
         * Link zum Designer setzen. Der Link soll ein Token enthalten, um sich direkt im Designer einzuloggen.
         */
        getDesignerLink(inquiryItemId) {
            let designerLineItem = this.getDesignerDataForLineItem(inquiryItemId);

            if(designerLineItem === null) {
                return "";
            }

            let url = this.designerData.designerBaseUrl;

            url += '?';
            url += 'product_base=' + designerLineItem['lumiseArticleId'];
            url += '&mvtoken=' + this.designerData.designerLoginToken;
            url += '&lumiseTmpCartId=' + designerLineItem['lumiseTmpCartId'];
            url += '&lumiseShopwareDesignsId=' + designerLineItem['lumiseShopwareDesignsId'];
            url += '&mode=admin';
            url += '&inquiryId=' + this.inquiry.id;
            url += '&inquiryItemId=' + inquiryItemId;

            return '<a href="' + url + '" target="_blank">Im Designer öffnen</a>';
        },

        /**
         * Get's triggered, when the value of the customer select has been changed.
         * @param {*} id 
         * @param {*} item 
         */
        updateAssignedCustomer(id, item) {
            console.log('dingsi');

            this.customerId = id;

            this.log('done');
            this.log(id);
        }
    }
});
