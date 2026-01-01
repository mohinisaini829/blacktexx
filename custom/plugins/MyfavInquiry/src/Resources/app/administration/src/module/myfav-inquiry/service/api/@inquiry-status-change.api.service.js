// Import the base ApiService class from Shopware
const { ApiService } = Shopware.Classes;

export default class InquiryStatusChangeApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'inquiryStatus') {
        // Call the parent constructor of ApiService
        super(httpClient, loginService, apiEndpoint);
        this.name = 'InquiryStatusChangeService';
        this.$listener = () => ({});
    }

    // Change status of the inquiry
    changeStatus(inquiryId, newStatus) {
        console.log('api');
        // Define the API endpoint for updating the inquiry status
        const route = `/api/_action/inquiry/update-status`;

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
