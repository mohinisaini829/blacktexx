//import ApiService from 'src/core/service/api.service';
const { ApiService } = Shopware.Classes;
export default class InquiryMediaApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'inquiryMedia') {
        super(httpClient, loginService, apiEndpoint);
        this.name = 'inquiryMediaService';
        this.$listener = () => ({});
    }

    getMedia(mediaId) {
        const route = `/_action/myfav_inquiry_media/media/${mediaId}`;
        return this.httpClient.get(route, {
            headers: this.getBasicHeaders(),
            responseType: 'blob'
        });
    }
    changeStatus(inquiryId, newStatus) {
        // Define the API endpoint for updating the inquiry status
        const route = `/_action/inquiry/update-status`;

        // Return the API call using the Shopware httpClient
        return this.httpClient.post(route, {
            id: inquiryId,  // Inquiry ID
            status: newStatus // New status
        }, {
            // Headers (Authorization could be handled in getBasicHeaders())
            headers: this.getBasicHeaders(),
            responseType: 'json'  // Expecting JSON response
        });
    }
}
