//import ApiService from 'src/core/service/api.service';
const { ApiService } = Shopware.Classes;
export default class InquiryOfferDocumentApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'inquiryMedia') {
        super(httpClient, loginService, apiEndpoint);
        this.name = 'InquiryOfferDocumentService';
        this.$listener = () => ({});
    }

    createDocument(inquiryId) {
        const route = `_action/myfav_inquiry_offer/create/${inquiryId}`;
        return this.httpClient.post(
            route,
            {},
            {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return response.data;
        });
    }

    downloadDocument(offerId) {
        const route = `_action/myfav_inquiry_offer/download/${offerId}`;
        return this.httpClient.get(
            route,
            {
                responseType: 'blob',
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return response.data;
        });
    }
}
