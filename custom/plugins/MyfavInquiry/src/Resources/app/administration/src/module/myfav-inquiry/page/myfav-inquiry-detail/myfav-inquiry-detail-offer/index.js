import template from './myfav-inquiry-detail-offer.html.twig';
import './myfav-inquiry-detail-offer.scss';
import InquiryOfferDocumentApiService from "../../../service/api/inquiry-offer-document.api.service";
const {Component, Context, Application, Service} = Shopware;
const {Criteria} = Shopware.Data;

Component.register('myfav-inquiry-detail-offer', {
    template,

    inject: [
        'repositoryFactory',
        'acl'
    ],

    props: {
        inquiryId: {
            type: String,
            required: true,
            default: null
        },

        reload: {
            type: Boolean,
            required: false,
            default: false
        }
    },

    data() {
        return {
            offers: null,
            inquiryOfferDocumentApiService: null,
            repository: null,
            isLoading: true
        };
    },

    created() {
        const httpClient = Application.getContainer('init')['httpClient'];
        const loginService = Service('loginService');
        this.inquiryOfferDocumentApiService = new InquiryOfferDocumentApiService(
            httpClient,
            loginService
        );
        this.repository = this.repositoryFactory.create('myfav_inquiry_offer');
        this.loadOffers();
    },

    computed: {
        offerColumns() {
            return [
                { 
                    property: 'offerNumber', 
                    label: this.$tc('myfav-inquiry.detail.offerNumber'),
                    primary: true
                },
                { 
                    property: 'createdAt', 
                    label: this.$tc('myfav-inquiry.detail.offerCreatedAt')
                }
            ];
        }
    },

    methods: {
        loadOffers() {
            let criteria = new Criteria();
            criteria
                .addAssociation('media')
            criteria
                .addFilter(
                    Criteria.equals('inquiryId', this.inquiryId)
                )
            this.repository
                .search(criteria, Context.api)
                .then((result) => {
                    this.offers = result;
                    this.isLoading = false;
                });
        },

        deleteOffer(offer) {
            this.isLoading = true;
            this.repository.delete(offer.id, Context.api).then(() => {
                this.loadOffers();
                this.isLoading = false;
            });
        },

        downloadDocument(offer) {
            this.inquiryOfferDocumentApiService
                .downloadDocument(offer.id)
                .then((blob) => {
                    const filename = `offer_${offer.offerNumber}.pdf`;
                    const link = document.createElement('a');
                    link.href = URL.createObjectURL(blob);
                    link.download = filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    URL.revokeObjectURL(link.href);
                })
                .catch((error) => {
                    console.error('Download failed:', error);
                });
        }
    },

    watch: {
        reload: function(newVal, oldVal) {
            if(newVal === true && oldVal === false) {
                this.loadOffers();
                this.reload = false;
            }
        }
    }

});
